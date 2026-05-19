<?php
require_once __DIR__ . "/../../config/DataBaseManager.php";

class DbUser {
    private $conn;

    public function __construct()
    {
        // Usuarios y credenciales ahora viven en la misma base de datos comunity
        $this->conn = DataBase::connect("comunity");
    }

    public function getConnection()
    {
        return $this->conn;
    }
}

?>