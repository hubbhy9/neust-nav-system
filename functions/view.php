<?php
include __DIR__ . '\..\libraries\include.php';
class view
{
    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    public function getById(int $id): array
    {
        $stmt = $this->con->prepare('SELECT * FROM user WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [];
    }
}
?>