<?php
include __DIR__ . '\..\libraries\include.php';
class view
{
    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    public function getById(int $user_id): array
    {
        $stmt = $this->con->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);   
        return $stmt->fetch() ?: [];
    }
}
?>