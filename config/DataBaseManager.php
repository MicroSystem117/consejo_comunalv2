<?php
class DataBase {
    private static $connections = [];

    Public static function connect($dbName){
        $key = strtolower($dbName);
        if(!isset(self::$connections[$key])) {
            switch($key) {
                case 'credentials':
                    // credentials ahora es alias de comunity para unificar la base de datos
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
            // Forzar charset utf8mb4 para evitar problemas de acentos y caracteres especiales
            if (!$conn->set_charset('utf8mb4')) {
                throw new Exception("Error configurando charset utf8mb4: " . $conn->error);
            }
            self::$connections[$key] = $conn;
        }
        return self::$connections[$key];
    }
}
?>