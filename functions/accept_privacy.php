<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$accepted = isset($_POST['accepted']) && $_POST['accepted'] === '1';
$dontShowAgain = isset($_POST['dont_show_again']) && $_POST['dont_show_again'] === '1';

if (!$accepted) {
    $_SESSION['privacy_accepted'] = 0;
    if (isset($_COOKIE['neustPrivacyConsent'])) {
        setcookie('neustPrivacyConsent', '', time() - 3600, '/');
    }
    echo json_encode(['success' => false, 'message' => 'Consent not accepted']);
    exit;
}

$_SESSION['privacy_accepted'] = 1;

if ($dontShowAgain) {
    setcookie('neustPrivacyConsent', '1', time() + (60 * 60 * 24 * 365), '/');
} else {
    unset($_SESSION['privacy_dont_show']);
    if (isset($_COOKIE['neustPrivacyConsent'])) {
        setcookie('neustPrivacyConsent', '', time() - 3600, '/');
    }
}

echo json_encode(['success' => true, 'message' => 'Consent accepted']);
