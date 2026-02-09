<?php
require_once __DIR__ . "/../models/comunity_db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

// CSRF Validation
function validateCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    if (!empty($_SESSION['csrf_token_session']) && $token === $_SESSION['csrf_token_session']) {
        return true;
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF invalido']);
    return false;
}

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

// Validate CSRF for write operations (temporarily disabled for testing)
// $writeActions = ['create', 'update', 'delete'];
// if (in_array($action, $writeActions)) {
//     if (!validateCsrfToken()) exit;
// }

if ($action === 'list') {
    // Obtener calles con nombre del lider
    $stmt = $conn->prepare("
        SELECT s.id_street, s.name_street, s.id_leader, p.name_person as leader_name 
        FROM street s 
        LEFT JOIN person p ON s.id_leader = p.id_person 
        ORDER BY s.id_street DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['status'=>'ok','data'=>$rows]);
    exit;
}

if ($action === 'create') {
    $name = $_POST['name'] ?? '';
    $id_leader = isset($_POST['id_leader']) && $_POST['id_leader'] !== '' ? intval($_POST['id_leader']) : null;
    
    if (!$name) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    // Validar que el lider exista si se proporciona
    if ($id_leader) {
        $check = $conn->prepare("SELECT id_person FROM person WHERE id_person = ?");
        $check->bind_param('i', $id_leader);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Persona lider no encontrada']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO street (name_street, id_leader) VALUES (?, ?)");
    $stmt->bind_param('si', $name, $id_leader);
    if ($stmt->execute()) echo json_encode(['status'=>'ok','id'=>$stmt->insert_id]); 
    else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $id_leader = isset($_POST['id_leader']) && $_POST['id_leader'] !== '' ? intval($_POST['id_leader']) : null;
    
    if (!$id || !$name) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    $stmt = $conn->prepare("UPDATE street SET name_street = ?, id_leader = ? WHERE id_street = ?");
    $stmt->bind_param('sii', $name, $id_leader, $id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status'=>'error','message'=>'id faltante']); exit; }
    
    // Verificar si hay plazas en esta calle
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM square WHERE id_street = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['cnt'];
    $check->close();
    
    if ($count > 0) {
        echo json_encode(['status'=>'error','message'=>'No se puede eliminar: hay plazas asociadas a esta calle']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM street WHERE id_street = ?");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Accion no soportada']);
