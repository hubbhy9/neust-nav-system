<?php
session_start();
session_unset();
session_destroy();
echo "<script>
                    alert('Logout Successful!');
                    window.location.href='../index.php';
                </script>";
exit;