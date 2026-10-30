<?php
declare(strict_types=1);

$sessionSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
if (!is_dir($sessionSavePath)) {
    @mkdir($sessionSavePath, 0777, true);
}
if (!is_dir($sessionSavePath) || !is_writable($sessionSavePath)) {
    $sessionSavePath = sys_get_temp_dir();
}
if (session_status() === PHP_SESSION_NONE) {
    session_save_path($sessionSavePath);
    session_start();
}
require_once __DIR__ . '/libraries/db.php';
require_once __DIR__ . '/functions/mailer.php';
require_once __DIR__ . '/functions/audit.php';

if (empty($_SESSION['id']) || (int) ($_SESSION['role_id'] ?? 0) !== 1 || !empty($_SESSION['is_guest'])) {
  auditLogSafely($db, [
    'action' => 'ACCESS_DENIED',
    'module' => 'Document Requests',
    'description' => 'Denied access to administrator document request management.',
    'status' => 'denied',
  ]);
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['document_request_admin_csrf'])) {
    $_SESSION['document_request_admin_csrf'] = bin2hex(random_bytes(32));
}
$isEmbedded = (string) ($_GET['embed'] ?? '') === '1';
$redirectParams = [];
if ($isEmbedded) $redirectParams['embed'] = '1';
$redirectStatus = trim((string) ($_GET['status'] ?? 'pending'));
if (in_array($redirectStatus, ['all', 'pending', 'accepted', 'rejected'], true)) {
  $redirectParams['status'] = $redirectStatus;
}
$redirectUrl = 'document-requests.php' . ($redirectParams !== [] ? '?' . http_build_query($redirectParams) : '');
$acceptedRequestHandled = false;

if (isset($_GET['ajax']) && $_GET['ajax'] === 'summary') {
    header('Content-Type: application/json; charset=utf-8');
    $summary = $db->query("SELECT (SELECT COUNT(*) FROM document_requests WHERE status = 'pending') AS pending_count, (SELECT COALESCE(MAX(request_id), 0) FROM document_requests) AS latest_request_id, (SELECT COALESCE(DATE_FORMAT(MAX(last_access_link_requested_at), '%Y-%m-%d %H:%i:%s'), '') FROM document_requests) AS latest_link_request_at")->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'pending_count' => (int) ($summary['pending_count'] ?? 0),
        'latest_request_id' => (int) ($summary['latest_request_id'] ?? 0),
        'latest_link_request_at' => (string) ($summary['latest_link_request_at'] ?? ''),
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string) $_SESSION['document_request_admin_csrf'], $token)) {
        $_SESSION['flash_message'] = 'The page expired. Refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . $redirectUrl);
        exit;
    }

    $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
    $status = trim((string) ($_POST['status'] ?? ''));
    $note = trim((string) ($_POST['admin_note'] ?? ''));
    $isNoteSave = (string) ($_POST['save_note'] ?? '') === '1';
    $isDeleteRequest = (string) ($_POST['delete_request'] ?? '') === '1';
    if (!$requestId || (!$isNoteSave && !$isDeleteRequest && !in_array($status, ['accepted', 'rejected'], true))) {
        $_SESSION['flash_message'] = 'Choose a valid request and status.';
        $_SESSION['flash_type'] = 'warning';
    } else {
      try {
      $requestStmt = $db->prepare('SELECT request_id, fname, mname, lname, suffix, requester_name, requester_email, source, status, admin_note, request_access_origin FROM document_requests WHERE request_id = ? LIMIT 1');
      $requestStmt->execute([(int) $requestId]);
      $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
      if (!$request) {
        $_SESSION['flash_message'] = 'The selected request no longer exists.';
        $_SESSION['flash_type'] = 'warning';
      } elseif ($isDeleteRequest) {
        if (!in_array((string) $request['status'], ['accepted', 'rejected'], true)) {
          $_SESSION['flash_message'] = 'Only accepted or rejected requests can be deleted.';
          $_SESSION['flash_type'] = 'warning';
        } else {
          $db->beginTransaction();
          try {
            $delete = $db->prepare('DELETE FROM document_requests WHERE request_id = ?');
            $delete->execute([(int) $requestId]);
            auditLog($db, [
              'action' => 'DELETE',
              'module' => 'Document Requests',
              'target_type' => 'document_request',
              'target_id' => (int) $requestId,
              'target_name' => (string) $request['requester_name'],
              'description' => 'Deleted a ' . (string) $request['status'] . ' document request and its linked items and access tokens.',
              'old_values' => [
                'status' => (string) $request['status'],
                'source' => (string) $request['source'],
                'requester_email' => (string) $request['requester_email'],
              ],
              'status' => 'success',
            ]);
            $db->commit();
          } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
          }
          $_SESSION['flash_message'] = 'Request deleted.';
          $_SESSION['flash_type'] = 'success';
        }
        header('Location: ' . $redirectUrl);
        exit;
      } else {
        if ($isNoteSave) $status = (string) $request['status'];
        $statusChanged = $status !== (string) $request['status'];
        $notifyGuest = $statusChanged && in_array($status, ['accepted', 'rejected'], true);
        $accessToken = '';
        $accessExpiresAt = '';
        $accessOrigin = documentRequestAccessOrigin((string) ($request['request_access_origin'] ?? ''));
        $documentTitles = [];
        if ($notifyGuest) {
          $itemsStmt = $db->prepare('SELECT i.document_title, c.name AS classification FROM document_request_items i LEFT JOIN documents d ON d.id = i.source_document_id LEFT JOIN classifications c ON c.id = d.classification_id WHERE i.request_id = ? ORDER BY i.item_id');
          $itemsStmt->execute([(int) $requestId]);
          $requestItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
          $documentTitles = array_column($requestItems, 'document_title');
          $accessExpiresAt = documentRequestAccessExpiresAt();
          $accessToken = bin2hex(random_bytes(32));
          $db->beginTransaction();
          try {
            $update = $db->prepare('UPDATE document_requests SET status = ?, admin_note = ?, handled_by = ?, updated_at = NOW() WHERE request_id = ?');
            $update->execute([$status, $note ?: null, (int) $_SESSION['id'], (int) $requestId]);
            $db->prepare('DELETE FROM document_request_access_tokens WHERE request_id = ? AND expires_at <= NOW()')->execute([(int) $requestId]);
            $tokenInsert = $db->prepare('INSERT INTO document_request_access_tokens (request_id, token_hash, expires_at, access_origin) VALUES (?, ?, ?, ?)');
            $tokenInsert->execute([(int) $requestId, hash('sha256', $accessToken), $accessExpiresAt, $accessOrigin ?: null]);
            auditLog($db, [
              'action' => 'UPDATE',
              'module' => 'Document Requests',
              'target_type' => 'document_request',
              'target_id' => (int) $requestId,
              'target_name' => (string) $request['requester_name'],
              'description' => 'Changed request status from ' . (string) $request['status'] . ' to ' . $status . '.',
              'old_values' => ['status' => (string) $request['status']],
              'new_values' => ['status' => $status, 'admin_note' => $note ?: null],
              'status' => 'success',
            ]);
            $db->commit();
          } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
          }
        } else {
          $updatedAtExpression = $statusChanged ? 'NOW()' : 'updated_at';
          $db->beginTransaction();
          $update = $db->prepare('UPDATE document_requests SET status = ?, admin_note = ?, handled_by = ?, updated_at = ' . $updatedAtExpression . ' WHERE request_id = ?');
          $update->execute([$status, $note ?: null, (int) $_SESSION['id'], (int) $requestId]);
          if ($statusChanged || $note !== (string) ($request['admin_note'] ?? '')) {
            auditLog($db, [
              'action' => $statusChanged ? 'UPDATE' : 'UPDATE',
              'module' => 'Document Requests',
              'target_type' => 'document_request',
              'target_id' => (int) $requestId,
              'target_name' => (string) $request['requester_name'],
              'description' => 'Updated document request status or internal note.',
              'old_values' => ['status' => (string) $request['status'], 'admin_note' => (string) ($request['admin_note'] ?? '')],
              'new_values' => ['status' => $status, 'admin_note' => $note],
              'status' => 'success',
            ]);
          }
          $db->commit();
        }
        $acceptedRequestHandled = $status === 'accepted' && $status !== (string) $request['status'];

        $emailSent = null;
        if ($notifyGuest && $accessOrigin !== '') {
          $accessUrl = rtrim($accessOrigin, '/') . '/request-access?token=' . rawurlencode($accessToken);
          $requesterName = trim(implode(' ', array_filter([(string) ($request['fname'] ?? ''), (string) ($request['mname'] ?? ''), (string) ($request['lname'] ?? ''), (string) ($request['suffix'] ?? '')], static fn (string $part): bool => trim($part) !== '')));
          if ($requesterName === '') $requesterName = (string) $request['requester_name'];
          try {
            $emailSent = sendDocumentRequestEmail((string) $request['requester_email'], $requesterName, (int) $requestId, $status, $documentTitles, $accessUrl, $accessExpiresAt);
          } catch (Throwable $emailError) {
            error_log('Request status email exception for request #' . (int) $requestId . ': ' . $emailError->getMessage());
            $emailSent = false;
          }
        }
        if ($notifyGuest && !$emailSent) {
          error_log('Request status email could not be sent for request #' . (int) $requestId);
          $_SESSION['flash_message'] = 'Status saved, but the notification email failed. Check the email configuration and contact the requester.';
          $_SESSION['flash_type'] = 'warning';
        } elseif ($notifyGuest) {
          $_SESSION['flash_message'] = 'Request ' . $status . ' and notification email sent.';
          $_SESSION['flash_type'] = 'success';
        } else {
          $_SESSION['flash_message'] = 'Request details saved.';
          $_SESSION['flash_type'] = 'success';
        }
      }
      } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('Document request admin update failed: ' . $error->getMessage());
        $_SESSION['flash_message'] = 'Request update failed. No success was recorded; refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
      }
    }
    $postRedirectUrl = $redirectUrl;
    if ($acceptedRequestHandled) {
      $postRedirectParams = $redirectParams;
      $postRedirectParams['status'] = 'pending';
      $postRedirectUrl = 'document-requests.php?' . http_build_query($postRedirectParams);
    }
    header('Location: ' . $postRedirectUrl);
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? 'pending'));
if (!in_array($statusFilter, ['all', 'pending', 'accepted', 'rejected'], true)) $statusFilter = 'pending';
$query = 'SELECT request_id, request_batch_id, requester_user_id, fname, mname, lname, suffix, requester_name, requester_email, requester_phone, source, status, admin_note, created_at, updated_at, last_access_link_requested_at, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(updated_at, INTERVAL 1 HOUR)) AS hide_after_seconds FROM document_requests';
$params = [];
if ($statusFilter === 'all') {
  $query .= " WHERE status = 'pending' OR (status IN ('accepted', 'rejected') AND (updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) OR last_access_link_requested_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)))";
} elseif (in_array($statusFilter, ['accepted', 'rejected'], true)) {
    $query .= ' WHERE status = ?';
  $query .= ' AND (updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) OR last_access_link_requested_at > DATE_SUB(NOW(), INTERVAL 1 HOUR))';
  $params[] = $statusFilter;
} else {
  $query .= ' WHERE status = ?';
    $params[] = $statusFilter;
}
$query .= ' ORDER BY (status = "pending") DESC, created_at DESC LIMIT 500';
$requestsStmt = $db->prepare($query);
$requestsStmt->execute($params);
$requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
$itemsByRequest = [];
if ($requests !== []) {
    $requestIds = array_map(static fn (array $request): int => (int) $request['request_id'], $requests);
    $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
    $itemsStmt = $db->prepare("SELECT request_id, source_document_id, document_title, office_name FROM document_request_items WHERE request_id IN ($placeholders) ORDER BY item_id");
    $itemsStmt->execute($requestIds);
    foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $itemsByRequest[(int) $item['request_id']][] = $item;
    }
}
$pendingCount = (int) $db->query("SELECT COUNT(*) FROM document_requests WHERE status = 'pending'")->fetchColumn();
$latestRequestId = (int) $db->query('SELECT COALESCE(MAX(request_id), 0) FROM document_requests')->fetchColumn();
$latestLinkRequestAt = (string) $db->query("SELECT COALESCE(DATE_FORMAT(MAX(last_access_link_requested_at), '%Y-%m-%d %H:%i:%s'), '') FROM document_requests")->fetchColumn();
$flashMessage = (string) ($_SESSION['flash_message'] ?? '');
$flashType = (string) ($_SESSION['flash_type'] ?? 'success');
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Document requests | NEUST Smart Campus</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root { color-scheme: light; --ink:#112548; --muted:#61718a; --line:#dce4ef; --blue:#14369c; --surface:#f3f7fc; }
    * { box-sizing:border-box; }
    body { margin:0; background:var(--surface); color:var(--ink); font-family:Arial,sans-serif; }
    body.embedded-request-page { background:transparent; }
    body.embedded-request-page { scrollbar-width:none; }
    body.embedded-request-page::-webkit-scrollbar { display:none; }
    header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px max(16px,calc((100vw - 1180px)/2)); background:linear-gradient(135deg,#07184f,#14369c); color:#fff; }
    header a { color:#fff; text-decoration:none; font-weight:700; }
    main { width:min(1180px,100%); margin:0 auto; padding:24px 16px 40px; }
    .page-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; }
    h1 { margin:0; font-size:1.35rem; }.page-heading p { margin:4px 0 0; color:var(--muted); font-size:.9rem; }
    .summary { display:flex; align-items:center; gap:7px; padding:7px 10px; border:1px solid #f2c3c5; border-radius:7px; background:#fff5f5; color:#9d1c2b; font-size:.8rem; font-weight:800; white-space:nowrap; }
    .toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
    select,input,textarea,button { font:inherit; }
    .filter { min-height:36px; padding:6px 9px; border:1px solid var(--line); border-radius:6px; background:#fff; color:var(--ink); }
    .request-list { display:grid; gap:8px; }
    .request-row { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:12px; padding:10px 12px; border:1px solid var(--line); border-left:3px solid #d89b00; border-radius:7px; background:#fff; }
    .request-row[hidden] { display:none; }
    .request-row[data-status="accepted"] { border-left-color:#23864a; }.request-row[data-status="rejected"] { border-left-color:#b42332; }
    .request-meta { display:flex; flex-wrap:wrap; align-items:center; gap:5px 9px; color:var(--muted); font-size:.73rem; }
    .request-meta strong { color:var(--ink); font-size:.88rem; }
    .request-expiry-countdown { color:#8a4b00; font-size:.7rem; font-variant-numeric:tabular-nums; font-weight:800; white-space:nowrap; }
    .source-badge,.status-badge { display:inline-flex; align-items:center; padding:3px 7px; border-radius:999px; background:#edf3ff; color:#1947b0; font-size:.66rem; font-weight:800; text-transform:uppercase; }
    .status-badge { background:#fff4d6; color:#805800; }.request-row[data-status="accepted"] .status-badge { background:#e8f8ed; color:#205b35; }.request-row[data-status="rejected"] .status-badge { background:#fff0f0; color:#9d1c2b; }
    .requested-documents { display:grid; gap:4px; margin:7px 0 0; padding:0; list-style:none; }
    .requested-documents li { display:flex; justify-content:space-between; gap:8px; padding:5px 8px; border:1px solid #edf0f5; border-radius:5px; background:#fafcff; font-size:.76rem; }
    .requested-documents span:last-child { color:var(--muted); text-align:right; }
    .requested-documents-toggle { margin-top:7px; }
    .requested-documents-toggle summary { width:max-content; max-width:100%; color:#1947b0; font-size:.75rem; font-weight:800; cursor:pointer; }
    .requested-documents-toggle[open] summary { margin-bottom:6px; }
    .contact-line { margin:6px 0 0; overflow-wrap:anywhere; color:var(--muted); font-size:.78rem; }
    .request-manage { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:7px; min-width:210px; }
    .request-actions { display:flex; gap:5px; }
    .request-search { flex:1 1 240px; min-width:180px; max-width:420px; }
    .request-search input { width:100%; min-height:36px; padding:7px 10px; border:1px solid var(--line); border-radius:6px; background:#fff; color:var(--ink); font:inherit; font-size:.84rem; }
    .request-search input:focus { border-color:#1947b0; outline:2px solid rgba(25,71,176,.14); }
    .request-search-summary { flex:0 0 auto; color:var(--muted); font-size:.76rem; }
    .request-actions button,.save-note-button,.refresh-button { min-height:32px; padding:6px 10px; border:1px solid var(--line); border-radius:5px; background:#fff; color:var(--ink); font-size:.76rem; font-weight:800; cursor:pointer; }
    .request-actions .accept-button { border-color:#23864a; color:#20643a; }
    .request-actions .reject-button { border-color:#d7a5a9; color:#9d1c2b; }
    .request-actions .delete-button { border-color:#b42332; color:#9d1c2b; }
    .request-actions button:disabled { opacity:.5; cursor:default; }
    .request-actions button.is-loading { cursor:wait; }
    .request-submit-spinner { display:inline-block; width:13px; height:13px; margin-right:6px; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; vertical-align:-2px; animation:request-spin .7s linear infinite; }
    @keyframes request-spin { to { transform:rotate(360deg); } }
    .note-editor { flex-basis:100%; text-align:right; }
    .note-editor summary { color:var(--muted); font-size:.72rem; font-weight:700; cursor:pointer; }
    .note-editor textarea { display:block; width:min(100%,360px); min-height:54px; margin:6px 0 5px auto; padding:7px 8px; border:1px solid var(--line); border-radius:5px; color:var(--ink); background:#fff; font:inherit; font-size:.78rem; resize:vertical; }
    .save-note-button { min-height:28px; padding:4px 8px; }
    .refresh-button { min-height:34px; }
    .empty { padding:22px 14px; border:1px dashed #cbd5e1; border-radius:7px; background:#fff; color:var(--muted); text-align:center; font-size:.88rem; }
    .flash { position:fixed; top:14px; right:14px; z-index:100; display:flex; align-items:flex-start; gap:14px; width:min(420px,calc(100vw - 28px)); margin:0; padding:13px 14px; border:1px solid #b8dfc5; border-radius:8px; background:#e8f8ed; color:#205b35; box-shadow:0 12px 32px rgba(17,37,72,.2); font-size:.88rem; font-weight:700; animation:toast-in 180ms ease-out; }
    .flash.warning { border-color:#f0d38a; background:#fff8e7; color:#775500; }.flash.danger { border-color:#efc8cc; background:#fff0f0; color:#9d1c2b; }
    .flash[hidden] { display:none; }.flash button { flex:0 0 auto; padding:0; border:0; background:transparent; color:inherit; font-size:1.1rem; line-height:1; cursor:pointer; }
    @keyframes toast-in { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .decision-dialog { width:min(420px,calc(100% - 32px)); padding:0; border:1px solid var(--line); border-radius:10px; color:var(--ink); background:#fff; box-shadow:0 24px 70px rgba(17,37,72,.3); }
    .decision-dialog::backdrop { background:rgba(10,20,40,.52); backdrop-filter:blur(2px); }
    .decision-dialog[open] { animation:dialog-in 150ms ease-out; }
    @keyframes dialog-in { from { opacity:0; transform:translateY(8px) scale(.98); } to { opacity:1; transform:translateY(0) scale(1); } }
    .decision-dialog-content { padding:20px; }
    .decision-dialog h2 { margin:0; font-size:1.05rem; }
    .decision-dialog p { margin:8px 0 0; color:var(--muted); font-size:.88rem; line-height:1.5; }
    .decision-dialog-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
    .decision-dialog-actions button { min-height:36px; padding:7px 12px; border:1px solid var(--line); border-radius:6px; background:#fff; color:var(--ink); font-size:.82rem; font-weight:800; cursor:pointer; }
    .decision-dialog-actions .confirm-accept { border-color:#23864a; background:#23864a; color:#fff; }
    .decision-dialog-actions .confirm-reject { border-color:#b42332; background:#b42332; color:#fff; }
    .decision-dialog-actions .confirm-delete { border-color:#b42332; background:#b42332; color:#fff; }
    .decision-dialog-actions button:focus-visible { outline:3px solid rgba(38,99,235,.28); outline-offset:2px; }
    .new-request-toast { position:fixed; right:18px; bottom:18px; z-index:10; display:none; max-width:min(360px,calc(100vw - 36px)); padding:13px 15px; border-radius:8px; background:#112548; color:#fff; box-shadow:0 12px 30px rgba(0,0,0,.2); }
    .new-request-toast.visible { display:block; }
    body.embedded-request-page main { width:100%; padding:10px 8px 16px; }
    @media(max-width:760px) { header { align-items:flex-start; } main { padding:14px 10px 24px; }.page-heading { align-items:flex-start; }.toolbar { margin-bottom:8px; flex-wrap:wrap; }.request-search { order:3; flex-basis:100%; max-width:none; }.request-row { grid-template-columns:minmax(0,1fr); gap:8px; }.request-manage { justify-content:flex-start; min-width:0; }.note-editor { text-align:left; }.note-editor textarea { margin-left:0; } }
  </style>
</head>
<body class="<?= $isEmbedded ? 'embedded-request-page' : ''; ?>">
  <?php if (!$isEmbedded): ?><header><a href="dashboard.php"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>NEUST Smart Campus</a><span>Administrator</span></header><?php endif; ?>
  <main>
    <div class="page-heading"><div><h1>Document requests</h1><p>Review guest requests, update progress, and leave an internal note.</p></div><div class="summary"><i class="fa-solid fa-inbox" aria-hidden="true"></i><span id="pendingRequestCount"><?= $pendingCount; ?> pending</span></div></div>
    <?php if ($flashMessage !== ''): ?><div class="flash <?= $escape($flashType); ?>" id="requestActionToast" role="<?= in_array($flashType, ['danger', 'warning'], true) ? 'alert' : 'status'; ?>"><span><?= $escape($flashMessage); ?></span><button type="button" aria-label="Dismiss notification" onclick="this.parentElement.hidden=true">&times;</button></div><?php endif; ?>
    <div class="toolbar">
      <form method="get"><?php if ($isEmbedded): ?><input type="hidden" name="embed" value="1"><?php endif; ?><label for="requestStatusFilter" class="visually-hidden">Filter requests</label><select class="filter" id="requestStatusFilter" name="status" onchange="this.form.submit()"><option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>All requests</option><option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option><option value="accepted" <?= $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted (last hour)</option><option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected (last hour)</option></select></form>
      <label class="request-search"><span class="visually-hidden">Search requests</span><input id="requestSearch" type="search" placeholder="Search ID, requester, document, email, or batch" autocomplete="off"></label>
      <span class="request-search-summary" id="requestSearchSummary" role="status" aria-live="polite"></span>
      <button class="refresh-button" type="button" onclick="window.location.reload()"><i class="fa-solid fa-rotate me-1" aria-hidden="true"></i>Refresh</button>
    </div>
    <section class="request-list" aria-label="Document request queue">
      <div class="empty" id="requestQueueEmpty" <?= $requests === [] ? '' : 'hidden'; ?>>There are no requests in this view.</div>
      <?php foreach ($requests as $request): $requestId = (int) $request['request_id']; ?>
        <?php $hideAfterSeconds = in_array($request['status'], ['accepted', 'rejected'], true) ? max(1, (int) $request['hide_after_seconds']) : 0; ?>
        <?php $hideAfterLabel = sprintf('%02d:%02d:%02d', intdiv($hideAfterSeconds, 3600), intdiv($hideAfterSeconds % 3600, 60), $hideAfterSeconds % 60); ?>
        <article class="request-row" data-status="<?= $escape($request['status']); ?>" data-request-id="<?= $requestId; ?>" data-hide-after-seconds="<?= $hideAfterSeconds; ?>">
          <div>
            <div class="request-meta"><strong>Request #<?= $requestId; ?></strong><span class="status-badge"><?= $escape($request['status']); ?></span><?php if (!empty($request['last_access_link_requested_at']) && strtotime((string) $request['last_access_link_requested_at']) >= time() - 3600): ?><span class="source-badge">New link requested</span><?php endif; ?><?php if ($hideAfterSeconds > 0): ?><span class="request-expiry-countdown" data-countdown aria-live="off">Drops in <?= $hideAfterLabel; ?></span><?php endif; ?><span class="source-badge"><?= $escape($request['source']); ?></span><time datetime="<?= $escape($request['created_at']); ?>"><?= $escape(date('M j, Y, g:i A', strtotime((string) $request['created_at']))); ?></time></div>
            <?php $requesterParts = array_filter([(string) ($request['fname'] ?? ''), (string) ($request['mname'] ?? ''), (string) ($request['lname'] ?? ''), (string) ($request['suffix'] ?? '')], static fn (string $part): bool => trim($part) !== ''); $requesterDisplayName = trim(implode(' ', $requesterParts)) ?: (string) $request['requester_name']; ?>
            <p class="contact-line"><strong><?= $escape($requesterDisplayName); ?></strong> · <a href="mailto:<?= $escape($request['requester_email']); ?>"><?= $escape($request['requester_email']); ?></a><?= trim((string) ($request['requester_phone'] ?? '')) !== '' ? ' · ' . $escape($request['requester_phone']) : ''; ?></p>
            <?php $requestItems = $itemsByRequest[$requestId] ?? []; ?>
            <?php if (count($requestItems) > 3): ?><details class="requested-documents-toggle"><summary><?= count($requestItems); ?> documents requested · Show list</summary><?php endif; ?>
            <ul class="requested-documents">
              <?php foreach ($requestItems as $item): ?><li><span><?= $escape($item['document_title']); ?></span><span><?= $escape($item['office_name'] ?? 'Office not listed'); ?></span></li><?php endforeach; ?>
            </ul>
            <?php if (count($requestItems) > 3): ?></details><?php endif; ?>
            <p class="contact-line">Batch <?= $escape($request['request_batch_id']); ?></p>
          </div>
          <form class="request-manage" method="post">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['document_request_admin_csrf']); ?>">
            <input type="hidden" name="request_id" value="<?= $requestId; ?>">
            <div class="request-actions" aria-label="Request decision">
              <?php if (in_array($request['status'], ['accepted', 'rejected'], true)): ?>
                <button class="delete-button" type="submit" name="delete_request" value="1">Delete</button>
              <?php else: ?>
                <button class="accept-button" type="submit" name="status" value="accepted">Accept</button>
                <button class="reject-button" type="submit" name="status" value="rejected">Reject</button>
              <?php endif; ?>
            </div>
            <details class="note-editor">
              <summary><?= trim((string) ($request['admin_note'] ?? '')) !== '' ? 'Edit internal note' : 'Add internal note'; ?></summary>
              <textarea name="admin_note" maxlength="4000" placeholder="Add a note for administrators"><?= $escape($request['admin_note'] ?? ''); ?></textarea>
              <button class="save-note-button" type="submit" name="save_note" value="1">Save note</button>
            </details>
          </form>
        </article>
      <?php endforeach; ?>
      <div class="empty" id="requestSearchEmpty" role="status" hidden>No requests match your search.</div>
    </section>
  </main>
  <dialog class="decision-dialog" id="decisionDialog" aria-labelledby="decisionDialogTitle" aria-describedby="decisionDialogMessage">
    <div class="decision-dialog-content">
      <h2 id="decisionDialogTitle">Confirm request decision</h2>
      <p id="decisionDialogMessage"></p>
      <div class="decision-dialog-actions">
        <button type="button" id="cancelDecisionButton">Cancel</button>
        <button type="button" id="confirmDecisionButton">Confirm</button>
      </div>
    </div>
  </dialog>
  <div class="new-request-toast" id="newRequestToast" role="status" aria-live="polite">A new document request arrived. Refresh to review it.</div>
  <script>
    (function () {
      const actionToast = document.getElementById('requestActionToast');
      if (actionToast) window.setTimeout(() => { actionToast.hidden = true; }, 6500);
    })();
    (function () {
      const dialog = document.getElementById('decisionDialog');
      const message = document.getElementById('decisionDialogMessage');
      const cancelButton = document.getElementById('cancelDecisionButton');
      const confirmButton = document.getElementById('confirmDecisionButton');
      let pendingDecision = null;

      document.querySelectorAll('.request-manage').forEach(function (form) {
        form.addEventListener('submit', function (event) {
          const submitter = event.submitter;
          if (!(submitter instanceof HTMLButtonElement) || !submitter.matches('.accept-button, .reject-button, .delete-button')) return;
          const requestId = form.querySelector('input[name="request_id"]')?.value || '';
          const action = submitter.matches('.delete-button') ? 'delete' : submitter.matches('.accept-button') ? 'accept' : 'reject';
          if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
          } else {
            event.preventDefault();
            if (form.dataset.submitting === 'true' || dialog.open) return;
            pendingDecision = { form, submitter, action, requestId };
            message.textContent = action === 'delete'
              ? `Permanently delete ${form.closest('.request-row')?.dataset.status} request #${requestId} and its linked items and access tokens?`
              : `Are you sure you want to ${action} request #${requestId}?`;
            confirmButton.textContent = action === 'delete' ? 'Delete request' : action === 'accept' ? 'Accept request' : 'Reject request';
            confirmButton.className = action === 'delete' ? 'confirm-delete' : action === 'accept' ? 'confirm-accept' : 'confirm-reject';
            dialog.showModal();
            return;
          }

          if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
          }

          form.dataset.submitting = 'true';
          const actionLabel = action === 'delete' ? 'Deleting' : action === 'accept' ? 'Accepting' : 'Rejecting';
          submitter.classList.add('is-loading');
          submitter.setAttribute('aria-disabled', 'true');
          submitter.innerHTML = '<span class="request-submit-spinner" aria-hidden="true"></span>' + actionLabel + '...';
          form.querySelectorAll('button:not(.is-loading)').forEach(function (button) {
            button.disabled = true;
          });
        });
      });

      cancelButton.addEventListener('click', function () {
        pendingDecision = null;
        dialog.close();
      });

      confirmButton.addEventListener('click', function () {
        if (!pendingDecision) return;
        const { form, submitter } = pendingDecision;
        pendingDecision = null;
        form.dataset.confirmed = 'true';
        dialog.close();
        form.requestSubmit(submitter);
      });

      dialog.addEventListener('close', function () {
        if (dialog.returnValue !== 'confirm') pendingDecision = null;
      });
    })();

    (function () {
      const search = document.getElementById('requestSearch');
      const rows = Array.from(document.querySelectorAll('.request-row'));
      const emptyState = document.getElementById('requestSearchEmpty');
      const queueEmpty = document.getElementById('requestQueueEmpty');
      const summary = document.getElementById('requestSearchSummary');
      if (!search || !emptyState || !queueEmpty || !summary) return;
      const loadedAt = Date.now();
      const hideAt = new Map(rows.map(row => [row, Number(row.dataset.hideAfterSeconds) > 0 ? loadedAt + Number(row.dataset.hideAfterSeconds) * 1000 : 0]));

      function filterRequests() {
        const query = search.value.trim().toLocaleLowerCase();
        const exactId = query.match(/^#?(\d+)$/)?.[1] ?? '';
        let visibleCount = 0;
        let activeCount = 0;
        rows.forEach(function (row) {
          const expiresAt = hideAt.get(row) || 0;
          const remainingSeconds = expiresAt > 0 ? Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000)) : 0;
          const countdown = row.querySelector('[data-countdown]');
          if (countdown && remainingSeconds > 0) {
            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            countdown.textContent = `Drops in ${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
          }
          const isExpired = expiresAt > 0 && remainingSeconds === 0;
          if (isExpired) row.hidden = true;
          if (isExpired) return;
          activeCount += 1;
          const searchableText = row.textContent.toLocaleLowerCase();
          const matches = exactId !== ''
            ? row.dataset.requestId === exactId
            : searchableText.includes(query);
          row.hidden = !matches;
          if (matches) visibleCount += 1;
        });
        emptyState.hidden = query === '' || visibleCount !== 0;
        queueEmpty.hidden = query !== '' || visibleCount !== 0;
        summary.textContent = query === ''
          ? `${activeCount} request${activeCount === 1 ? '' : 's'}`
          : `${visibleCount} of ${activeCount} request${activeCount === 1 ? '' : 's'}`;
      }

      search.addEventListener('input', filterRequests);
      filterRequests();
      window.setInterval(filterRequests, 1000);
    })();
    (function () {
      let latestRequestId = <?= $latestRequestId; ?>;
      let latestLinkRequestAt = <?= json_encode($latestLinkRequestAt, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
      let pendingCount = <?= $pendingCount; ?>;
      const toast = document.getElementById('newRequestToast');
      const count = document.getElementById('pendingRequestCount');
      window.setInterval(async function () {
        try {
          const response = await fetch('document-requests.php?ajax=summary', { credentials: 'same-origin', cache: 'no-store' });
          if (!response.ok) return;
          const summary = await response.json();
          if (Number(summary.latest_request_id) > latestRequestId) toast.classList.add('visible');
          if (summary.latest_link_request_at > latestLinkRequestAt) window.location.reload();
          latestLinkRequestAt = summary.latest_link_request_at || latestLinkRequestAt;
          latestRequestId = Math.max(latestRequestId, Number(summary.latest_request_id) || 0);
          pendingCount = Number(summary.pending_count) || 0;
          count.textContent = `${pendingCount} pending`;
        } catch (_) {}
      }, 15000);
    })();
  </script>
</body>
</html>