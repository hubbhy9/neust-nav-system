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

if (isset($_POST['guest_login'])) {
    $postedToken = (string) ($_POST['guest_csrf'] ?? '');
    $expectedToken = (string) ($_SESSION['guest_login_csrf'] ?? '');
    $hasPrivacyConsent = (($_SESSION['privacy_accepted'] ?? 0) == 1)
        || (($_COOKIE['neustPrivacyConsent'] ?? '') === '1');

    if ($expectedToken === '' || !hash_equals($expectedToken, $postedToken) || !$hasPrivacyConsent) {
        $_SESSION['guest_login_error'] = 'Please accept the privacy agreement, then try guest access again.';
        header('Location: ../index.php');
        exit;
    }

    require_once __DIR__ . '/../libraries/db.php';
    require_once __DIR__ . '/audit.php';
    session_regenerate_id(true);
    unset($_SESSION['id'], $_SESSION['first_name'], $_SESSION['last_name']);
    $_SESSION['role_id'] = 0;
    $_SESSION['first_name'] = 'Guest';
    $_SESSION['last_name'] = '';
    $_SESSION['is_guest'] = true;
    $_SESSION['guest_login_csrf'] = bin2hex(random_bytes(32));

    auditLogSafely($db, [
        'actor_name' => 'Guest',
        'actor_role' => 'guest',
        'action' => 'LOGIN',
        'module' => 'Authentication',
        'description' => 'Guest session started.',
        'status' => 'success',
    ]);

    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/login_auth.php';

$auth = new login_auth($db);

if (isset($_POST['login'])) {
    $auth->handleLogin($_POST['email'], $_POST['pass_word']);
}