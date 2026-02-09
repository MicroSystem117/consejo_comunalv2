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
    // Obtener plazas con nombre de la calle
    $stmt = $conn->prepare("
        SELECT s.id_square, s.id_street, s.codigo_square, st.name_street 
        FROM square s 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY s.id_square DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['status'=>'ok','data'=>$rows]);
    exit;
}

if ($action === 'create') {
    $id_street = isset($_POST['id_street']) ? intval($_POST['id_street']) : null;
    $codigo = $_POST['codigo'] ?? '';
    
    if (!$id_street || !$codigo) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    // Verificar que la calle exista
    $check = $conn->prepare("SELECT id_street FROM street WHERE id_street = ?");
    $check->bind_param('i', $id_street);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['status'=>'error','message'=>'Calle no encontrada']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO square (id_street, codigo_square) VALUES (?, ?)");
    $stmt->bind_param('is', $id_street, $codigo);
    if ($stmt->execute()) echo json_encode(['status'=>'ok','id'=>$stmt->insert_id]); 
    else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $id_street = isset($_POST['id_street']) ? intval($_POST['id_street']) : null;
    $codigo = $_POST['codigo'] ?? '';
    
    if (!$id || !$id_street || !$codigo) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    $stmt = $conn->prepare("UPDATE square SET id_street = ?, codigo_square = ? WHERE id_square = ?");
    $stmt->bind_param('isi', $id_street, $codigo, $id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status'=>'error','message'=>'id faltante']); exit; }
    
    // Verificar si hay viviendas en esta plaza
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM house WHERE id_square = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['cnt'];
    $check->close();
    
    if ($count > 0) {
        echo json_encode(['status'=>'error','message'=>'No se puede eliminar: hay viviendas asociadas a esta plaza']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM square WHERE id_square = ?");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Accion no soportada']);
