<?php
include_once __DIR__ . '\..\libraries\include.php';
class display
{
    public string $first_name;
    public string $middle_name;
    public string $last_name;
    public string $suffix;
    public string $email;
    public string $password;
    public string $created_at;

    private $con;

    private string $response;

    public function __construct($db)
    {
        $this->con = $db;
    }

    public function getAllStaff()
    {
        // With only two roles (0 = user, 1 = admin), staff/admins are role_id = 1
        $stmt = $this->con->prepare('SELECT * FROM user WHERE role_id = 1');
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return [];
        }
        return $stmt->fetchAll();
    }
    public function getAllByDept()
    {
        // Departments/offices removed in the new schema — return empty array
        return [];
    }
    public function getAllAdmin()
    {
        // Admins are role_id = 1
        $stmt = $this->con->prepare('SELECT * FROM user WHERE role_id = 1');
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return [];
        }
        return $stmt->fetchAll();
    }

    public function getAll()
    {
        $stmt = $this->con->prepare('SELECT * FROM user');
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return [];
        }
        return $stmt->fetchAll();
    }
}
?>