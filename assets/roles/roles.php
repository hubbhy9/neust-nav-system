<?php
class roles
{
    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    public function getroles(){
    $roles = [];
    $stmt = $this->con->query("SELECT role_id, role_name FROM roles");
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $roles[$row['role_id']] = $row['role_name'];
    }
    
    return $roles;
}
}


?>