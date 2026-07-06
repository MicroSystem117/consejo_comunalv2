<?php
// Controller for Backup and Restore operations
error_reporting(E_ALL);
ini_set('display_errors', '0');
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../helpers/sql_import.php';

// Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['mode']) && !isset($_GET['action']) && !isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'restore' && !isset($_FILES['backup_file'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo.']);
    exit;
}

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? $_GET['mode'] ?? '';

switch ($action) {
    case 'backup':
        performBackup();
        break;
    case 'restore':
        performRestore();
        break;
    case 'list':
        listBackups();
        break;
    case 'delete':
        deleteBackup();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function performBackup() {
    try {
        $databases = ['comunity'];
        
        $backupDir = __DIR__ . '/../../backups';
        $backupDir = __DIR__ . '/../../backups';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        $host = '127.0.0.1';
        $user = 'root';
        $pass = '';
        
        $allContent = "-- ============================================\n";
        $allContent .= "-- Respaldo de Base de Datos\n";
        $allContent .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $allContent .= "-- Servidor: 127.0.0.1\n";
        $allContent .= "-- ============================================\n\n";
        $allContent .= "SET NAMES utf8mb4;\n";
        $allContent .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($databases as $db) {
            $allContent .= "DROP DATABASE IF EXISTS `{$db}`;\n";
            $allContent .= "CREATE DATABASE `{$db}`;\n";
            $allContent .= "USE `{$db}`;\n\n";
            $allContent .= "-- ============================================\n";
            $allContent .= "-- Base de datos: {$db}\n";
            $allContent .= "-- ============================================\n\n";

            $allContent .= getManualDump($host, $user, $pass, $db);
        }
        
        $allContent .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Save file
        file_put_contents($backupFile, $allContent);
        
        // Download the file
        if (file_exists($backupFile)) {
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backupFile));
            readfile($backupFile);
            exit;
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al crear respaldo: ' . $e->getMessage()]);
    }
}

function getManualDump($host, $user, $pass, $db) {
    $content = '';

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        return $content;
    }
    $conn->set_charset('utf8mb4');
    
    // Get tables
    $result = $conn->query("SHOW TABLES");
    if (!$result) {
        return $content;
    }
    
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        $content .= "-- Estructura de la tabla: {$table}\n";
        $content .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $createResult = $conn->query("SHOW CREATE TABLE `{$table}`");
        if ($createResult) {
            $createRow = $createResult->fetch_row();
            $content .= $createRow[1] . ";\n\n";
            $createResult->free();
        }
        
        // Get data
        $dataResult = $conn->query("SELECT * FROM `{$table}`");
        if ($dataResult && $dataResult->num_rows > 0) {
            $content .= "-- Datos de la tabla: {$table}\n";
            
            while ($rowData = $dataResult->fetch_assoc()) {
                $values = [];
                foreach ($rowData as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                $content .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $content .= "\n";
            $dataResult->free();
        }
    }
    
    $conn->close();
    return $content;
}

function performRestore() {
    header('Content-Type: application/json');
    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Archivo no recibido correctamente.');
        }

        $backupFile = $_FILES['backup_file']['tmp_name'];
        $originalName = $_FILES['backup_file']['name'] ?? '';

        if (!is_file($backupFile)) {
            throw new Exception('No se encontró el archivo subido.');
        }

        if (!preg_match('/\.sql$/i', $originalName)) {
            throw new Exception('El archivo debe tener extensión .sql.');
        }

        if (filesize($backupFile) === false || filesize($backupFile) === 0) {
            throw new Exception('El archivo está vacío.');
        }

        $sql = file_get_contents($backupFile);
        if ($sql === false || trim($sql) === '') {
            throw new Exception('No se pudo leer o el archivo SQL está vacío.');
        }

        $dbName = 'comunity';

        // Create a pre-restore backup of the current database state
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $preFile = $backupDir . '/pre_restore_' . date('Y-m-d_H-i-s') . '.sql';
        $preContent = "-- Pre-restore backup\n-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        $preContent .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
        $preContent .= getManualDump('127.0.0.1', 'root', '', $dbName);
        $preContent .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        @file_put_contents($preFile, $preContent);

        // Perform import allowing schema changes (drop/create) to fully restore
        importSqlFile($backupFile, '127.0.0.1', 'root', '', $dbName, ['allow_schema' => true]);

        echo json_encode([
            'success' => true,
            'message' => 'Respaldo restaurado correctamente en la base de datos "comunity". Se creó un respaldo previo: ' . basename($preFile)
        ]);
    } catch (Throwable $e) {
        error_log('Restore error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al restaurar: ' . $e->getMessage()]);
    }
}

function listBackups() {
    header('Content-Type: application/json');

    try {
        $backupDir = __DIR__ . '/../../backups';
        $backups = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/backup_*.sql');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (!is_file($file)) {
                        continue;
                    }

                    $filename = basename($file);
                    $backups[] = [
                        'name' => $filename,
                        'date' => date('Y-m-d H:i:s', filemtime($file)),
                        'size' => formatBytes(filesize($file))
                    ];
                }

                usort($backups, function ($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
            }
        }

        echo json_encode(['backups' => $backups]);
    } catch (Throwable $e) {
        error_log('List backups error: ' . $e->getMessage());
        echo json_encode(['backups' => [], 'error' => $e->getMessage()]);
    }
}

function deleteBackup() {
    header('Content-Type: application/json');
    
    $file = $_POST['file'] ?? $_GET['file'] ?? '';
    
    if (empty($file)) {
        echo json_encode(['success' => false, 'message' => 'Archivo no especificado']);
        return;
    }
    
    $backupDir = __DIR__ . '/../../backups';
    $filePath = $backupDir . '/' . basename($file);
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo json_encode(['success' => true, 'message' => 'Respaldo eliminado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el archivo']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
    }
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = ['B', 'KB', 'MB', 'GB'];
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
