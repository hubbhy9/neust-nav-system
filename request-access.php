<?php
declare(strict_types=1);

require_once __DIR__ . '/libraries/db.php';
require_once __DIR__ . '/functions/mailer.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$token = strtolower(trim((string) ($_POST['token'] ?? $_GET['token'] ?? '')));
$request = null;
$items = [];
$errorMessage = '';
$resendMessage = '';
$resendError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $requestOrigin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
  if ($requestOrigin !== '') {
    $originHost = strtolower((string) parse_url($requestOrigin, PHP_URL_HOST));
    $forwardedHost = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''))[0]);
    $requestHost = strtolower(preg_replace('/:\d+$/', '', $forwardedHost !== '' ? $forwardedHost : (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '');
    if ($originHost === '' || $requestHost === '' || !hash_equals($requestHost, $originHost)) {
      http_response_code(403);
      exit('Cross-origin link renewal denied.');
    }
  }
  if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $tokenHash = hash('sha256', $token);
    $expiredRequestStmt = $db->prepare('SELECT request_id, status, requester_user_id, fname, mname, lname, suffix, requester_name, requester_email, requester_phone, source, request_access_origin, last_access_link_requested_at FROM document_requests WHERE request_access_token_hash = ? UNION ALL SELECT r.request_id, r.status, r.requester_user_id, r.fname, r.mname, r.lname, r.suffix, r.requester_name, r.requester_email, r.requester_phone, r.source, r.request_access_origin, r.last_access_link_requested_at FROM document_request_access_tokens t INNER JOIN document_requests r ON r.request_id = t.request_id WHERE t.token_hash = ? LIMIT 1');
    $expiredRequestStmt->execute([$tokenHash, $tokenHash]);
    $expiredRequest = $expiredRequestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$expiredRequest || !in_array((string) $expiredRequest['status'], ['pending', 'accepted', 'rejected'], true)) {
      $resendError = 'This request link is invalid or unavailable. Contact the office if you need help.';
    } elseif (!empty($expiredRequest['last_access_link_requested_at']) && strtotime((string) $expiredRequest['last_access_link_requested_at']) > time() - 60) {
      $resendError = 'A new link was just requested. Please wait one minute before requesting another.';
    } else {
      try {
        $itemsStmt = $db->prepare('SELECT i.source_document_id, i.document_title, i.office_name, c.name AS classification FROM document_request_items i LEFT JOIN documents d ON d.id = i.source_document_id LEFT JOIN classifications c ON c.id = d.classification_id WHERE i.request_id = ? ORDER BY i.item_id');
        $itemsStmt->execute([(int) $expiredRequest['request_id']]);
        $requestItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        $documentTitles = array_column($requestItems, 'document_title');
        $expiresAt = documentRequestAccessExpiresAt();
        $newToken = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newToken);
        $random = bin2hex(random_bytes(16));
        $batchId = substr($random, 0, 8) . '-' . substr($random, 8, 4) . '-4' . substr($random, 13, 3) . '-a' . substr($random, 17, 3) . '-' . substr($random, 20, 12);
        $preferredRenewalOrigin = $requestOrigin !== '' ? $requestOrigin : (string) ($expiredRequest['request_access_origin'] ?? '');
        $accessOrigin = documentRequestAccessOrigin($preferredRenewalOrigin, true);

        if ($accessOrigin === '') {
          $resendError = 'A new link could not be sent because the public app address is not configured. Please contact support.';
        } elseif ($requestItems === []) {
          $resendError = 'This request has no documents to submit for review. Please contact the office.';
        } else {
          $requesterName = trim(implode(' ', array_filter([(string) ($expiredRequest['fname'] ?? ''), (string) ($expiredRequest['mname'] ?? ''), (string) ($expiredRequest['lname'] ?? ''), (string) ($expiredRequest['suffix'] ?? '')], static fn (string $part): bool => trim($part) !== '')));
          if ($requesterName === '') $requesterName = (string) $expiredRequest['requester_name'];
          $followUpRequestId = 0;
          $db->beginTransaction();
          try {
            $eligible = $db->prepare('SELECT COUNT(*) FROM document_requests WHERE request_id = ? AND status = ?');
            $eligible->execute([(int) $expiredRequest['request_id'], (string) $expiredRequest['status']]);
            if ((int) $eligible->fetchColumn() !== 1) {
              throw new RuntimeException('Request status changed before follow-up submission.');
            }
            $insertRequest = $db->prepare('INSERT INTO document_requests (request_batch_id, requester_user_id, fname, mname, lname, suffix, requester_name, requester_email, requester_phone, request_access_token_hash, request_access_expires_at, request_access_origin, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insertRequest->execute([$batchId, $expiredRequest['requester_user_id'], $expiredRequest['fname'], $expiredRequest['mname'], $expiredRequest['lname'], $expiredRequest['suffix'], $requesterName, $expiredRequest['requester_email'], $expiredRequest['requester_phone'], $newTokenHash, $expiresAt, $accessOrigin, $expiredRequest['source']]);
            $followUpRequestId = (int) $db->lastInsertId();
            $copyItem = $db->prepare('INSERT INTO document_request_items (request_id, source_document_id, document_title, office_name) VALUES (?, ?, ?, ?)');
            foreach ($requestItems as $item) {
              $copyItem->execute([$followUpRequestId, $item['source_document_id'], $item['document_title'], $item['office_name']]);
            }
            $db->prepare('UPDATE document_requests SET last_access_link_requested_at = NOW() WHERE request_id = ?')->execute([(int) $expiredRequest['request_id']]);
            auditLog($db, [
              'action' => 'CREATE',
              'module' => 'Document Requests',
              'target_type' => 'document_request',
              'target_id' => $followUpRequestId,
              'target_name' => $requesterName,
              'description' => 'Created a pending follow-up request from a replacement-link request.',
              'new_values' => ['follow_up_of' => (int) $expiredRequest['request_id'], 'document_count' => count($requestItems), 'source' => $expiredRequest['source']],
              'status' => 'success',
            ]);
            auditLog($db, [
              'action' => 'REQUEST_LINK',
              'module' => 'Document Requests',
              'target_type' => 'document_request',
              'target_id' => (int) $expiredRequest['request_id'],
              'target_name' => (string) $expiredRequest['requester_name'],
              'description' => 'Created pending follow-up request #' . $followUpRequestId . ' from a replacement-link request.',
              'status' => 'success',
            ]);
            $db->commit();
          } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
          }

          $accessUrl = rtrim($accessOrigin, '/') . '/request-access?token=' . rawurlencode($newToken);
          $emailSent = false;
          try {
            $emailSent = sendDocumentRequestEmail((string) $expiredRequest['requester_email'], $requesterName, $followUpRequestId, 'pending', $documentTitles, $accessUrl, $expiresAt);
          } catch (Throwable $emailError) {
            error_log('Pending follow-up email exception for request #' . $followUpRequestId . ': ' . $emailError->getMessage());
          }

          if ($emailSent) {
            $resendMessage = 'A new pending request (#' . $followUpRequestId . ') was sent to the office for review. Its private link has been emailed to you.';
          } else {
            error_log('Pending follow-up email could not be sent for request #' . $followUpRequestId);
            $resendError = 'Follow-up request #' . $followUpRequestId . ' was submitted for review, but the email could not be sent. Please contact the office.';
          }
        }
      } catch (Throwable $resendError) {
        error_log('Expired request access renewal failed: ' . $resendError->getMessage());
        $resendError = 'The new link could not be processed. Please try again later.';
      }
    }
  }
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $errorMessage = 'This request link is invalid or has expired.';
} else {
    $tokenHash = hash('sha256', $token);
    $requestStmt = $db->prepare('SELECT request_id, status, created_at, LEAST(request_access_expires_at, DATE_ADD(created_at, INTERVAL 3 HOUR)) AS request_access_expires_at FROM document_requests WHERE request_access_token_hash = ? AND LEAST(request_access_expires_at, DATE_ADD(created_at, INTERVAL 3 HOUR)) > NOW() UNION ALL SELECT r.request_id, r.status, r.created_at, LEAST(t.expires_at, DATE_ADD(t.created_at, INTERVAL 3 HOUR)) AS request_access_expires_at FROM document_request_access_tokens t INNER JOIN document_requests r ON r.request_id = t.request_id WHERE t.token_hash = ? AND LEAST(t.expires_at, DATE_ADD(t.created_at, INTERVAL 3 HOUR)) > NOW() LIMIT 1');
    $requestStmt->execute([$tokenHash, $tokenHash]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
    if (!$request) {
        $errorMessage = 'This request link is invalid or has expired.';
    } elseif ((string) $request['status'] === 'accepted') {
        $itemsStmt = $db->prepare('SELECT i.source_document_id, i.document_title, i.office_name, d.who_may_avail, c.name AS classification FROM document_request_items i LEFT JOIN documents d ON d.id = i.source_document_id LEFT JOIN classifications c ON c.id = d.classification_id WHERE i.request_id = ? ORDER BY i.item_id');
        $itemsStmt->execute([(int) $request['request_id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $documentId = (int) $item['source_document_id'];
            $requirements = $db->prepare('SELECT requirement_name, where_to_secure FROM requirements WHERE document_id = ? ORDER BY id');
            $requirements->execute([$documentId]);
            $item['requirements'] = $requirements->fetchAll(PDO::FETCH_ASSOC);
            $steps = $db->prepare('SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible FROM document_steps WHERE document_id = ? ORDER BY step_number, id');
            $steps->execute([$documentId]);
            $item['steps'] = $steps->fetchAll(PDO::FETCH_ASSOC);
            $totals = $db->prepare('SELECT fees, time FROM `total` WHERE document_id = ? ORDER BY id');
            $totals->execute([$documentId]);
            $item['totals'] = $totals->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($item);
    }
}

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$status = strtolower((string) ($request['status'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Your requested documents | NEUST Smart Campus</title>
  <style>
    :root { color-scheme: light; --ink:#112548; --muted:#61718a; --line:#dce4ef; --blue:#14369c; }
    * { box-sizing:border-box; }
    body { margin:0; min-height:100vh; background:#f3f7fc; color:var(--ink); font-family:Arial,sans-serif; }
    main { width:min(900px,100%); margin:0 auto; padding:24px 16px 40px; }
    .heading { display:flex; align-items:center; gap:13px; margin-bottom:18px; }
    .mark { display:block; width:46px; height:46px; flex:0 0 46px; object-fit:contain; border-radius:8px; }
    .eyebrow { margin:0 0 4px; color:var(--blue); font-size:.72rem; font-weight:800; text-transform:uppercase; }
    h1 { margin:0; font-size:1.5rem; line-height:1.2; }
    .muted { margin:5px 0 0; color:var(--muted); font-size:.88rem; }
    .summary,.document,.state { padding:14px; border:1px solid var(--line); border-radius:8px; background:#fff; }
    .summary { display:flex; flex-wrap:wrap; gap:12px 24px; margin-bottom:12px; font-size:.84rem; }
    .summary span { display:grid; gap:4px; }.label { color:var(--muted); font-size:.68rem; font-weight:800; text-transform:uppercase; }
    .badge { width:max-content; padding:4px 8px; border-radius:99px; background:#fff3d1; color:#805800; font-size:.7rem; font-weight:800; text-transform:capitalize; }
    .badge.accepted { background:#e8f8ed; color:#205b35; }.badge.rejected { background:#fff0f0; color:#9d1c2b; }
    .documents { display:grid; gap:10px; }.document h2 { margin:4px 0 0; font-size:1rem; line-height:1.35; overflow-wrap:anywhere; }
    .office { color:var(--blue); font-size:.72rem; font-weight:800; }.classification { float:right; color:var(--muted); font-size:.72rem; }
    h3 { margin:13px 0 6px; font-size:.82rem; }.document ul,.document ol { display:grid; gap:5px; margin:0; padding-left:20px; font-size:.8rem; }
    .document li span,.document li small { display:block; margin-top:2px; color:var(--muted); }
    .details-footer { display:flex; flex-wrap:wrap; gap:8px 18px; margin-top:12px; padding-top:9px; border-top:1px solid #edf0f5; color:var(--muted); font-size:.78rem; }
    .details-footer strong { color:var(--ink); }.page-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:14px; color:var(--muted); font-size:.78rem; }.page-footer-actions { display:flex; align-items:center; flex-wrap:wrap; gap:8px; }.page-footer-actions .resend-form { margin:0; }
    .button { display:inline-flex; min-height:38px; align-items:center; padding:8px 12px; border:1px solid var(--blue); border-radius:6px; background:var(--blue); color:#fff; font-weight:700; text-decoration:none; }
    .button-secondary { background:#fff; color:var(--blue); }
    .button:disabled { opacity:.65; cursor:wait; }
    .resend-form { display:flex; align-items:center; flex-wrap:wrap; gap:10px; margin-top:12px; }
    .resend-message { margin:12px 0 0; padding:10px 12px; border:1px solid #b8dfc5; border-radius:7px; background:#f1fbf4; color:#205b35; font-size:.84rem; line-height:1.5; }
    .resend-error { margin:12px 0 0; padding:10px 12px; border:1px solid #efc8cc; border-radius:7px; background:#fff4f4; color:#9d1c2b; font-size:.84rem; line-height:1.5; }
    .resend-spinner { display:none; width:14px; height:14px; margin-right:7px; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; vertical-align:-2px; animation:resend-spin .7s linear infinite; }
    .is-resending .resend-spinner { display:inline-block; }
    @keyframes resend-spin { to { transform:rotate(360deg); } }
    .state h2 { margin:0 0 8px; font-size:1.1rem; }.state p { color:var(--muted); font-size:.88rem; }
    @media(max-width:560px) { main { padding:18px 12px 28px; }.summary { gap:10px 16px; }.page-footer { align-items:flex-start; flex-direction:column; } }
  </style>
</head>
<body>
<main>
  <header class="heading"><img class="mark" src="img/neust_logo.png" alt="NEUST system logo"><div><p class="eyebrow">NEUST Smart Campus</p><h1>Your requested documents</h1><p class="muted">A direct shortcut to the services in your request.</p></div></header>
  <?php if ($errorMessage !== ''): ?>
    <section class="state" role="alert"><h2>Link unavailable</h2><p><?= $escape($errorMessage); ?></p><p>You can submit these documents as a new pending request for office review. The existing request will not change.</p>
      <?php if (preg_match('/^[a-f0-9]{64}$/', $token)): ?>
        <form class="resend-form" method="post" action="request-access.php?token=<?= rawurlencode($token); ?>">
          <input type="hidden" name="token" value="<?= $escape($token); ?>">
          <button class="button" type="submit"><span class="resend-spinner" aria-hidden="true"></span><span class="resend-button-label">Submit new request for review</span></button>
        </form>
      <?php endif; ?>
      <?php if ($resendMessage !== ''): ?><p class="resend-message" role="status"><?= $escape($resendMessage); ?></p><?php endif; ?>
      <?php if ($resendError !== ''): ?><p class="resend-error" role="alert"><?= $escape($resendError); ?></p><?php endif; ?>
    </section>
  <?php else: ?>
    <section class="summary" aria-label="Request status">
      <span><b class="label">Request</b><strong>#<?= (int) $request['request_id']; ?></strong></span>
      <span><b class="label">Status</b><b class="badge <?= $escape($status); ?>"><?= $escape($status); ?></b></span>
      <span><b class="label">Submitted</b><time datetime="<?= $escape($request['created_at']); ?>"><?= $escape(date('M j, Y, g:i A', strtotime((string) $request['created_at']))); ?></time></span>
      <span><b class="label">Link expires</b><time datetime="<?= $escape($request['request_access_expires_at']); ?>"><?= $escape(date('M j, Y, g:i A', strtotime((string) $request['request_access_expires_at']))); ?></time></span>
    </section>
    <?php if ($status !== 'accepted'): ?>
      <section class="state" role="status"><h2><?= $status === 'rejected' ? 'This request was not approved' : 'Your request is awaiting approval'; ?></h2><p><?= $status === 'rejected' ? 'The requested document details are unavailable from this link.' : 'The requested documents will appear here after the request is accepted.'; ?></p></section>
    <?php else: ?>
    <section class="documents" aria-label="Approved requested services">
      <?php foreach ($items as $item): ?>
        <article class="document">
          <span class="office"><?= $escape($item['office_name'] ?: 'Campus office'); ?></span><span class="classification"><?= $escape($item['classification'] ?: 'Service'); ?></span>
          <h2><?= $escape($item['document_title']); ?></h2>
          <?php if (trim((string) ($item['who_may_avail'] ?? '')) !== ''): ?><p class="muted">For <?= $escape($item['who_may_avail']); ?></p><?php endif; ?>
          <?php if ($item['requirements'] !== []): ?><h3>Requirements</h3><ul><?php foreach ($item['requirements'] as $requirement): ?><li><strong><?= $escape($requirement['requirement_name']); ?></strong><?php if (trim((string) $requirement['where_to_secure']) !== ''): ?><span><?= $escape($requirement['where_to_secure']); ?></span><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
          <?php if ($item['steps'] !== []): ?><h3>Process steps</h3><ol><?php foreach ($item['steps'] as $step): ?><li><strong><?= $escape($step['client_step'] ?: $step['agency_action'] ?: 'Step ' . $step['step_number']); ?></strong><?php if (trim((string) $step['agency_action']) !== '' && $step['agency_action'] !== $step['client_step']): ?><span><?= $escape($step['agency_action']); ?></span><?php endif; ?><?php if (trim((string) ($step['fees'] ?? '')) !== '' && strtolower((string) $step['fees']) !== 'none'): ?><small>Fee: <?= $escape($step['fees']); ?></small><?php endif; ?></li><?php endforeach; ?></ol><?php endif; ?>
          <?php if ($item['totals'] !== []): ?><footer class="details-footer"><?php foreach ($item['totals'] as $total): ?><span>Fees: <strong><?= $escape($total['fees'] ?: 'None'); ?></strong></span><span>Time: <strong><?= $escape($total['time'] ?: 'Not specified'); ?></strong></span><?php endforeach; ?></footer><?php endif; ?>
        </article>
      <?php endforeach; ?>
      <?php if ($items === []): ?><section class="state">No document details are available for this request.</section><?php endif; ?>
    </section>
    <?php endif; ?>
    <footer class="page-footer"><span>This private link only opens the documents in this request.</span><div class="page-footer-actions"><a class="button" href="index.php?returnUrl=%2Ftabs%2Fservices">Browse all services as guest</a><form class="resend-form" method="post" action="request-access.php?token=<?= rawurlencode($token); ?>"><input type="hidden" name="token" value="<?= $escape($token); ?>"><button class="button button-secondary" type="submit"><span class="resend-spinner" aria-hidden="true"></span><span class="resend-button-label">Submit new request for review</span></button></form></div></footer>
    <?php if ($resendMessage !== ''): ?><p class="resend-message" role="status"><?= $escape($resendMessage); ?></p><?php endif; ?>
    <?php if ($resendError !== ''): ?><p class="resend-error" role="alert"><?= $escape($resendError); ?></p><?php endif; ?>
  <?php endif; ?>
</main>
<script>
  document.querySelector('.resend-form')?.addEventListener('submit', function () {
    const button = this.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;
    button.disabled = true;
    button.classList.add('is-resending');
    button.querySelector('.resend-button-label').textContent = 'Sending request...';
  });
</script>
</body>
</html>
