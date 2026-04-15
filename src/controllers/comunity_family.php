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
    // Obtener familias con informacion de la vivienda
    $stmt = $conn->prepare("
        SELECT f.id_family, f.id_house, f.surname_family, h.number_house, s.codigo_square, st.name_street 
        FROM family f 
        LEFT JOIN house h ON f.id_house = h.id_house 
        LEFT JOIN square s ON h.id_square = s.id_square 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY f.id_family DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['status'=>'ok','data'=>$rows]);
    exit;
}

if ($action === 'create') {
    $id_house = isset($_POST['id_house']) ? intval($_POST['id_house']) : null;
    $surname = $_POST['surname'] ?? '';
    
    if (!$surname || !$id_house) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    // Verificar que la vivienda exista
    $check = $conn->prepare("SELECT id_house FROM house WHERE id_house = ?");
    $check->bind_param('i', $id_house);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['status'=>'error','message'=>'Vivienda no encontrada']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO family (id_house, surname_family) VALUES (?, ?)");
    $stmt->bind_param('is', $id_house, $surname);
    if ($stmt->execute()) echo json_encode(['status'=>'ok','id'=>$stmt->insert_id]); 
    else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $id_house = isset($_POST['id_house']) ? intval($_POST['id_house']) : null;
    $surname = $_POST['surname'] ?? '';
    
    if (!$id || !$surname || !$id_house) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    $stmt = $conn->prepare("UPDATE family SET id_house = ?, surname_family = ? WHERE id_family = ?");
    $stmt->bind_param('isi', $id_house, $surname, $id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status'=>'error','message'=>'id faltante']); exit; }
    
    // Verificar si hay personas en esta familia
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM person WHERE id_family = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['cnt'];
    $check->close();
    
    if ($count > 0) {
        echo json_encode(['status'=>'error','message'=>'No se puede eliminar: hay personas asociadas a esta familia']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM family WHERE id_family = ?");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Accion no soportada']);
