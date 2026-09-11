<?php
include __DIR__ . '\..\libraries\include.php';
class delete
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getById(int $user_id): array
    {
        $stmt = $this->con->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);   
        return $stmt->fetch() ?: [];
    }
    
    public function deleteUser(int $user_id): bool
    {   
        if ($user_id > 0) {
            $stmt = $this->con->prepare('DELETE FROM users WHERE user_id = ?');
            return $stmt->execute([$user_id]);
        }
        return false;
    }
}
$delete = new delete($db);
if (isset($_POST['delete'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        $delete->deleteUser($user_id);
        echo "<script>
                alert('Successfully Deleted'); 
                window.location.href='../dashboard.php';
              </script>";
        exit;
    }
} else {
    header("Location: ../dashboard.php");
    exit;
}
?>
