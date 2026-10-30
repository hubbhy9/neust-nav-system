<?php
require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/audit.php';

class login_auth
{
    private $con;

    public function __construct($db)
    {
        $this->con = $db;

        $sessionSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
        if (!is_dir($sessionSavePath)) {
            @mkdir($sessionSavePath, 0777, true);
        }
        if (!is_dir($sessionSavePath) || !is_writable($sessionSavePath)) {
            $sessionSavePath = sys_get_temp_dir();
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_save_path($sessionSavePath);
            session_start();
        }
    }

    public function handleLogin($email, $password)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            unset($_SESSION['is_guest'], $_SESSION['guest_login_csrf']);
            session_regenerate_id(true);

            $_SESSION['id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];

            auditLogSafely($this->con, [
                'action' => 'LOGIN',
                'module' => 'Authentication',
                'target_type' => 'user',
                'target_id' => (int) $user['id'],
                'target_name' => (string) $user['email'],
                'description' => 'User signed in successfully.',
                'status' => 'success',
            ]);

            // No office/department in new schema; just redirect
            header("Location: ../dashboard.php");
            exit;

        } else {
            auditLogSafely($this->con, [
                'actor_name' => mb_substr(trim((string) $email), 0, 190, 'UTF-8') ?: 'Unknown',
                'actor_role' => 'anonymous',
                'action' => 'LOGIN',
                'module' => 'Authentication',
                'target_type' => 'user',
                'target_name' => mb_substr(trim((string) $email), 0, 255, 'UTF-8'),
                'description' => 'Login attempt failed.',
                'status' => 'failure',
            ]);
            echo "<script>
alert('Invalid Credentials');
window.location.href = '../index.php';
</script>";
            exit;
        }
    }
}