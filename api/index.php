<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$isSecureRequest = $forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecureRequest,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function apiRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function requestPayload(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return [];
    }

    $payload = json_decode($raw, true);
    return is_array($payload) ? $payload : [];
}

function requestIsSameOrigin(): bool
{
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') {
        return true;
    }

    $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
    $forwardedHost = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''))[0]);
    $requestHost = $forwardedHost !== '' ? $forwardedHost : (string) ($_SERVER['HTTP_HOST'] ?? '');
    $requestHost = strtolower(preg_replace('/:\d+$/', '', $requestHost) ?? '');

    if ($originHost === '' || !in_array(strtolower((string) parse_url($origin, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        return false;
    }
    if ($requestHost !== '' && hash_equals($requestHost, $originHost)) {
        return true;
    }

    $configuredOrigin = trim((string) getenv('APP_PUBLIC_URL'));
    if ($configuredOrigin === '') {
        $publicUrlFile = dirname(__DIR__) . '/ionic-map-app/.public-app-url';
        if (is_readable($publicUrlFile)) {
            $configuredOrigin = trim((string) file_get_contents($publicUrlFile));
        }
    }
    $configuredHost = strtolower((string) parse_url($configuredOrigin, PHP_URL_HOST));

    return $configuredHost !== '' && hash_equals($configuredHost, $originHost);
}

function csrfToken(): string
{
    if (empty($_SESSION['ionic_csrf_token'])) {
        $_SESSION['ionic_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['ionic_csrf_token'];
}

function apiUserFromSession(): ?array
{
    if (!empty($_SESSION['is_guest'])) {
        return [
            'id' => 0,
            'name' => 'Guest',
            'email' => '',
            'role' => 'Guest',
            'office' => '',
            'role_id' => 0,
            'isAdmin' => false,
            'isGuest' => true,
        ];
    }

    if (empty($_SESSION['id'])) {
        return null;
    }

    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    $firstName = trim((string) ($_SESSION['first_name'] ?? ''));
    $lastName = trim((string) ($_SESSION['last_name'] ?? ''));

    return [
        'id' => (int) $_SESSION['id'],
        'name' => trim($firstName . ' ' . $lastName),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'role' => $roleId === 1 ? 'Admin' : 'User',
        'office' => (string) ($_SESSION['office'] ?? ''),
        'role_id' => $roleId,
        'isAdmin' => $roleId === 1,
        'isGuest' => false,
    ];
}

$action = trim((string) ($_GET['action'] ?? ''));
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($action === 'session' && $method === 'GET') {
    apiRespond(200, ['user' => apiUserFromSession(), 'csrfToken' => apiUserFromSession() ? csrfToken() : null]);
}

if ($action === 'services' && $method === 'GET') {
    if (!apiUserFromSession()) {
        apiRespond(401, ['error' => 'Please log in or continue as guest to browse services.']);
    }

    require_once __DIR__ . '/../libraries/db.php';
    $servicesStmt = $db->query('SELECT d.id AS process_id, d.title, o.name AS office_name, c.name AS classification, d.who_may_avail, (SELECT GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name SEPARATOR "; ") FROM document_transaction_types dtt INNER JOIN transaction_types tt ON tt.id = dtt.transaction_type_id WHERE dtt.document_id = d.id) AS transaction_type, (SELECT fees FROM `total` WHERE document_id = d.id ORDER BY id LIMIT 1) AS total_fee, (SELECT time FROM `total` WHERE document_id = d.id ORDER BY id LIMIT 1) AS total_time FROM documents d LEFT JOIN offices o ON o.id = d.office_id LEFT JOIN classifications c ON c.id = d.classification_id ORDER BY d.title');
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($services as &$service) {
        $documentId = (int) $service['process_id'];
        $requirementsStmt = $db->prepare('SELECT requirement_name, where_to_secure FROM requirements WHERE document_id = ? ORDER BY id');
        $requirementsStmt->execute([$documentId]);
        $service['requirements'] = $requirementsStmt->fetchAll(PDO::FETCH_ASSOC);

        $stepsStmt = $db->prepare('SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible FROM document_steps WHERE document_id = ? ORDER BY step_number, id');
        $stepsStmt->execute([$documentId]);
        $service['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
        $service['persons'] = array_values(array_unique(array_filter(array_map(
            static fn (array $step): string => trim((string) ($step['person_responsible'] ?? '')),
            $service['steps']
        ))));
    }
    unset($service);

    require_once __DIR__ . '/../functions/audit.php';
    auditLogSafely($db, [
        'action' => 'READ',
        'module' => 'Ionic Services',
        'target_type' => 'service_catalog',
        'target_name' => 'Campus services',
        'description' => 'Viewed the campus service catalog through Ionic.',
        'new_values' => ['service_count' => count($services)],
    ]);

    apiRespond(200, ['services' => $services]);
}

if ($action === 'request-access' && $method === 'GET') {
    $token = strtolower(trim((string) ($_GET['token'] ?? '')));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        apiRespond(400, ['error' => 'This request link is invalid or has expired.']);
    }

    require_once __DIR__ . '/../libraries/db.php';
    $tokenHash = hash('sha256', $token);
    $requestStmt = $db->prepare('SELECT request_id, status, created_at, LEAST(request_access_expires_at, DATE_ADD(created_at, INTERVAL 3 HOUR)) AS expires_at FROM document_requests WHERE request_access_token_hash = ? AND LEAST(request_access_expires_at, DATE_ADD(created_at, INTERVAL 3 HOUR)) > NOW() UNION ALL SELECT r.request_id, r.status, r.created_at, LEAST(t.expires_at, DATE_ADD(t.created_at, INTERVAL 3 HOUR)) AS expires_at FROM document_request_access_tokens t INNER JOIN document_requests r ON r.request_id = t.request_id WHERE t.token_hash = ? AND LEAST(t.expires_at, DATE_ADD(t.created_at, INTERVAL 3 HOUR)) > NOW() LIMIT 1');
    $requestStmt->execute([$tokenHash, $tokenHash]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
    if (!$request) {
        apiRespond(404, ['error' => 'This request link is invalid or has expired.']);
    }

    $items = [];
    if ((string) $request['status'] === 'accepted') {
        $itemsStmt = $db->prepare('SELECT i.item_id AS process_id, i.source_document_id, i.document_title AS title, i.office_name, d.who_may_avail, c.name AS classification, (SELECT GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name SEPARATOR "; ") FROM document_transaction_types dtt INNER JOIN transaction_types tt ON tt.id = dtt.transaction_type_id WHERE dtt.document_id = d.id) AS transaction_type, (SELECT fees FROM `total` WHERE document_id = d.id ORDER BY id LIMIT 1) AS total_fee, (SELECT time FROM `total` WHERE document_id = d.id ORDER BY id LIMIT 1) AS total_time FROM document_request_items i LEFT JOIN documents d ON d.id = i.source_document_id LEFT JOIN classifications c ON c.id = d.classification_id WHERE i.request_id = ? ORDER BY i.item_id');
        $itemsStmt->execute([(int) $request['request_id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $documentId = (int) $item['source_document_id'];
            $requirementsStmt = $db->prepare('SELECT requirement_name, where_to_secure FROM requirements WHERE document_id = ? ORDER BY id');
            $requirementsStmt->execute([$documentId]);
            $item['requirements'] = $requirementsStmt->fetchAll(PDO::FETCH_ASSOC);

            $stepsStmt = $db->prepare('SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible FROM document_steps WHERE document_id = ? ORDER BY step_number, id');
            $stepsStmt->execute([$documentId]);
            $item['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

            $item['persons'] = [];
        }
        unset($item);
    }

    require_once __DIR__ . '/../functions/audit.php';
    auditLogSafely($db, [
        'action' => 'READ',
        'module' => 'Ionic Document Access',
        'target_type' => 'document_request',
        'target_id' => (int) $request['request_id'],
        'target_name' => 'Request #' . (int) $request['request_id'],
        'description' => 'Viewed private document request details through Ionic.',
        'new_values' => ['status' => (string) $request['status'], 'document_count' => count($items)],
    ]);

    apiRespond(200, [
        'request' => [
            'request_id' => (int) $request['request_id'],
            'status' => (string) $request['status'],
            'created_at' => (string) $request['created_at'],
            'expires_at' => (string) $request['expires_at'],
        ],
        'items' => $items,
    ]);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    apiRespond(405, ['error' => 'Method not allowed.']);
}

if (!requestIsSameOrigin()) {
    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/../functions/audit.php';
    auditLogSafely($db, [
        'action' => 'ACCESS_DENIED',
        'module' => 'API',
        'description' => 'Rejected cross-origin API request.',
        'status' => 'denied',
    ]);
    apiRespond(403, ['error' => 'Cross-origin request denied.']);
}

$payload = requestPayload();

if ($action === 'activity') {
    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/../functions/audit.php';
    if (!apiUserFromSession()) {
        apiRespond(401, ['error' => 'Please log in or continue as guest.']);
    }

    $postedToken = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    $expectedToken = (string) ($_SESSION['ionic_csrf_token'] ?? '');
    if ($expectedToken === '' || !hash_equals($expectedToken, $postedToken)) {
        auditLogSafely($db, [
            'action' => 'ACCESS_DENIED',
            'module' => 'Ionic Activity',
            'description' => 'Ionic activity event rejected because the CSRF token was invalid.',
            'status' => 'denied',
        ]);
        apiRespond(403, ['error' => 'Your session expired. Refresh and try again.']);
    }

    $eventType = strtoupper(trim((string) ($payload['event'] ?? '')));
    if ($eventType === 'PAGE_VIEW') {
        $page = auditCleanText((string) ($payload['page'] ?? ''), 100);
        if ($page === '' || !preg_match('#^[a-z0-9/-]+$#i', $page)) {
            apiRespond(400, ['error' => 'Invalid page activity.']);
        }
        auditLogSafely($db, [
            'action' => 'READ',
            'module' => 'Ionic Navigation',
            'target_type' => 'ionic_page',
            'target_name' => $page,
            'description' => 'Opened an Ionic app screen.',
            'new_values' => ['page' => $page],
        ]);
    } elseif ($eventType === 'MAP_ROUTE' || $eventType === 'MAP_DESTINATION') {
        $destination = auditCleanText((string) ($payload['destination'] ?? ''), 160);
        $start = auditCleanText((string) ($payload['start'] ?? ''), 160);
        if ($destination === '' || ($eventType === 'MAP_ROUTE' && $start === '')) {
            apiRespond(400, ['error' => 'Invalid map route activity.']);
        }
        auditLogSafely($db, [
            'action' => 'NAVIGATE',
            'module' => 'Ionic Campus Map',
            'target_type' => $eventType === 'MAP_ROUTE' ? 'campus_route' : 'campus_destination',
            'target_name' => $eventType === 'MAP_ROUTE' ? $start . ' to ' . $destination : $destination,
            'description' => $eventType === 'MAP_ROUTE'
                ? 'Calculated a campus route through the Ionic map.'
                : 'Selected a campus destination through Ionic map search.',
            'new_values' => $eventType === 'MAP_ROUTE'
                ? ['start' => $start, 'destination' => $destination]
                : ['destination' => $destination],
        ]);
    } else {
        apiRespond(400, ['error' => 'Unsupported activity event.']);
    }

    apiRespond(200, ['ok' => true]);
}

if ($action === 'document-requests') {
    $GLOBALS['documentRequestApiPayload'] = $payload;
    $GLOBALS['documentRequestApiMode'] = true;
    require __DIR__ . '/../functions/submit_document_request.php';
    apiRespond(500, ['error' => 'The document request handler did not complete.']);
}

if ($action === 'guest') {
    if (($payload['privacyAccepted'] ?? false) !== true) {
        require_once __DIR__ . '/../libraries/db.php';
        require_once __DIR__ . '/../functions/audit.php';
        auditLogSafely($db, [
            'action' => 'ACCESS_DENIED',
            'module' => 'Authentication',
            'description' => 'Guest sign-in was rejected because privacy consent was not accepted.',
            'status' => 'denied',
        ]);
        apiRespond(400, ['error' => 'Accept the privacy agreement before continuing as a guest.']);
    }

    session_regenerate_id(true);
    unset($_SESSION['id'], $_SESSION['email'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['office']);
    $_SESSION['role_id'] = 0;
    $_SESSION['is_guest'] = true;
    $_SESSION['privacy_accepted'] = 1;
    $_SESSION['ionic_csrf_token'] = bin2hex(random_bytes(32));
    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/../functions/audit.php';
    auditLogSafely($db, [
        'actor_name' => 'Guest',
        'actor_role' => 'guest',
        'action' => 'LOGIN',
        'module' => 'Authentication',
        'description' => 'Guest session started through the Ionic API.',
        'status' => 'success',
    ]);

    apiRespond(200, ['user' => apiUserFromSession(), 'csrfToken' => csrfToken()]);
}

if ($action === 'login') {
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    if ($email === '' || $password === '') {
        apiRespond(400, ['error' => 'Enter your email and password.']);
    }

    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/../functions/audit.php';
    $statement = $db->prepare('SELECT id, email, password, role_id, first_name, last_name FROM `user` WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, (string) $user['password'])) {
        auditLogSafely($db, [
            'actor_name' => mb_substr(trim((string) $email), 0, 190, 'UTF-8') ?: 'Unknown',
            'actor_role' => 'anonymous',
            'action' => 'LOGIN',
            'module' => 'Authentication',
            'target_type' => 'user',
            'target_name' => mb_substr(trim((string) $email), 0, 255, 'UTF-8'),
            'description' => 'Ionic login attempt failed.',
            'status' => 'failure',
        ]);
        apiRespond(401, ['error' => 'Invalid email or password.']);
    }

    session_regenerate_id(true);
    unset($_SESSION['is_guest']);
    $_SESSION['id'] = (int) $user['id'];
    $_SESSION['email'] = (string) $user['email'];
    $_SESSION['role_id'] = (int) $user['role_id'];
    $_SESSION['first_name'] = (string) $user['first_name'];
    $_SESSION['last_name'] = (string) $user['last_name'];
    $_SESSION['ionic_csrf_token'] = bin2hex(random_bytes(32));
    auditLogSafely($db, [
        'action' => 'LOGIN',
        'module' => 'Authentication',
        'target_type' => 'user',
        'target_id' => (int) $user['id'],
        'target_name' => (string) $user['email'],
        'description' => 'User signed in through the Ionic API.',
        'status' => 'success',
    ]);

    apiRespond(200, ['user' => apiUserFromSession(), 'csrfToken' => csrfToken()]);
}

if ($action === 'logout') {
    if (!apiUserFromSession()) {
        apiRespond(200, ['ok' => true]);
    }

    $postedToken = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    $expectedToken = (string) ($_SESSION['ionic_csrf_token'] ?? '');
    if ($expectedToken === '' || !hash_equals($expectedToken, $postedToken)) {
        require_once __DIR__ . '/../libraries/db.php';
        require_once __DIR__ . '/../functions/audit.php';
        auditLogSafely($db, [
            'action' => 'ACCESS_DENIED',
            'module' => 'Authentication',
            'description' => 'Ionic logout rejected because the CSRF token was invalid.',
            'status' => 'denied',
        ]);
        apiRespond(403, ['error' => 'Your session expired. Refresh and try again.']);
    }

    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/../functions/audit.php';
    auditLogSafely($db, [
        'action' => 'LOGOUT',
        'module' => 'Authentication',
        'description' => 'User ended the Ionic session.',
        'status' => 'success',
    ]);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
    }
    session_destroy();
    apiRespond(200, ['ok' => true]);
}

if ($action === 'request-access') {
    $token = strtolower(trim((string) ($payload['token'] ?? '')));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        apiRespond(400, ['error' => 'This request link is invalid or has expired.']);
    }

    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/../functions/mailer.php';
    $tokenHash = hash('sha256', $token);
    $requestStmt = $db->prepare('SELECT request_id, status, requester_user_id, fname, mname, lname, suffix, requester_name, requester_email, requester_phone, source, request_access_origin, last_access_link_requested_at FROM document_requests WHERE request_access_token_hash = ? UNION ALL SELECT r.request_id, r.status, r.requester_user_id, r.fname, r.mname, r.lname, r.suffix, r.requester_name, r.requester_email, r.requester_phone, r.source, r.request_access_origin, r.last_access_link_requested_at FROM document_request_access_tokens t INNER JOIN document_requests r ON r.request_id = t.request_id WHERE t.token_hash = ? LIMIT 1');
    $requestStmt->execute([$tokenHash, $tokenHash]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || !in_array((string) $request['status'], ['pending', 'accepted', 'rejected'], true)) {
        apiRespond(400, ['error' => 'This request link is invalid or unavailable. Contact the office if you need help.']);
    }

    $lastLinkRequestAt = trim((string) ($request['last_access_link_requested_at'] ?? ''));
    if ($lastLinkRequestAt !== '' && strtotime($lastLinkRequestAt) > time() - 60) {
        apiRespond(429, ['error' => 'A new link was just requested. Please wait one minute before requesting another.']);
    }

    require_once __DIR__ . '/../functions/audit.php';
        $itemsStmt = $db->prepare('SELECT source_document_id, document_title, office_name FROM document_request_items WHERE request_id = ? ORDER BY item_id');
        $itemsStmt->execute([(int) $request['request_id']]);
        $requestItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($requestItems === []) {
            apiRespond(409, ['error' => 'This request has no documents to submit for review. Please contact the office.']);
        }
        $documentTitles = array_column($requestItems, 'document_title');
        $requestOrigin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        $accessOrigin = documentRequestAccessOrigin($requestOrigin !== '' ? $requestOrigin : (string) ($request['request_access_origin'] ?? ''), true);

        if ($accessOrigin === '') {
            error_log('Replacement request access email skipped: public URL is unavailable for request #' . (int) $request['request_id']);
            apiRespond(503, ['error' => 'A new link could not be sent because the public app address is not configured. Please contact support.']);
        }

        $expiresAt = documentRequestAccessExpiresAt();
        $newToken = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newToken);
        $random = bin2hex(random_bytes(16));
        $batchId = substr($random, 0, 8) . '-' . substr($random, 8, 4) . '-4' . substr($random, 13, 3) . '-a' . substr($random, 17, 3) . '-' . substr($random, 20, 12);
        $requesterName = trim(implode(' ', array_filter([(string) ($request['fname'] ?? ''), (string) ($request['mname'] ?? ''), (string) ($request['lname'] ?? ''), (string) ($request['suffix'] ?? '')], static fn (string $part): bool => trim($part) !== '')));
        if ($requesterName === '') $requesterName = (string) $request['requester_name'];
        $followUpRequestId = 0;
        $db->beginTransaction();
        try {
            $eligibleStmt = $db->prepare('SELECT COUNT(*) FROM document_requests WHERE request_id = ? AND status = ?');
            $eligibleStmt->execute([(int) $request['request_id'], (string) $request['status']]);
            if ((int) $eligibleStmt->fetchColumn() !== 1) {
                throw new RuntimeException('Accepted request status changed before access link renewal.');
            }
            $insertRequest = $db->prepare('INSERT INTO document_requests (request_batch_id, requester_user_id, fname, mname, lname, suffix, requester_name, requester_email, requester_phone, request_access_token_hash, request_access_expires_at, request_access_origin, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insertRequest->execute([$batchId, $request['requester_user_id'], $request['fname'], $request['mname'], $request['lname'], $request['suffix'], $requesterName, $request['requester_email'], $request['requester_phone'], $newTokenHash, $expiresAt, $accessOrigin, $request['source']]);
            $followUpRequestId = (int) $db->lastInsertId();
            $copyItem = $db->prepare('INSERT INTO document_request_items (request_id, source_document_id, document_title, office_name) VALUES (?, ?, ?, ?)');
            foreach ($requestItems as $item) {
                $copyItem->execute([$followUpRequestId, $item['source_document_id'], $item['document_title'], $item['office_name']]);
            }
            $db->prepare('UPDATE document_requests SET last_access_link_requested_at = NOW() WHERE request_id = ?')->execute([(int) $request['request_id']]);
            auditLog($db, [
                'action' => 'CREATE',
                'module' => 'Document Requests',
                'target_type' => 'document_request',
                'target_id' => $followUpRequestId,
                'target_name' => $requesterName,
                'description' => 'Created a pending follow-up request from a replacement-link request.',
                'new_values' => ['follow_up_of' => (int) $request['request_id'], 'document_count' => count($requestItems), 'source' => $request['source']],
                'status' => 'success',
            ]);
            auditLog($db, [
                'action' => 'REQUEST_LINK',
                'module' => 'Document Requests',
                'target_type' => 'document_request',
                'target_id' => (int) $request['request_id'],
                'target_name' => (string) $request['requester_name'],
                'description' => 'Created pending follow-up request #' . $followUpRequestId . ' from a replacement-link request.',
                'status' => 'success',
            ]);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Pending follow-up request creation failed for request #' . (int) $request['request_id'] . ': ' . $error->getMessage());
            apiRespond(500, ['error' => 'The follow-up request could not be submitted. Please try again later.']);
        }

        $accessUrl = rtrim($accessOrigin, '/') . '/request-access?token=' . rawurlencode($newToken);
        $emailSent = false;
        try {
            $emailSent = sendDocumentRequestEmail((string) $request['requester_email'], $requesterName, $followUpRequestId, 'pending', $documentTitles, $accessUrl, $expiresAt);
        } catch (Throwable $emailError) {
            error_log('Pending follow-up email exception for request #' . $followUpRequestId . ': ' . $emailError->getMessage());
        }

        if (!$emailSent) {
            error_log('Pending follow-up email could not be sent for request #' . $followUpRequestId);
            apiRespond(502, ['error' => 'Follow-up request #' . $followUpRequestId . ' was submitted for review, but the email could not be sent. Please contact the office.']);
        }

    apiRespond(200, [
        'ok' => true,
        'request_id' => $followUpRequestId,
        'message' => 'A new pending request was sent to the office for review. Its private link has been emailed to you.',
    ]);
}

apiRespond(404, ['error' => 'Unknown API action.']);