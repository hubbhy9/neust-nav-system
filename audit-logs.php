<?php
declare(strict_types=1);

$sessionSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
if (!is_dir($sessionSavePath)) {
    @mkdir($sessionSavePath, 0777, true);
}
if (!is_dir($sessionSavePath) || !is_writable($sessionSavePath)) {
    $sessionSavePath = sys_get_temp_dir();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_save_path($sessionSavePath);
    session_start();
}

require_once __DIR__ . '/libraries/db.php';
require_once __DIR__ . '/functions/audit.php';

if (!canViewAuditLogs($db)) {
    auditLogSafely($db, [
        'action' => 'ACCESS_DENIED',
        'module' => 'Audit Logs',
        'description' => 'Denied access to audit logs because the session is not an administrator.',
        'status' => 'denied',
    ]);
    http_response_code(403);
    exit('Administrator access is required.');
}

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$perPage = 50;
$page = max(1, min(1000000, (int) ($_GET['page'] ?? 1)));
$search = trim((string) ($_GET['q'] ?? ''));
$search = mb_substr($search, 0, 120, 'UTF-8');
$filters = [
    'from' => trim((string) ($_GET['from'] ?? '')),
    'to' => trim((string) ($_GET['to'] ?? '')),
    'role' => trim((string) ($_GET['role'] ?? '')),
    'action' => strtoupper(trim((string) ($_GET['action'] ?? ''))),
    'module' => trim((string) ($_GET['module'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'user_id' => trim((string) ($_GET['user_id'] ?? '')),
    'target' => trim((string) ($_GET['target'] ?? '')),
];
foreach (['from', 'to'] as $dateFilter) {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $filters[$dateFilter], $dateParts)
        || !checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])) {
        $filters[$dateFilter] = '';
    }
}
if (!in_array($filters['role'], ['', 'admin', 'user', 'guest', 'anonymous'], true)) $filters['role'] = '';
if (!in_array($filters['status'], ['', 'success', 'failure', 'denied'], true)) $filters['status'] = '';
if (!in_array($filters['action'], ['', 'CREATE', 'READ', 'NAVIGATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'ACCESS_DENIED', 'REQUEST_LINK', 'PASSWORD_RECOVERY', 'PASSWORD_CHANGE'], true)) $filters['action'] = '';
if ($filters['module'] !== '' && !preg_match('/^[A-Za-z0-9 _-]{1,80}$/', $filters['module'])) $filters['module'] = '';
if ($filters['user_id'] !== '' && !ctype_digit($filters['user_id'])) $filters['user_id'] = '';
$filters['target'] = mb_substr($filters['target'], 0, 100, 'UTF-8');

$where = [];
$params = [];
if ($filters['from'] !== '') { $where[] = 'created_at >= ?'; $params[] = $filters['from'] . ' 00:00:00'; }
if ($filters['to'] !== '') { $where[] = 'created_at < DATE_ADD(?, INTERVAL 1 DAY)'; $params[] = $filters['to']; }
if ($filters['role'] !== '') { $where[] = 'actor_role = ?'; $params[] = $filters['role']; }
if ($filters['action'] !== '') { $where[] = 'action = ?'; $params[] = $filters['action']; }
if ($filters['module'] !== '') { $where[] = 'module = ?'; $params[] = $filters['module']; }
if ($filters['status'] !== '') { $where[] = 'status = ?'; $params[] = $filters['status']; }
if ($filters['user_id'] !== '') { $where[] = 'user_id = ?'; $params[] = (int) $filters['user_id']; }
if ($filters['target'] !== '') { $where[] = '(target_id = ? OR target_name LIKE ?)'; $params[] = $filters['target']; $params[] = '%' . $filters['target'] . '%'; }
if ($search !== '') {
    $where[] = '(actor_name LIKE ? OR description LIKE ? OR target_name LIKE ? OR module LIKE ? OR action LIKE ?)';
    for ($index = 0; $index < 5; $index++) $params[] = '%' . $search . '%';
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$countStmt = $db->prepare('SELECT COUNT(*) FROM audit_logs' . $whereSql);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$filterQueryParams = $_GET;
unset($filterQueryParams['page'], $filterQueryParams['detail'], $filterQueryParams['export']);
$filteredExportUrl = 'audit-logs.php?' . http_build_query($filterQueryParams + ['export' => 'txt']);
$actionLabel = static function (string $action): string {
    return [
        'CREATE' => 'Record created', 'UPDATE' => 'Record updated', 'DELETE' => 'Record deleted',
        'LOGIN' => 'Signed in', 'LOGOUT' => 'Signed out', 'ACCESS_DENIED' => 'Access denied',
        'REQUEST_LINK' => 'Replacement link requested', 'PASSWORD_RECOVERY' => 'Password recovery',
        'PASSWORD_CHANGE' => 'Password changed',
    ][$action] ?? ucwords(strtolower(str_replace('_', ' ', $action)));
};

if (($_GET['export'] ?? '') === 'txt') {
    $exportStmt = $db->prepare('SELECT id, created_at, actor_name, user_id, actor_role, action, module, target_type, target_id, target_name, status, description, changed_fields, old_values, new_values, request_method, request_path, ip_address, user_agent FROM audit_logs' . $whereSql . ' ORDER BY id DESC');
    $exportStmt->execute($params);
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit-logs-' . date('Ymd-His') . '.txt"');
    header('X-Content-Type-Options: nosniff');
    $cleanLine = static fn ($value): string => trim(preg_replace('/[\\x00-\\x1F\\x7F]+/u', ' ', (string) ($value ?? '')) ?? '');
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "AUDIT EVENT #" . (int) $row['id'] . "\r\n";
        echo "Date/time: " . $cleanLine($row['created_at']) . "\r\n";
        echo "Actor: " . $cleanLine($row['actor_name']) . ($row['user_id'] !== null ? ' (#' . (int) $row['user_id'] . ')' : '') . ' [' . $cleanLine($row['actor_role']) . "]\r\n";
        echo "Action: " . $cleanLine($actionLabel((string) $row['action'])) . ' / ' . $cleanLine(ucfirst((string) $row['status'])) . "\r\n";
        echo "Module: " . $cleanLine($row['module']) . "\r\n";
        echo "Target: " . $cleanLine($row['target_type']) . ' #' . $cleanLine($row['target_id']) . ' ' . $cleanLine($row['target_name']) . "\r\n";
        echo "Description: " . $cleanLine($row['description']) . "\r\n";
        echo "Changed fields: " . $cleanLine($row['changed_fields']) . "\r\n";
        echo "Previous values: " . $cleanLine($row['old_values']) . "\r\n";
        echo "New values: " . $cleanLine($row['new_values']) . "\r\n";
        echo "Request: " . $cleanLine($row['request_method']) . ' ' . $cleanLine($row['request_path']) . "\r\n";
        echo "IP: " . $cleanLine($row['ip_address']) . "\r\n";
        echo "User agent: " . $cleanLine($row['user_agent']) . "\r\n";
        echo str_repeat('-', 72) . "\r\n";
    }
    exit;
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$listStmt = $db->prepare('SELECT id, user_id, actor_name, actor_role, action, module, target_type, target_id, target_name, description, ip_address, request_method, request_path, status, created_at FROM audit_logs' . $whereSql . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
$listStmt->execute($params);
$logs = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$detail = null;
$detailId = filter_var($_GET['detail'] ?? null, FILTER_VALIDATE_INT);
if ($detailId && $detailId > 0) {
    $detailStmt = $db->prepare('SELECT * FROM audit_logs WHERE id = ? LIMIT 1');
    $detailStmt->execute([$detailId]);
    $detail = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$queryParams = $_GET;
unset($queryParams['page'], $queryParams['detail']);
$baseQuery = http_build_query($queryParams);
$baseUrl = 'audit-logs.php' . ($baseQuery !== '' ? '?' . $baseQuery . '&' : '?');
$decodeJson = static function ($value): mixed {
    if (!is_string($value) || $value === '') return null;
    $decoded = json_decode($value, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
};
$clientSummary = static function (array $detail) use ($decodeJson): array {
    $hints = $decodeJson($detail['client_hints'] ?? null);
    if (!is_array($hints)) $hints = [];
    $userAgent = (string) ($detail['user_agent'] ?? '');
    $brands = (string) ($hints['brands'] ?? '');
    $browser = '';
    foreach ([
        '/Microsoft Edge|"?Edge"?/i' => 'Microsoft Edge',
        '/Opera|"?OPR"?/i' => 'Opera',
        '/Brave/i' => 'Brave',
        '/Firefox/i' => 'Firefox',
        '/Google Chrome/i' => 'Chrome-compatible browser',
        '/Chromium/i' => 'Chromium-based browser',
    ] as $pattern => $label) {
        if ($brands !== '' && preg_match($pattern, $brands)) { $browser = $label; break; }
    }
    if ($browser === '') {
        if (preg_match('/Edg(?:e|A|iOS)?\/([\d.]+)/i', $userAgent, $match)) $browser = 'Microsoft Edge ' . $match[1];
        elseif (preg_match('/OPR\/([\d.]+)/i', $userAgent, $match)) $browser = 'Opera ' . $match[1];
        elseif (preg_match('/SamsungBrowser\/([\d.]+)/i', $userAgent, $match)) $browser = 'Samsung Internet ' . $match[1];
        elseif (preg_match('/(?:Firefox|FxiOS)\/([\d.]+)/i', $userAgent, $match)) $browser = 'Firefox ' . $match[1];
        elseif (preg_match('/(?:Chrome|CriOS)\/([\d.]+)/i', $userAgent, $match)) $browser = 'Chrome-compatible browser ' . $match[1];
        elseif (preg_match('/Version\/([\d.]+).*Safari/i', $userAgent, $match)) $browser = 'Safari ' . $match[1];
        elseif (preg_match('/Electron\/([\d.]+)/i', $userAgent, $match)) $browser = 'Electron app ' . $match[1];
        else $browser = $userAgent !== '' ? 'Browser not identified' : 'Not recorded';
    }

    $platform = trim((string) ($hints['platform'] ?? ''));
    if ($platform === '') {
        if (stripos($userAgent, 'Windows NT') !== false) $platform = 'Windows';
        elseif (stripos($userAgent, 'Android') !== false) $platform = 'Android';
        elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) $platform = 'iOS / iPadOS';
        elseif (stripos($userAgent, 'Mac OS X') !== false) $platform = 'macOS';
        elseif (stripos($userAgent, 'Linux') !== false) $platform = 'Linux';
        else $platform = 'Operating system not identified';
    }

    $mobileHint = strtolower(trim((string) ($hints['mobile'] ?? '')));
    if ($mobileHint === '?1' || stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'iPhone') !== false) {
        $device = 'Mobile';
    } elseif ($mobileHint === '?0') {
        $device = 'Desktop';
    } elseif (stripos($userAgent, 'iPad') !== false || stripos($userAgent, 'Tablet') !== false) {
        $device = 'Tablet';
    } elseif ($userAgent !== '') {
        $device = 'Desktop or tablet';
    } else {
        $device = 'Device type not identified';
    }
    $model = trim((string) ($hints['model'] ?? ''));
    if ($model !== '' && $model !== '""') $device .= ' · ' . trim($model, '" ');

    return ['browser' => $browser, 'platform' => $platform, 'device' => $device, 'client_hints' => $hints];
};
$formatValue = static function ($value) use ($escape): string {
    if (is_array($value) || is_object($value)) return $escape(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    if ($value === null || $value === '') return '<span class="muted">Not recorded</span>';
    return nl2br($escape((string) $value));
};
$actionLabel = static function (string $action): string {
    return [
        'CREATE' => 'Record created', 'UPDATE' => 'Record updated', 'DELETE' => 'Record deleted',
        'LOGIN' => 'Signed in', 'LOGOUT' => 'Signed out', 'ACCESS_DENIED' => 'Access denied',
        'REQUEST_LINK' => 'Replacement link requested', 'PASSWORD_RECOVERY' => 'Password recovery',
        'PASSWORD_CHANGE' => 'Password changed',
    ][$action] ?? ucwords(strtolower(str_replace('_', ' ', $action)));
};
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Audit Logs | NEUST Smart Campus</title>
<style>
:root{color-scheme:light;--ink:#112548;--muted:#61718a;--line:#dce4ef;--blue:#14369c;--surface:#f3f7fc}*{box-sizing:border-box}body{margin:0;background:var(--surface);color:var(--ink);font:14px Arial,sans-serif}main{max-width:1500px;margin:auto;padding:22px 18px 34px}h1{margin:0;font-size:1.35rem}p{margin:5px 0;color:var(--muted)}.toolbar{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:9px;margin:18px 0 12px;padding:12px;border:1px solid var(--line);border-radius:8px;background:#fff}.toolbar input,.toolbar select{width:100%;min-height:36px;padding:7px 9px;border:1px solid var(--line);border-radius:5px;background:white;color:var(--ink);font:inherit}.toolbar .search{grid-column:span 2}.toolbar button,.pagination a,.view-link{display:inline-flex;align-items:center;justify-content:center;min-height:34px;padding:7px 10px;border:1px solid var(--blue);border-radius:5px;background:var(--blue);color:#fff;font-weight:700;text-decoration:none;cursor:pointer}.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:8px;background:#fff}table{width:100%;border-collapse:collapse;min-width:1000px}th,td{padding:10px 11px;border-bottom:1px solid #edf0f5;text-align:left;vertical-align:top}th{position:sticky;top:0;background:#f8faff;color:#51617b;font-size:.72rem;text-transform:uppercase}td{font-size:.8rem}td .muted,.muted{color:var(--muted)}.audit-zebra tbody tr:nth-child(odd){background:#ffffff}.audit-zebra tbody tr:nth-child(even){background:#f3f4f6}.badge{display:inline-flex;padding:3px 7px;border-radius:999px;background:#edf3ff;color:#1947b0;font-size:.67rem;font-weight:800;text-transform:uppercase}.badge.success{background:#e8f8ed;color:#205b35}.badge.failure{background:#fff3d1;color:#805800}.badge.denied{background:#fff0f0;color:#9d1c2b}.pagination{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:12px}.pagination .links{display:flex;gap:6px}.pagination a.disabled{pointer-events:none;opacity:.45}.empty{padding:25px;text-align:center;color:var(--muted)}dialog{width:min(1180px,calc(100% - 28px));max-height:92vh;overflow:auto;border:0;border-radius:20px;padding:0;background:#f5f8ff;box-shadow:0 24px 72px rgba(8,25,63,.34)}dialog::backdrop{background:rgba(7,18,49,.78);backdrop-filter:blur(3px)}.detail-head{position:sticky;top:0;z-index:3;display:flex;justify-content:space-between;align-items:center;gap:16px;min-height:68px;padding:16px 22px;background:linear-gradient(135deg,#14369c 0%,#07184f 100%);color:#fff}.detail-head h2{margin:0;font-size:1.15rem;font-weight:800}.close{display:grid;place-items:center;width:38px;height:38px;border:1px solid rgba(255,255,255,.28);border-radius:9px;background:rgba(255,255,255,.1);color:#fff;font-size:1.45rem;line-height:1;cursor:pointer}.close:hover{background:rgba(255,255,255,.2)}.detail-body{padding:22px 24px 26px;background:linear-gradient(180deg,#f4f7ff 0%,#fff 100%)}.audit-detail-card{padding:18px;background:rgba(255,255,255,.92);border:1px solid rgba(20,54,156,.09);border-radius:16px;box-shadow:0 12px 28px rgba(9,24,57,.07)}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{padding:13px 14px;border:1px solid rgba(20,54,156,.15);border-radius:11px;background:#f7f9ff;min-width:0;overflow-wrap:anywhere}.field strong{display:block;margin-bottom:7px;color:#64748b;font-size:.69rem;letter-spacing:.06em;text-transform:uppercase}.field .field-value{color:#112548;font-size:.91rem;font-weight:600;line-height:1.45;overflow-wrap:anywhere}.field.description{grid-column:1/-1;background:#fffaf0;border-color:rgba(255,206,0,.3)}.field.request-meta{background:#f2f7ff}.field.user-agent{font-size:.84rem}.comparison{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:20px}.comparison section{min-width:0;padding:15px;border:1px solid rgba(20,54,156,.13);border-radius:13px;background:#f7f9ff}.comparison h3{margin:0 0 11px;color:#64748b;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase}.comparison pre{max-height:330px;overflow:auto;margin:0;padding:13px;border:1px solid rgba(15,23,42,.1);border-radius:9px;background:#fff;color:#172b4d;white-space:pre-wrap;overflow-wrap:anywhere;font:13px/1.55 Consolas,monospace}.comparison .not-recorded{color:#8190a5;font-family:inherit;font-style:italic}@media(max-width:760px){main{padding:14px 10px}.toolbar{grid-template-columns:repeat(2,minmax(0,1fr))}.toolbar .search{grid-column:span 2}.detail-head{min-height:60px;padding:13px 16px}.detail-body{padding:13px}.audit-detail-card{padding:12px}.grid,.comparison{grid-template-columns:1fr;gap:9px}.field.description{grid-column:auto}.comparison{margin-top:14px}}
.client-summary{display:flex;flex-wrap:wrap;gap:7px}.client-summary span{display:inline-flex;align-items:center;min-height:30px;padding:5px 9px;border:1px solid rgba(20,54,156,.13);border-radius:7px;background:#fff;color:#112548;font-weight:700}.raw-agent{margin-top:10px}.raw-agent summary{width:max-content;color:#1947b0;font-size:.77rem;font-weight:700;cursor:pointer}.raw-agent>div{display:grid;gap:5px;margin-top:8px}.raw-agent strong{color:#64748b;font-size:.68rem;text-transform:uppercase}.raw-agent code{display:block;padding:8px 9px;border:1px solid var(--line);border-radius:6px;background:#fff;color:#52627a;font:11px/1.45 Consolas,monospace;overflow-wrap:anywhere;white-space:pre-wrap}
</style>
<style>
@media (max-width: 760px) {
    main { padding: 16px 10px 24px; }
    h1 { font-size: 1.15rem; }
    .toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin: 12px 0 10px; padding: 10px; }
    .toolbar .search { grid-column: 1 / -1; }
    .toolbar input, .toolbar select, .toolbar button, .toolbar .view-link { min-width: 0; min-height: 42px; }
    .toolbar button, .toolbar .view-link { width: 100%; padding-inline: 6px; font-size: .78rem; }
    .pagination { align-items: flex-start; flex-direction: column; }
    .pagination .links { width: 100%; }
    .pagination .links a { flex: 1; }
}
</style>
</head>
<body>
<main>
<header><h1>Activity History</h1><p>Review account, document, request, feedback, and settings activity. Records are append-only.</p></header>
<form class="toolbar" method="get" action="audit-logs.php">
<input class="search" type="search" name="q" value="<?= $escape($search); ?>" placeholder="Search actor, description, record, module">
<input type="date" name="from" value="<?= $escape($filters['from']); ?>" aria-label="From date">
<input type="date" name="to" value="<?= $escape($filters['to']); ?>" aria-label="To date">
<select name="user_id"><option value="">All actors</option><?php $actorRows = $db->query("SELECT DISTINCT user_id, actor_name FROM audit_logs WHERE user_id IS NOT NULL ORDER BY actor_name LIMIT 500")->fetchAll(PDO::FETCH_ASSOC); foreach ($actorRows as $actor): ?><option value="<?= (int) $actor['user_id']; ?>" <?= $filters['user_id'] === (string) $actor['user_id'] ? 'selected' : ''; ?>><?= $escape($actor['actor_name']); ?> (#<?= (int) $actor['user_id']; ?>)</option><?php endforeach; ?></select>
<select name="role"><option value="">All roles</option><?php foreach (['admin','user','guest','anonymous'] as $role): ?><option value="<?= $role; ?>" <?= $filters['role'] === $role ? 'selected' : ''; ?>><?= ucfirst($role); ?></option><?php endforeach; ?></select>
<select name="action"><option value="">All actions</option><?php foreach (['CREATE','READ','NAVIGATE','UPDATE','DELETE','LOGIN','LOGOUT','ACCESS_DENIED','REQUEST_LINK','PASSWORD_RECOVERY','PASSWORD_CHANGE'] as $action): ?><option value="<?= $action; ?>" <?= $filters['action'] === $action ? 'selected' : ''; ?>><?= $action; ?></option><?php endforeach; ?></select>
<input name="module" value="<?= $escape($filters['module']); ?>" placeholder="Module">
<input name="target" value="<?= $escape($filters['target']); ?>" placeholder="Target ID or title">
<select name="status"><option value="">All statuses</option><?php foreach (['success','failure','denied'] as $status): ?><option value="<?= $status; ?>" <?= $filters['status'] === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option><?php endforeach; ?></select>
<button type="submit">Apply filters</button>
<a class="view-link" href="<?= $escape($filteredExportUrl); ?>">Download TXT</a>
</form>
<div class="table-wrap"><table class="audit-zebra"><thead><tr><th>Date / time</th><th>Actor</th><th>Role</th><th>Action</th><th>Module</th><th>Target</th><th>Status</th><th>IP</th><th></th></tr></thead><tbody>
<?php foreach ($logs as $log): ?><tr>
<td><?= $escape($log['created_at']); ?></td><td><?= $escape($log['actor_name']); ?><?php if ($log['user_id'] !== null): ?><br><span class="muted">#<?= (int) $log['user_id']; ?></span><?php endif; ?></td><td><?= $escape(ucfirst((string) $log['actor_role'])); ?></td><td><?= $escape($actionLabel((string) $log['action'])); ?></td><td><?= $escape($log['module']); ?></td><td><?= $escape(trim((string) $log['target_name']) ?: (string) $log['target_type']); ?><?php if ($log['target_id'] !== null): ?><br><span class="muted">#<?= $escape($log['target_id']); ?></span><?php endif; ?></td><td><span class="badge <?= $escape($log['status']); ?>"><?= $escape(ucfirst((string) $log['status'])); ?></span></td><td><?= $escape($log['ip_address'] ?? ''); ?></td><td><a class="view-link" href="<?= $escape($baseUrl . http_build_query(['detail' => (int) $log['id']])); ?>">Details</a></td>
</tr><?php endforeach; ?>
<?php if ($logs === []): ?><tr><td class="empty" colspan="9">No audit events match these filters.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="pagination"><span><?= $totalRows; ?> events · Page <?= $page; ?> of <?= $totalPages; ?></span><div class="links"><a class="<?= $page <= 1 ? 'disabled' : ''; ?>" href="<?= $escape($baseUrl . http_build_query(['page' => max(1, $page - 1)])); ?>">Previous</a><a class="<?= $page >= $totalPages ? 'disabled' : ''; ?>" href="<?= $escape($baseUrl . http_build_query(['page' => min($totalPages, $page + 1)])); ?>">Next</a></div></div>
</main>
<?php if ($detail): $oldValues = $decodeJson($detail['old_values']); $newValues = $decodeJson($detail['new_values']); $changedFields = $decodeJson($detail['changed_fields']); ?>
<?php $clientInfo = $clientSummary($detail); ?>
<dialog aria-labelledby="auditDetailTitle"><div class="detail-head"><h2 id="auditDetailTitle">Audit event #<?= (int) $detail['id']; ?></h2><button class="close" type="button" aria-label="Close details" onclick="this.closest('dialog').close()">&times;</button></div><div class="detail-body"><div class="audit-detail-card">
<div class="grid">
<div class="field"><strong>Actor</strong><div class="field-value"><?= $escape($detail['actor_name']); ?> · <?= $escape(ucfirst((string) $detail['actor_role'])); ?><?php if ($detail['user_id'] !== null): ?> (#<?= (int) $detail['user_id']; ?>)<?php endif; ?></div></div>
<div class="field"><strong>Date and time</strong><div class="field-value"><?= $escape($detail['created_at']); ?></div></div>
<div class="field"><strong>Action and result</strong><div class="field-value"><?= $escape($actionLabel((string) $detail['action'])); ?> · <?= $escape(ucfirst((string) $detail['status'])); ?></div></div>
<div class="field"><strong>Module and target</strong><div class="field-value"><?= $escape($detail['module']); ?> · <?= $escape(trim((string) $detail['target_name']) ?: (string) $detail['target_type']); ?><?php if ($detail['target_id'] !== null): ?> (#<?= $escape($detail['target_id']); ?>)<?php endif; ?></div></div>
<div class="field description"><strong>What happened</strong><div class="field-value"><?= $escape($detail['description']); ?></div></div>
<div class="field request-meta"><strong>Request</strong><div class="field-value"><?= $escape($detail['request_method']); ?> <?= $escape($detail['request_path']); ?></div></div>
<div class="field"><strong>Changed fields</strong><div class="field-value"><?= $formatValue(is_array($changedFields) ? implode(', ', $changedFields) : $changedFields); ?></div></div>
<div class="field"><strong>IP address</strong><div class="field-value"><?= $escape($detail['ip_address'] ?? 'Not recorded'); ?></div></div>
<div class="field user-agent"><strong>Browser / device (reported)</strong><div class="client-summary"><span><?= $escape($clientInfo['browser']); ?></span><span><?= $escape($clientInfo['platform']); ?></span><span><?= $escape($clientInfo['device']); ?></span></div><details class="raw-agent"><summary>Technical details</summary><div><strong>Reported user-agent</strong><code><?= $escape($detail['user_agent'] ?? 'Not recorded'); ?></code><?php if ($clientInfo['client_hints'] !== []): ?><strong>Browser client hints</strong><code><?= $escape(json_encode($clientInfo['client_hints'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)); ?></code><?php endif; ?></div></details></div>
</div>
<div class="comparison"><section><h3>Previous values</h3><pre><?= $oldValues === null ? '<span class="not-recorded">Not recorded</span>' : $formatValue($oldValues); ?></pre></section><section><h3>New values</h3><pre><?= $newValues === null ? '<span class="not-recorded">Not recorded</span>' : $formatValue($newValues); ?></pre></section></div>
</div></div></dialog><script>document.querySelector('dialog')?.showModal();</script>
<?php endif; ?>
</body></html>
