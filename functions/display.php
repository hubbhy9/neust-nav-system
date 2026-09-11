<?php
include __DIR__ . '\..\libraries\include.php';
class display
{
    public string $first_name;
    public string $middle_name;
    public string $last_name;
    public string $suffix;
    public string $email;
    public string $pass_word;
    public string $office_id;
    public string $created_at;

    private $con;

    private string $response;

    public function __construct($db)
    {
        $this->con = $db;
    }

    public function getAllStaff()
    {
        $stmt = $this->con->prepare('SELECT * FROM users where role_id != 0');
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return [];
        }
        return $stmt->fetchAll();
    }
    public function getAllByDept()
    {
        $stmt = $this->con->prepare('SELECT * FROM users where office_id = ?');
        $stmt->execute([$_SESSION['office_id']]);
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return [];
        }
        return $stmt->fetchAll();
    }
    public function getAllAdmin()
    {
        $stmt = $this->con->prepare('SELECT * FROM users where role_id = 2');
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return [];
        }
        return $stmt->fetchAll();
    }

    public function getAll()
    {
        $stmt = $this->con->prepare('SELECT * FROM users');
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return [];
        }
        return $stmt->fetchAll();
    }
}
?>