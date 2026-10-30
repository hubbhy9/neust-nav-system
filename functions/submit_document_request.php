<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/audit.php';

header('Content-Type: application/json; charset=utf-8');

function respondRequest(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function respondAndContinueRequest(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return;
    }
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @flush();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondRequest(405, ['error' => 'Use POST to submit document requests.']);
}

$raw = file_get_contents('php://input');
$data = isset($GLOBALS['documentRequestApiPayload']) && is_array($GLOBALS['documentRequestApiPayload'])
    ? $GLOBALS['documentRequestApiPayload']
    : (is_string($raw) && $raw !== '' ? json_decode($raw, true) : $_POST);
if (!is_array($data)) {
    respondRequest(400, ['error' => 'The request body is invalid.']);
}

$csrf = (string) ($data['csrf_token'] ?? '');
$expectedCsrf = (string) ($_SESSION['document_request_csrf'] ?? '');
$ionicCsrf = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
$expectedIonicCsrf = (string) ($_SESSION['ionic_csrf_token'] ?? '');
$validWebCsrf = $csrf !== '' && $expectedCsrf !== '' && hash_equals($expectedCsrf, $csrf);
$validIonicCsrf = $ionicCsrf !== '' && $expectedIonicCsrf !== '' && hash_equals($expectedIonicCsrf, $ionicCsrf);
if (!$validWebCsrf && !$validIonicCsrf) {
    respondRequest(403, ['error' => 'Your session expired. Refresh the document page and try again.']);
}
$isIonicRequest = $validIonicCsrf && !$validWebCsrf;

$isGuest = !empty($_SESSION['is_guest']);
$userId = isset($_SESSION['id']) && !$isGuest ? (int) $_SESSION['id'] : null;
if (!$isGuest && !$userId) {
    respondRequest(401, ['error' => 'Please log in before submitting a request.']);
}

$documentIds = $data['document_ids'] ?? [];
if (!is_array($documentIds)) {
    respondRequest(422, ['error' => 'Select at least one document.']);
}
$documentIds = array_values(array_unique(array_filter(array_map('intval', $documentIds), static fn (int $id): bool => $id > 0)));
if ($documentIds === [] || count($documentIds) > 500) {
    respondRequest(422, ['error' => 'Select between 1 and 500 documents to request.']);
}

if ($isGuest) {
    $firstName = trim((string) ($data['fname'] ?? ''));
    $middleName = trim((string) ($data['mname'] ?? ''));
    $lastName = trim((string) ($data['lname'] ?? ''));
    $suffix = trim((string) ($data['suffix'] ?? ''));
    $requesterEmail = strtolower(trim((string) ($data['requester_email'] ?? '')));
    $requesterPhone = trim((string) ($data['requester_phone'] ?? ''));
    $namePattern = '/^[\p{L} ]+$/u';
    if (
        $firstName === '' || $lastName === '' ||
        !preg_match($namePattern, $firstName) ||
        ($middleName !== '' && !preg_match($namePattern, $middleName)) ||
        ($suffix !== '' && !preg_match($namePattern, $suffix)) ||
        !filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)
    ) {
        respondRequest(422, ['error' => 'Use letters only for names, and enter your first name, last name, and a valid email address.']);
    }
    if (
        mb_strlen($firstName, 'UTF-8') > 80 ||
        mb_strlen($middleName, 'UTF-8') > 80 ||
        mb_strlen($lastName, 'UTF-8') > 80 ||
        mb_strlen($suffix, 'UTF-8') > 20
    ) {
        respondRequest(422, ['error' => 'Names must be 80 characters or fewer and suffix must be 20 characters or fewer.']);
    }
} else {
    $userStmt = $db->prepare('SELECT first_name, middle_name, last_name, suffix, email, mobile_number FROM user WHERE id = ? LIMIT 1');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) respondRequest(401, ['error' => 'Your account could not be verified. Please log in again.']);
    $firstName = trim((string) $user['first_name']);
    $middleName = trim((string) ($user['middle_name'] ?? ''));
    $lastName = trim((string) $user['last_name']);
    $suffix = trim((string) ($user['suffix'] ?? ''));
    $requesterName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix], static fn (string $part): bool => $part !== '')));
    $requesterEmail = strtolower(trim((string) $user['email']));
    $requesterPhone = trim((string) ($user['mobile_number'] ?? ''));
}

if ($isGuest) {
    $requesterName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix], static fn (string $part): bool => $part !== '')));
}

$placeholders = implode(',', array_fill(0, count($documentIds), '?'));
$documentsStmt = $db->prepare("SELECT d.id, d.title, o.name AS office_name, c.name AS classification FROM documents d LEFT JOIN offices o ON o.id = d.office_id LEFT JOIN classifications c ON c.id = d.classification_id WHERE d.id IN ($placeholders)");
$documentsStmt->execute($documentIds);
$documentsById = [];
foreach ($documentsStmt->fetchAll(PDO::FETCH_ASSOC) as $document) {
    $documentsById[(int) $document['id']] = $document;
}
if (count($documentsById) !== count($documentIds)) {
    respondRequest(422, ['error' => 'One or more selected documents are no longer available. Refresh and try again.']);
}

$accessToken = bin2hex(random_bytes(32));
$accessTokenHash = hash('sha256', $accessToken);
$accessExpiresAt = documentRequestAccessExpiresAt();
$accessOrigin = documentRequestAccessOrigin();

$random = bin2hex(random_bytes(16));
$batchId = substr($random, 0, 8) . '-' . substr($random, 8, 4) . '-4' . substr($random, 13, 3) . '-a' . substr($random, 17, 3) . '-' . substr($random, 20, 12);
$db->beginTransaction();
try {
    $insertRequest = $db->prepare('INSERT INTO document_requests (request_batch_id, requester_user_id, fname, mname, lname, suffix, requester_name, requester_email, requester_phone, request_access_token_hash, request_access_expires_at, request_access_origin, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insertRequest->execute([$batchId, $userId, $firstName, $middleName ?: null, $lastName, $suffix ?: null, $requesterName, $requesterEmail, $requesterPhone ?: null, $accessTokenHash, $accessExpiresAt, $accessOrigin ?: null, $isIonicRequest ? 'ionic' : 'web']);
    $requestId = (int) $db->lastInsertId();
    $insertItem = $db->prepare('INSERT INTO document_request_items (request_id, source_document_id, document_title, office_name) VALUES (?, ?, ?, ?)');
    foreach ($documentIds as $documentId) {
        $document = $documentsById[$documentId];
        $insertItem->execute([$requestId, $documentId, $document['title'], $document['office_name']]);
    }
    auditLog($db, [
        'action' => 'CREATE',
        'module' => 'Document Requests',
        'target_type' => 'document_request',
        'target_id' => $requestId,
        'target_name' => $requesterName,
        'description' => 'Submitted a document request containing ' . count($documentIds) . ' document(s).',
        'new_values' => [
            'document_count' => count($documentIds),
            'document_titles' => array_column($documentsById, 'title'),
            'source' => $isGuest ? 'guest' : 'user',
        ],
        'status' => 'success',
    ]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Document request submission failed: ' . $error->getMessage());
    respondRequest(500, ['error' => 'The request could not be saved. Please try again.']);
}

respondAndContinueRequest(201, [
    'ok' => true,
    'request_id' => $requestId,
    'request_batch_id' => $batchId,
    'document_count' => count($documentIds),
    'access_expires_at' => $accessExpiresAt,
    'email_sent' => null,
    'email_pending' => true,
]);

if ($accessOrigin !== '') {
    $accessUrl = rtrim($accessOrigin, '/') . '/request-access?token=' . rawurlencode($accessToken);
    try {
        $emailSent = sendDocumentRequestEmail($requesterEmail, $requesterName, $requestId, 'pending', array_column($documentsById, 'title'), $accessUrl, $accessExpiresAt);
    } catch (Throwable $emailError) {
        error_log('Request access email exception for request #' . $requestId . ': ' . $emailError->getMessage());
        $emailSent = false;
    }
    if (!$emailSent) {
        error_log('Request access email could not be sent for request #' . $requestId);
    }
} else {
    error_log('Request access email skipped: APP_PUBLIC_URL and HTTP_ORIGIN are unavailable.');
}

if (!empty($GLOBALS['documentRequestApiMode'])) {
    exit;
}