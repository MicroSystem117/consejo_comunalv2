<?php
// Controller for Backup and Restore operations
session_start();

// Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
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
        $includeCredentials = ($_POST['include_credentials'] ?? '1') === '1';
        
        $databases = ['comunity'];
        if ($includeCredentials) {
            $databases[] = 'credentials';
        }
        
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
            $allContent .= "-- ============================================\n";
            $allContent .= "-- Base de datos: {$db}\n";
            $allContent .= "-- ============================================\n\n";
            
            // Use mysqldump if available
            $cmd = "mysqldump --host={$host} --user={$user}";
            if (!empty($pass)) {
                $cmd .= " --password={$pass}";
            }
            $cmd .= " --single-transaction --routines --triggers {$db}";
            
            $output = [];
            $returnVar = 0;
            exec($cmd, $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output)) {
                $allContent .= implode("\n", $output) . "\n";
            } else {
                // Fallback to manual dump if mysqldump fails
                $allContent .= getManualDump($host, $user, $pass, $db);
            }
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
            echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
            exit;
        }
        
        $file = $_FILES['backup_file']['tmp_name'];
        $content = file_get_contents($file);
        
        if (!$content) {
            echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo']);
            exit;
        }
        
        $host = '127.0.0.1';
        $user = 'root';
        $pass = '';
        
        $conn = new mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            throw new Exception('Error de conexión: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
        
        // Get databases from file and execute SQL for each
        $databases = ['comunity', 'credentials'];
        
        foreach ($databases as $db) {
            // Create database if not exists
            $conn->query("CREATE DATABASE IF NOT EXISTS `{$db}`");
            $conn->select_db($db);
            
            // Get all INSERT statements for this database
            preg_match_all('/INSERT INTO.*?;/s', $content, $matches);
            
            foreach ($matches[0] as $sql) {
                $sql = trim($sql);
                if (!empty($sql)) {
                    $conn->query($sql);
                }
            }
        }
        
        $conn->close();
        
        echo json_encode(['success' => true, 'message' => 'Restauración completada exitosamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al restaurar: ' . $e->getMessage()]);
    }
}

function listBackups() {
    header('Content-Type: application/json');
    
    $backupDir = __DIR__ . '/../../backups';
    
    $backups = [];
    
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/backup_*.sql');
        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'name' => $filename,
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'size' => formatBytes(filesize($file))
            ];
        }
        
        // Sort by date descending
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
    }
    
    echo json_encode(['backups' => $backups]);
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
