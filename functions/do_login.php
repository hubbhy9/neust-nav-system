<?php
require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/login_auth.php';

$auth = new login_auth($db);

if (isset($_POST['login'])) {
    $auth->handleLogin($_POST['email'], $_POST['pass_word']);
}