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

require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/audit.php';
if (!empty($_SESSION['id']) || !empty($_SESSION['is_guest'])) {
    auditLogSafely($db, [
        'action' => 'LOGOUT',
        'module' => 'Authentication',
        'description' => 'User ended the session.',
        'status' => 'success',
    ]);
}
session_unset();
session_destroy();
echo "<script>
                    alert('Logout Successful!');
                    window.location.href='../index.php';
                </script>";
exit;