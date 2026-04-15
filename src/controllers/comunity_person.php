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
    $stmt = $conn->prepare("SELECT p.id_person, p.name_person, p.ci_person, p.birth_person, p.id_family, f.surname_family FROM person p LEFT JOIN family f ON p.id_family = f.id_family ORDER BY p.id_person DESC");
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['status'=>'ok','data'=>$rows]);
    exit;
}

if ($action === 'get') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status'=>'error','message'=>'ID inválido']);
        exit;
    }
    $stmt = $conn->prepare("SELECT p.id_person, p.name_person, p.ci_person, p.birth_person, p.id_family, p.id_family, f.surname_family FROM person p LEFT JOIN family f ON p.id_family = f.id_family WHERE p.id_person = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if ($row) {
        echo json_encode(['status'=>'ok','data'=>$row]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Persona no encontrada']);
    }
    exit;
}

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $ci = trim($_POST['ci'] ?? '');
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

    $id_family = isset($_POST['id_family']) ? intval($_POST['id_family']) : 0;

    if ($id_family > 0) {
        $checkFamily = $conn->prepare("SELECT id_family FROM family WHERE id_family = ?");
        $checkFamily->bind_param('i', $id_family);
        $checkFamily->execute();
        if ($checkFamily->get_result()->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Familia seleccionada no existe']);
            exit;
        }
        $checkFamily->close();
    } else {
        // Si no se selecciona familia, usar secuencia basada en calle/manzana/vivienda/apellido
        $id_street = isset($_POST['id_street']) ? intval($_POST['id_street']) : 0;
        $id_square = isset($_POST['id_square']) ? intval($_POST['id_square']) : 0;
        $number_house = trim($_POST['number_house'] ?? '');
        $surname_family = trim($_POST['surname_family'] ?? '');

        if (!$id_street || !$id_square || !$number_house || !$surname_family) {
            echo json_encode(['status'=>'error','message'=>'Debe completar calle, manzana, vivienda y apellido de familia si no selecciona familia existente']);
            exit;
        }

        // Validar calle
        $checkStreet = $conn->prepare("SELECT id_street FROM street WHERE id_street = ?");
        $checkStreet->bind_param('i', $id_street);
        $checkStreet->execute();
        if ($checkStreet->get_result()->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Calle no encontrada']);
            exit;
        }
        $checkStreet->close();

        // Validar manzana y relación calle
        $checkSquare = $conn->prepare("SELECT id_square FROM square WHERE id_square = ? AND id_street = ?");
        $checkSquare->bind_param('ii', $id_square, $id_street);
        $checkSquare->execute();
        if ($checkSquare->get_result()->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Manzana no encontrada para la calle seleccionada']);
            exit;
        }
        $checkSquare->close();

        // Buscar o crear vivienda
        $houseStmt = $conn->prepare("SELECT id_house FROM house WHERE id_square = ? AND number_house = ? LIMIT 1");
        $houseStmt->bind_param('is', $id_square, $number_house);
        $houseStmt->execute();
        $houseRes = $houseStmt->get_result();
        if ($houseRes->num_rows > 0) {
            $id_house = $houseRes->fetch_assoc()['id_house'];
        } else {
            $createHouse = $conn->prepare("INSERT INTO house (id_square, number_house) VALUES (?, ?)");
            $createHouse->bind_param('is', $id_square, $number_house);
            $createHouse->execute();
            if (!$createHouse->affected_rows) {
                echo json_encode(['status'=>'error','message'=>'Error creando vivienda: ' . $conn->error]);
                exit;
            }
            $id_house = $createHouse->insert_id;
            $createHouse->close();
        }
        $houseStmt->close();

        // Buscar o crear familia
        $familyStmt = $conn->prepare("SELECT id_family FROM family WHERE id_house = ? AND surname_family = ? LIMIT 1");
        $familyStmt->bind_param('is', $id_house, $surname_family);
        $familyStmt->execute();
        $familyRes = $familyStmt->get_result();
        if ($familyRes->num_rows > 0) {
            $id_family = $familyRes->fetch_assoc()['id_family'];
        } else {
            $createFamily = $conn->prepare("INSERT INTO family (id_house, surname_family) VALUES (?, ?)");
            $createFamily->bind_param('is', $id_house, $surname_family);
            $createFamily->execute();
            if (!$createFamily->affected_rows) {
                echo json_encode(['status'=>'error','message'=>'Error creando familia: ' . $conn->error]);
                exit;
            }
            $id_family = $createFamily->insert_id;
            $createFamily->close();
        }
        $familyStmt->close();
    }

    $stmt = $conn->prepare("INSERT INTO person (name_person, ci_person, birth_person, id_family) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sisi', $name, $ci, $birth, $id_family);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'ok','id'=>$stmt->insert_id]);
    } else {
        echo json_encode(['status'=>'error','message'=>$conn->error]);
    }
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $ci = trim($_POST['ci'] ?? '');
    $birth = $_POST['birth'] ?? null;
    
    if (!$id || !$name || !$ci) { 
        echo json_encode(['status'=>'error','message'=>'Faltan datos']); 
        exit; 
    }
    
    // Validar formato de cedula
    if (!is_numeric($ci) || $ci < 1000000 || $ci > 99999999) {
        echo json_encode(['status'=>'error','message'=>'Cedula invalida']); exit;
    }

    $id_family = isset($_POST['id_family']) ? intval($_POST['id_family']) : 0;

    if ($id_family > 0) {
        $checkFamily = $conn->prepare("SELECT id_family FROM family WHERE id_family = ?");
        $checkFamily->bind_param('i', $id_family);
        $checkFamily->execute();
        if ($checkFamily->get_result()->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Familia seleccionada no existe']);
            exit;
        }
        $checkFamily->close();
    } else {
        $id_street = isset($_POST['id_street']) ? intval($_POST['id_street']) : 0;
        $id_square = isset($_POST['id_square']) ? intval($_POST['id_square']) : 0;
        $number_house = trim($_POST['number_house'] ?? '');
        $surname_family = trim($_POST['surname_family'] ?? '');

        if (!$id_street || !$id_square || !$number_house || !$surname_family) {
            echo json_encode(['status'=>'error','message'=>'Debe completar calle, manzana, vivienda y apellido de familia si no selecciona familia existente']);
            exit;
        }

        $checkStreet = $conn->prepare("SELECT id_street FROM street WHERE id_street = ?");
        $checkStreet->bind_param('i', $id_street);
        $checkStreet->execute();
        if ($checkStreet->get_result()->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Calle no encontrada']);
            exit;
        }
        $checkStreet->close();

        $checkSquare = $conn->prepare("SELECT id_square FROM square WHERE id_square = ? AND id_street = ?");
        $checkSquare->bind_param('ii', $id_square, $id_street);
        $checkSquare->execute();
        if ($checkSquare->get_result()->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Manzana no encontrada para la calle seleccionada']);
            exit;
        }
        $checkSquare->close();

        $houseStmt = $conn->prepare("SELECT id_house FROM house WHERE id_square = ? AND number_house = ? LIMIT 1");
        $houseStmt->bind_param('is', $id_square, $number_house);
        $houseStmt->execute();
        $houseRes = $houseStmt->get_result();
        if ($houseRes->num_rows > 0) {
            $id_house = $houseRes->fetch_assoc()['id_house'];
        } else {
            $createHouse = $conn->prepare("INSERT INTO house (id_square, number_house) VALUES (?, ?)");
            $createHouse->bind_param('is', $id_square, $number_house);
            $createHouse->execute();
            if (!$createHouse->affected_rows) {
                echo json_encode(['status'=>'error','message'=>'Error creando vivienda: ' . $conn->error]);
                exit;
            }
            $id_house = $createHouse->insert_id;
            $createHouse->close();
        }
        $houseStmt->close();

        $familyStmt = $conn->prepare("SELECT id_family FROM family WHERE id_house = ? AND surname_family = ? LIMIT 1");
        $familyStmt->bind_param('is', $id_house, $surname_family);
        $familyStmt->execute();
        $familyRes = $familyStmt->get_result();
        if ($familyRes->num_rows > 0) {
            $id_family = $familyRes->fetch_assoc()['id_family'];
        } else {
            $createFamily = $conn->prepare("INSERT INTO family (id_house, surname_family) VALUES (?, ?)");
            $createFamily->bind_param('is', $id_house, $surname_family);
            $createFamily->execute();
            if (!$createFamily->affected_rows) {
                echo json_encode(['status'=>'error','message'=>'Error creando familia: ' . $conn->error]);
                exit;
            }
            $id_family = $createFamily->insert_id;
            $createFamily->close();
        }
        $familyStmt->close();
    }

    $stmt = $conn->prepare("UPDATE person SET name_person = ?, ci_person = ?, birth_person = ?, id_family = ? WHERE id_person = ?");
    $stmt->bind_param('sisii', $name, $ci, $birth, $id_family, $id);
    if ($stmt->execute()) echo json_encode(['status'=>'ok']); else echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status'=>'error','message'=>'id faltante']); exit; }

    $stmt = $conn->prepare("DELETE FROM person WHERE id_person = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'ok']);
    } else {
        echo json_encode(['status'=>'error','message'=>$conn->error]);
    }
    exit;
}

// Dentro de comunity_person.php, añade esta acción:

if ($action === 'pdf') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) throw new Exception("ID no proporcionado");

        // Consulta con JOIN para traer la dirección completa
        $stmt = $conn->prepare("
            SELECT p.name_person, p.ci_person, f.surname_family, 
                   h.number_house, s.codigo_square, st.name_street
            FROM person p
            LEFT JOIN family f ON p.id_family = f.id_family
            LEFT JOIN house h ON f.id_house = h.id_house
            LEFT JOIN square s ON h.id_square = s.id_square
            LEFT JOIN street st ON s.id_street = st.id_street
            WHERE p.id_person = ?
        ");
        if (!$stmt) throw new Exception("Error en prepare: " . $conn->error);
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) throw new Exception("Error en execute: " . $stmt->error);
        $person = $stmt->get_result()->fetch_assoc();

        if (!$person) throw new Exception("Persona no encontrada");

        // Generación del PDF con mPDF
        require_once __DIR__ . '/../../vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf();

        // Hacer que $person esté disponible en el template
        extract(["person" => $person]);
        ob_start();
        include __DIR__ . '/../views/pdf_template.php';
        $html = ob_get_clean();

        $mpdf->WriteHTML($html);
        $mpdf->Output("Constancia_{$person['ci_person']}.pdf", \Mpdf\Output\Destination::INLINE);
        exit;
    } catch (Exception $e) {
        echo "<h2>Error al generar PDF:</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit;
    }
}

echo json_encode(['status'=>'error','message'=>'Accion no soportada']);
