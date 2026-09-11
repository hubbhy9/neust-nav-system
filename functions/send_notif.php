<?php
session_start();
include __DIR__ . '/../libraries/include.php';

// Security: Only Office 9 is allowed to trigger notifications
if ($_SESSION['office_id'] == 9 && isset($_POST['message'])) {
    $message = $_POST['message'];
    $target  = $_POST['target']; // 'global' or '1'-'10'
    
    $data = [
        'message' => $message,
        'from'    => $_SESSION['first_name'] . " (Admin)"
    ];

    // Select the channel based on the target selected in the dropdown
    $channelName = ($target === 'global') ? 'office-global' : 'office-' . $target;

    $pusher->trigger($channelName, 'new-announcement', $data);
}
?>