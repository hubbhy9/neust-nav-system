<?php
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
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'error' => 'Use POST.']);
}

if (empty($_SESSION['id']) || (int) ($_SESSION['role_id'] ?? 0) !== 1 || !empty($_SESSION['is_guest'])) {
    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/../functions/audit.php';
    auditLogSafely($db, [
        'action' => 'ACCESS_DENIED',
        'module' => 'Map Configuration',
        'description' => 'Denied attempt to save campus map configuration.',
        'status' => 'denied',
    ]);
    respond(403, ['ok' => false, 'error' => 'Administrator access is required.']);
}

$origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($origin !== '') {
    $originHost = parse_url($origin, PHP_URL_HOST);
    $serverHost = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (!$originHost || !$serverHost || strcasecmp($originHost, $serverHost) !== 0) {
        respond(403, ['ok' => false, 'error' => 'Cross-site save blocked.']);
    }
}

$raw = file_get_contents('php://input');
if (!is_string($raw) || $raw === '') {
    respond(400, ['ok' => false, 'error' => 'Empty map layout.']);
}
if (strlen($raw) > 20 * 1024 * 1024) {
    respond(413, ['ok' => false, 'error' => 'Map layout is too large.']);
}

$layout = json_decode($raw, true);
if (!is_array($layout) || !isset($layout['nodes']) || !is_array($layout['nodes']) || !isset($layout['edges']) || !is_array($layout['edges'])) {
    respond(400, ['ok' => false, 'error' => 'Map layout must include nodes and edges.']);
}
if ($layout['nodes'] === []) {
    respond(400, ['ok' => false, 'error' => 'Cannot save an empty map.']);
}

$target = __DIR__ . '/map.json';
$backup = __DIR__ . '/map.json.bak';
$temporary = tempnam(__DIR__, 'map_');
if ($temporary === false || file_put_contents($temporary, $raw, LOCK_EX) === false) {
    if ($temporary !== false) {
        @unlink($temporary);
    }
    respond(500, ['ok' => false, 'error' => 'Could not write to the map folder.']);
}

if (is_file($target) && !@copy($target, $backup)) {
    @unlink($temporary);
    respond(500, ['ok' => false, 'error' => 'Could not create a backup of the current map.']);
}

if (!@rename($temporary, $target)) {
    @unlink($temporary);
    respond(500, ['ok' => false, 'error' => 'Could not replace map.json. Check folder permissions.']);
}

require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/../functions/audit.php';
auditLogSafely($db, [
    'action' => 'UPDATE',
    'module' => 'Map Configuration',
    'target_type' => 'map_layout',
    'target_id' => 'map.json',
    'target_name' => 'Campus map layout',
    'description' => 'Saved campus map layout.',
    'new_values' => [
        'node_count' => count($layout['nodes']),
        'edge_count' => count($layout['edges']),
        'building_count' => is_array($layout['buildings'] ?? null) ? count($layout['buildings']) : 0,
    ],
    'status' => 'success',
]);

respond(200, ['ok' => true, 'nodes' => count($layout['nodes']), 'savedAt' => gmdate('c')]);