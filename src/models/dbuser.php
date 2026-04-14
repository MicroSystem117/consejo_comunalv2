<?php
require_once __DIR__ . "/../../config/DataBaseManager.php";

class DbUser {
    private $conn;

    public function __construct()
    {
        $this->conn = DataBase::connect("credentials");
    }

    public function getConnection()
    {
        return $this->conn;
    }
}

?>