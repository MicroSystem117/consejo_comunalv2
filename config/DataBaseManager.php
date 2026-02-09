<?php
class DataBase {
    private static $connections = [];

    Public static function connect($dbName){
        $key = strtolower($dbName);
        if(!isset(self::$connections[$key])) {
            switch($key) {
                case 'credentials':
                    $db = 'credentials';
                    break;
                case 'comunity':
                case 'comunidad':
                    $db = 'comunity';
                    break;
                default:
                    throw new Exception("Base de datos no configurada: " . $dbName);
            }

            $conn = new mysqli("127.0.0.1", "root", "", $db);
            if ($conn->connect_error) {
                throw new Exception("Error conectando a la BD {$db}: " . $conn->connect_error);
            }
            self::$connections[$key] = $conn;
        }
        return self::$connections[$key];
    }
}
?>