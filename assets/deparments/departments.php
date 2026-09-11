<?php
class Departments
{
    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    public function getDepartments()
{
    $depts = [];
    $stmt = $this->con->query("SELECT office_id, office_name FROM offices");
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $depts[$row['office_id']] = $row['office_name'];
    }
    
    return $depts;
}
}


?>