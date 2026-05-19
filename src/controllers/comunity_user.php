<?php
/**
 * Controller for User Management (CRUD)
 * Handles user accounts in the unified comunity database
 */

require_once __DIR__ . "/../models/dbuser.php";
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

session_start();

// CSRF Validation
function validateCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token || empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF invalido']);
        return false;
    }
    return true;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$dbUser = new DbUser();
$conn = $dbUser->getConnection();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['mode'] ?? $_POST['action'] ?? 'list';

// Get all users with their levels
if ($action === 'list') {
    header('Content-Type: application/json');
    
    $stmt = $conn->prepare("
        SELECT u.id_user, u.name, u.surname, u.ci, u.birth, u.id_level, l.user_role 
        FROM `user` u 
        LEFT JOIN `level` l ON u.id_level = l.id_level 
        ORDER BY u.id_user DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    
    $users = [];
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(['data' => $users]);
    exit;
}

// Get single user
if ($action === 'get') {
    $id = $_GET['id'] ?? 0;
    
    $stmt = $conn->prepare("
        SELECT u.*, l.user_role 
        FROM `user` u 
        LEFT JOIN `level` l ON u.id_level = l.id_level 
        WHERE u.id_user = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
    exit;
}

// Get security questions for user
if ($action === 'getSecQuestion') {
    $id = $_GET['id'] ?? 0;
    
    $stmt = $conn->prepare("SELECT QuestOne, QuestTwo FROM SecQuestion WHERE id_user = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Create or Update user
if ($action === 'save') {
    if (!validateCsrfToken()) exit;
    
    // Check if user has permission (only admin level 1 can manage users)
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $stmt = $conn->prepare("SELECT id_level FROM `user` WHERE id_user = ?");
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $current_user = $res->fetch_assoc();
    
    if (!$current_user || $current_user['id_level'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para gestionar usuarios']);
        exit;
    }
    
    $id_user = $_POST['id_user'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $ci = (int)($_POST['ci'] ?? 0);
    $birth = $_POST['birth'] ?? null;
    $id_level = (int)($_POST['id_level'] ?? 3);
    $pass = $_POST['pass'] ?? '';
    
    if (!$name || !$ci) {
        echo json_encode(['status' => 'error', 'message' => 'Nombre y Cédula son requeridos']);
        exit;
    }
    
    if ($id_user) {
        // Update existing user
        if (!empty($pass)) {
            // Update password too
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE `user` SET name=?, surname=?, ci=?, birth=?, id_level=?, pass=? WHERE id_user=?");
            $stmt->bind_param('ssisssi', $name, $surname, $ci, $birth, $id_level, $hash, $id_user);
        } else {
            // Update without password
            $stmt = $conn->prepare("UPDATE `user` SET name=?, surname=?, ci=?, birth=?, id_level=? WHERE id_user=?");
            $stmt->bind_param('ssissi', $name, $surname, $ci, $birth, $id_level, $id_user);
        }
    } else {
        // Create new user
        if (empty($pass)) {
            echo json_encode(['status' => 'error', 'message' => 'La contraseña es requerida para nuevos usuarios']);
            exit;
        }
        
        // Check if CI already exists
        $stmt = $conn->prepare("SELECT id_user FROM `user` WHERE ci = ?");
        $stmt->bind_param('i', $ci);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'La Cédula ya está registrada']);
            exit;
        }
        
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO `user` (name, surname, ci, birth, id_level, pass) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssisss', $name, $surname, $ci, $birth, $id_level, $hash);
        $id_user = null;
    }
    
    if ($stmt->execute()) {
        if (!$id_user) {
            $id_user = $stmt->insert_id;
        }
        
        // Handle security questions if provided
        $sq1 = trim($_POST['sq1'] ?? '');
        $sa1 = trim($_POST['sa1'] ?? '');
        $sq2 = trim($_POST['sq2'] ?? '');
        $sa2 = trim($_POST['sa2'] ?? '');
        
        if ($sq1 && $sa1 && $sq2 && $sa2) {
            // Validate questions are distinct
            if ($sq1 === $sq2) {
                echo json_encode(['status' => 'error', 'message' => 'Las preguntas de seguridad deben ser distintas']);
                exit;
            }
            
            // Hash answers
            $ha1 = password_hash($sa1, PASSWORD_DEFAULT);
            $ha2 = password_hash($sa2, PASSWORD_DEFAULT);
            
            // Check if SecQuestion exists
            $secStmt = $conn->prepare("SELECT id_SecQuest FROM SecQuestion WHERE id_user = ?");
            $secStmt->bind_param('i', $id_user);
            $secStmt->execute();
            $secRes = $secStmt->get_result();
            $exists = $secRes->num_rows > 0;
            $secStmt->close();
            
            if ($exists) {
                $updateSec = $conn->prepare("UPDATE SecQuestion SET QuestOne = ?, QuestTwo = ?, AnswerOne = ?, AnswerTwo = ? WHERE id_user = ?");
                $updateSec->bind_param('ssssi', $sq1, $sq2, $ha1, $ha2, $id_user);
                $updateSec->execute();
                $updateSec->close();
            } else {
                $insertSec = $conn->prepare("INSERT INTO SecQuestion (QuestOne, QuestTwo, AnswerOne, AnswerTwo, id_user) VALUES (?, ?, ?, ?, ?)");
                $insertSec->bind_param('ssssi', $sq1, $sq2, $ha1, $ha2, $id_user);
                $insertSec->execute();
                $insertSec->close();
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => $id_user ? 'Usuario actualizado' : 'Usuario creado', 'id_user' => $id_user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Delete user
if ($action === 'delete') {
    if (!validateCsrfToken()) exit;
    
    // Check if user has permission
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $stmt = $conn->prepare("SELECT id_level FROM `user` WHERE id_user = ?");
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $current_user = $res->fetch_assoc();
    
    if (!$current_user || $current_user['id_level'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para eliminar usuarios']);
        exit;
    }
    
    $id = $_POST['id_user'] ?? 0;
    
    // Don't allow deleting yourself
    if ($id == $current_user_id) {
        echo json_encode(['status' => 'error', 'message' => 'No puede eliminarse a sí mismo']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM `user` WHERE id_user = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Usuario eliminado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Get levels for dropdown
if ($action === 'getLevels') {
    $stmt = $conn->query("SELECT id_level, user_role FROM `level` ORDER BY id_level");
    $levels = [];
    while ($row = $stmt->fetch_assoc()) {
        $levels[] = $row;
    }
    echo json_encode($levels);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
