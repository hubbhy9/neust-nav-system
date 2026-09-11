<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../assets/deparments/departments.php';
include_once __DIR__ . '/../assets/roles/roles.php';
include_once __DIR__ . '/../functions/display.php';


$pusher = new Pusher\Pusher(
    "ee626886154497d0f929",
    "1cb11747ccdb00e4c135",
    "2148160",
    [
        'cluster' => 'ap1',
        'useTLS' => true
    ]
);
?>



<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Pusher JS Library -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<!-- Data Tables -->
<link href="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.7/datatables.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.7/datatables.min.js"></script>

<!-- Font Awesome 6-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- CSS HardCode -->
<link rel="stylesheet" href="config/css/css.css">