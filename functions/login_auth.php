<?php
require_once __DIR__ . '/../libraries/db.php';

class login_auth
{
    private $con;

    public function __construct($db)
    {
        $this->con = $db;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function handleLogin($email, $password)
    {
        $stmt = $this->con->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['pass_word'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];

            if ($user['role_id'] != 0) {
                $_SESSION['office_id'] = $user['office_id'];

                header("Location: ../dashboard.php");
                exit;
            }

            header("Location: ../dashboard.php");
            exit;

        } else {
            echo "<script>
alert('Invalid Credentials');
window.location.href = '../login.php';
</script>";
            exit;
        }
    }
}