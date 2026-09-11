<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
// Include your DB or config where the $pusher object is initialized
include __DIR__ . '/../libraries/include.php'; 

if (isset($_SESSION['user_id'])) {
    // This helper function from the Pusher SDK handles the handshake
    echo $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
} else {
    header('', true, 403);
    echo "Forbidden";
}