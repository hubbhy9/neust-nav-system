<?php
include_once __DIR__ . '/../libraries/include.php';

class edit
{

    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $suffix = '';
    public string $email = '';
    public string $office_id = '';
    public string $role_id = '';
    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    public function getPost(): void
    {
        if (!empty($_POST)) {
            $this->first_name  = $_POST['first_name'] ?? '';
            $this->middle_name = $_POST['middle_name'] ?? '';
            $this->last_name   = $_POST['last_name'] ?? '';
            $this->suffix      = $_POST['suffix'] ?? '';
            $this->email       = $_POST['email'] ?? '';
            $this->office_id   = $_POST['office_id'] ?? '';
            $this->role_id     = $_POST['role_id'] ?? ''; 
        }
    }

    public function update($user_id) {

        $this->getPost();
        
        $sql = 'UPDATE users SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                suffix = ?, 
                email = ?, 
                role_id = ?, 
                office_id = ? 
                WHERE user_id = ?';

        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
            $this->email,
            $this->role_id,
            $this->office_id,
            $user_id
        ]);
    }
}

if (isset($_POST['update'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        $editor = new edit($db);
        $editor->update($user_id);
        echo "<script>
                alert('Successfully Updated'); 
                window.location.href='../../dashboard.php';
              </script>";
        exit;
    }
} else {
    header("Location: ../../dashboard.php");
    exit;
}
?>