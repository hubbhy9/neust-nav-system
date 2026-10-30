<?php
$sessionSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
if (!is_dir($sessionSavePath)) {
    @mkdir($sessionSavePath, 0777, true);
}
if (!is_dir($sessionSavePath) || !is_writable($sessionSavePath)) {
    $sessionSavePath = sys_get_temp_dir();
}

if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_save_path($sessionSavePath);
    session_start();
}

if (!empty($_SESSION['is_guest']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(403);
    exit('Guest access is read-only.');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
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

<script>
    (function () {
        function hideProjectNotifications() {
            document.querySelectorAll('.feedback-toast, .alert-success, .alert-danger, .alert-warning, .alert-info').forEach(function (element) {
                if (!element || element.dataset.dismissed === 'true') return;
                element.dataset.dismissed = 'true';

                element.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                element.style.opacity = '0';
                element.style.transform = 'translateY(-6px)';

                setTimeout(function () {
                    if (element && element.parentNode) {
                        element.remove();
                    }
                }, 220);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.feedback-toast, .alert').forEach(function (element) {
                if (element.classList.contains('alert-dismissible') || element.classList.contains('feedback-toast')) {
                    var closeButton = element.querySelector('button[aria-label="Close notification"]');
                    if (closeButton) {
                        closeButton.addEventListener('click', function () {
                            hideProjectNotifications();
                        });
                    }
                }
            });

            window.setTimeout(function () {
                hideProjectNotifications();
            }, 7000);
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                hideProjectNotifications();
            }
        });

        window.addEventListener('pagehide', hideProjectNotifications);

        document.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof Element)) return;

            const navTrigger = target.closest('.nav-link, .nav-item a, a[href]');
            if (!navTrigger) return;

            if (navTrigger.closest('.feedback-toast')) return;

            setTimeout(hideProjectNotifications, 80);
        });
    })();
</script>