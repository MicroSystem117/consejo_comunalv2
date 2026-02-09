<?php
require_once __DIR__ . "/../models/comunity_db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

// CSRF Validation - Temporarily simplified
function validateCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    // Check session token
    if ($token && !empty($_SESSION['csrf_token_session'])) {
        if (hash_equals($_SESSION['csrf_token_session'], $token)) {
            return true;
        }
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF invalido']);
    return false;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token_session'])) {
    $_SESSION['csrf_token_session'] = bin2hex(random_bytes(32));
}

try {
    $db = new ComunityDb();
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

// Validate CSRF for state-changing operations (temporarily disabled for testing)
$writeActions = ['create', 'update', 'delete'];
// if (in_array($action, $writeActions)) {
//     if (!validateCsrfToken($conn)) {
//         exit;
//     }
// }

if ($action === 'list') {
    $stmt = $conn->prepare("SELECT id_person, name_person, ci_person, birth_person FROM person ORDER BY id_person DESC");
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['status'=>'ok','data'=>$rows]);
    exit;
}

if ($action === 'create') {
    $name = $_POST['name'] ?? '';
    $ci = $_POST['ci'] ?? '';
    $birth = $_POST['birth'] ?? null;

    if (!$name || !$ci) {
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); exit;
    }

    // Validar formato de cedula
    if (!is_numeric($ci) || $ci < 1000000 || $ci > 99999999) {
        echo json_encode(['status'=>'error','message'=>'Cedula invalida']); exit;
    }

    // Verificar si la cedula ya existe
    $check = $conn->prepare("SELECT id_person FROM person WHERE ci_person = ?");
    $check->bind_param('i', $ci);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status'=>'error','message'=>'Esta cedula ya esta registrada']); 
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO person (name_person, ci_person, birth_person) VALUES (?, ?, ?)");
    $stmt->bind_param('sis', $name, $ci, $birth);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'ok','id'=>$stmt->insert_id]);
    } else {
        echo json_encode(['status'=>'error','message'=>$conn->error]);
    }
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $ci = $_POST['ci'] ?? '';
    $birth = $_POST['birth'] ?? null;
    
    if (!$id || !$name || !$ci) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    // Validar formato de cedula
    if (!is_numeric($ci) || $ci < 1000000 || $ci > 99999999) {
        echo json_encode(['status'=>'error','message'=>'Cedula invalida']); exit;
    }
    
    $stmt = $conn->prepare("UPDATE person SET name_person = ?, ci_person = ?, birth_person = ? WHERE id_person = ?");
    $stmt->bind_param('sisi', $name, $ci, $birth, $id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status'=>'error','message'=>'id faltante']); exit; }
    
    // Verificar si tiene familia asignada
    $check = $conn->prepare("SELECT id_family FROM person WHERE id_person = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $checkRes = $check->get_result();
    $person = $checkRes->fetch_assoc();
    $check->close();
    
    if ($person && $person['id_family']) {
        echo json_encode(['status'=>'error','message'=>'No se puede eliminar: persona tiene familia asignada']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM person WHERE id_person = ?");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Accion no soportada']);
