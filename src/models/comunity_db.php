<?php
require_once __DIR__ . "/../../config/DataBaseManager.php";

class ComunityDb {
    private $conn;

    public function __construct()
    {
        $this->conn = DataBase::connect("comunity");
    }

    public function getConnection()
    {
        return $this->conn;
    }
}

?>
