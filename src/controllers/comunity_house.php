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
    // Obtener viviendas con informacion de la plaza
    $stmt = $conn->prepare("
        SELECT h.id_house, h.id_square, h.number_house, s.codigo_square, st.name_street 
        FROM house h 
        LEFT JOIN square s ON h.id_square = s.id_square 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY h.id_house DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['status'=>'ok','data'=>$rows]);
    exit;
}

if ($action === 'create') {
    $id_square = isset($_POST['id_square']) ? intval($_POST['id_square']) : null;
    $number = $_POST['number'] ?? '';
    
    if (!$id_square || !$number) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    // Verificar que la plaza exista
    $check = $conn->prepare("SELECT id_square FROM square WHERE id_square = ?");
    $check->bind_param('i', $id_square);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['status'=>'error','message'=>'Plaza no encontrada']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO house (id_square, number_house) VALUES (?, ?)");
    $stmt->bind_param('is', $id_square, $number);
    if ($stmt->execute()) echo json_encode(['status'=>'ok','id'=>$stmt->insert_id]); 
    else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $id_square = isset($_POST['id_square']) ? intval($_POST['id_square']) : null;
    $number = $_POST['number'] ?? '';
    
    if (!$id || !$id_square || !$number) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    $stmt = $conn->prepare("UPDATE house SET id_square = ?, number_house = ? WHERE id_house = ?");
    $stmt->bind_param('isi', $id_square, $number, $id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status'=>'error','message'=>'id faltante']); exit; }
    
    // Verificar si hay familias en esta vivienda
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM family WHERE id_house = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['cnt'];
    $check->close();
    
    if ($count > 0) {
        echo json_encode(['status'=>'error','message'=>'No se puede eliminar: hay familias asociadas a esta vivienda']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM house WHERE id_house = ?");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Accion no soportada']);
