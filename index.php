<?php
/**
 * Front Controller del Sistema del Consejo Comunal
 * 
 * Maneja el enrutamiento y renderizado de vistas
 * Redirige a login si no esta autenticado
 */

// Configurar codificación UTF-8 para toda la aplicación
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=utf-8');

// Definir URL base para recursos estaticos
$base_url = '/consejo_comunalv2.0.0';

session_start();

// Generar token CSRF para la sesion
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Cargar reglas de acceso RBAC
require_once __DIR__ . '/config/permissions.php';

// Verificar si esta logueado
$is_logged_in = isset($_SESSION['user_id']);

// Manejo de operaciones AJAX para backup (antes de cualquier salida)
if (isset($_GET['view']) && $_GET['view'] === 'backup' && (isset($_GET['mode']) || isset($_GET['action']))) {
    if (!$is_logged_in) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    require_once __DIR__ . '/src/controllers/backup.php';
    exit;
}

// Si no esta logueado, mostrar login
if (!$is_logged_in) {
    $title = 'Login - Consejo Comunal';
    $layout = 'simple';
    
    // Incluir header
    require_once __DIR__ . '/src/views/layout/header.php';
    
    // Incluir vista de login
    require_once __DIR__ . '/src/views/login.php';
    
    // Incluir footer
    require_once __DIR__ . '/src/views/layout/footer.php';
    exit;
}

// Si esta logueado, mostrar el panel
$views_dir = __DIR__ . '/src/views/';
$allowed_views = ['dashboard', 'personas', 'familias', 'viviendas', 'calles', 'manzana', 'calendar', 'statistics', 'backup', 'usuarios'];

// Obtener vista actual
$view = $_GET['view'] ?? 'dashboard';

// Validar vista
if (!in_array($view, $allowed_views)) {
    $view = 'dashboard';
}

// Definir página activa para el menú
$active_page = $view;

// Validar permisos RBAC para la vista solicitada
if (!validarAccesoVista($view)) {
    header("Location: {$base_url}/index.php?view=dashboard&error=unauthorized");
    exit;
}

// Definir título de vista
$viewTitles = [
    'dashboard' => 'Panel',
    'personas' => 'Personas',
    'familias' => 'Familias',
    'viviendas' => 'Viviendas',
    'calles' => 'Calles',
    'manzana' => 'Manzana',
    'calendar' => 'Calendario',
    'statistics' => 'Estadísticas',
    'backup' => 'Backup',
    'usuarios' => 'Usuarios'
];
$title = ($viewTitles[$view] ?? ucfirst($view)) . ' - Consejo Comunal';

// Cargar datos segun vista
$stats = [];
$personas = [];
$familias = [];
$viviendas = [];
$calles = [];
$manzana = [];

try {
    require_once __DIR__ . '/src/models/comunity_db.php';
    $db = new ComunityDb();
    $conn = $db->getConnection();
    
    // Obtener estadisticas
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM person");
    $stmt->execute();
    $stats['personas'] = $stmt->get_result()->fetch_assoc()['cnt'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM family");
    $stmt->execute();
    $stats['familias'] = $stmt->get_result()->fetch_assoc()['cnt'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM house");
    $stmt->execute();
    $stats['viviendas'] = $stmt->get_result()->fetch_assoc()['cnt'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM street");
    $stmt->execute();
    $stats['calles'] = $stmt->get_result()->fetch_assoc()['cnt'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM square");
    $stmt->execute();
    $stats['manzana'] = $stmt->get_result()->fetch_assoc()['cnt'];
    
// Obtener calles
    $stmt = $conn->prepare("SELECT * FROM street ORDER BY name_street ASC");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $calles[] = $r;
    }
    
    // Obtener manzanas
    $manzana = [];
    $stmt = $conn->prepare("
        SELECT s.*, st.name_street 
        FROM square s 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY s.codigo_square ASC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $manzana[] = $r;
    }
    
    // Obtener viviendas con manzana y calle
    $viviendas = [];
    $stmt = $conn->prepare("
        SELECT h.*, s.codigo_square, st.name_street 
        FROM house h 
        LEFT JOIN square s ON h.id_square = s.id_square 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY h.number_house ASC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $viviendas[] = $r;
    }
    
    // Obtener familias con vivienda, manzana y calle
    $familias = [];
    $stmt = $conn->prepare("
        SELECT f.*, h.number_house, s.codigo_square, st.name_street 
        FROM family f 
        LEFT JOIN house h ON f.id_house = h.id_house 
        LEFT JOIN square s ON h.id_square = s.id_square 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY f.id_family DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $familias[] = $r;
    }

    // Obtener total de eventos calendario si existe la tabla
    $stmt = $conn->prepare("SHOW TABLES LIKE 'calendar_events'");
    $stmt->execute();
    $eventsTableExists = $stmt->get_result()->num_rows > 0;
    $stats['eventos'] = 0;
    if ($eventsTableExists) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM calendar_events");
        $stmt->execute();
        $stats['eventos'] = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    }

    if ($view === 'statistics') {
        $topFamilies = [];
        $stmt = $conn->prepare(
            "SELECT f.id_family, f.surname_family, COUNT(p.id_person) AS members, h.number_house, s.codigo_square, st.name_street
            FROM family f
            LEFT JOIN person p ON f.id_family = p.id_family
            LEFT JOIN house h ON f.id_house = h.id_house
            LEFT JOIN square s ON h.id_square = s.id_square
            LEFT JOIN street st ON s.id_street = st.id_street
            GROUP BY f.id_family
            ORDER BY members DESC, f.surname_family ASC
            LIMIT 5"
        );
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $topFamilies[] = $r;
        }

        $upcomingEvents = [];
        if ($eventsTableExists) {
            $stmt = $conn->prepare("SELECT * FROM calendar_events WHERE event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC LIMIT 5");
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $upcomingEvents[] = $r;
            }
        }
    }
    
} catch (Exception $e) {
    $stmt = $conn->prepare("
        SELECT p.*, f.surname_family 
        FROM person p 
        LEFT JOIN family f ON p.id_family = f.id_family 
        ORDER BY p.id_person DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $personas[] = $r;
    }
    
    // Obtener familias
    $stmt = $conn->prepare("
        SELECT f.*, h.number_house, s.codigo_square, st.name_street 
        FROM family f 
        LEFT JOIN house h ON f.id_house = h.id_house 
        LEFT JOIN square s ON h.id_square = s.id_square 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY f.id_family DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $familias[] = $r;
    }
    
    // Obtener manzanas
    $manzana = [];
    $stmt = $conn->prepare("
        SELECT s.*, st.name_street 
        FROM square s 
        LEFT JOIN street st ON s.id_street = st.id_street 
        ORDER BY s.codigo_square ASC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $manzana[] = $r;
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Manejo de operaciones para manzana
if ($view === 'manzana' && isset($_GET['mode'])) {
    header('Content-Type: application/json');
    
    // Validar CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    $mode = $_GET['mode'];
    
    if ($mode === 'save') {
        try {
            $codigo_square = $_POST['codigo_square'] ?? '';
            $id_street = $_POST['id_street'] ?? '';
            $name_square = $_POST['name_square'] ?? '';
            $id_square = $_POST['id_square'] ?? null;
            
            if (empty($codigo_square) || empty($id_street) || empty($name_square)) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            if ($id_square) {
                // Update
                $stmt = $conn->prepare("UPDATE square SET codigo_square = ?, id_street = ?, name_square = ? WHERE id_square = ?");
                $stmt->bind_param('sisi', $codigo_square, $id_street, $name_square, $id_square);
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO square (codigo_square, id_street, name_square) VALUES (?, ?, ?)");
                $stmt->bind_param('sis', $codigo_square, $id_street, $name_square);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $id_square ? 'Manzana actualizada' : 'Manzana creada']);
            } else {
                throw new Exception('Error al guardar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($mode === 'get') {
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM square WHERE id_square = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode($result);
        exit;
    }
    
    if ($mode === 'delete') {
        try {
            $id = $_POST['id_square'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM square WHERE id_square = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Manzana eliminada']);
            } else {
                throw new Exception('Error al eliminar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Manejo de operaciones para calles
if ($view === 'calles' && isset($_GET['mode'])) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    $mode = $_GET['mode'];
    
    if ($mode === 'save') {
        try {
            $codigo_street = $_POST['codigo_street'] ?? '';
            $name_street = $_POST['name_street'] ?? '';
            $id_street = $_POST['id_street'] ?? null;
            
            if (empty($codigo_street) || empty($name_street)) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            if ($id_street) {
                $stmt = $conn->prepare("UPDATE street SET codigo_street = ?, name_street = ? WHERE id_street = ?");
                $stmt->bind_param('ssi', $codigo_street, $name_street, $id_street);
            } else {
                $stmt = $conn->prepare("INSERT INTO street (codigo_street, name_street) VALUES (?, ?)");
                $stmt->bind_param('ss', $codigo_street, $name_street);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $id_street ? 'Calle actualizada' : 'Calle creada']);
            } else {
                throw new Exception('Error al guardar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($mode === 'get') {
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM street WHERE id_street = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode($result);
        exit;
    }
    
    if ($mode === 'delete') {
        try {
            $id = $_POST['id_street'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM street WHERE id_street = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Calle eliminada']);
            } else {
                throw new Exception('Error al eliminar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Manejo de operaciones para familias
if ($view === 'familias' && isset($_GET['mode'])) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    $mode = $_GET['mode'];
    
    if ($mode === 'save') {
        try {
            $surname_family = $_POST['surname_family'] ?? '';
            $id_house = $_POST['id_house'] ?? '';
            $numero_familia = $_POST['numero_familia'] ?? '';
            $id_family = $_POST['id_family'] ?? null;
            
            if (empty($surname_family) || empty($id_house)) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            if ($id_family) {
                $stmt = $conn->prepare("UPDATE family SET surname_family = ?, id_house = ?, numero_familia = ? WHERE id_family = ?");
                $stmt->bind_param('sisi', $surname_family, $id_house, $numero_familia, $id_family);
            } else {
                $stmt = $conn->prepare("INSERT INTO family (surname_family, id_house, numero_familia) VALUES (?, ?, ?)");
                $stmt->bind_param('sis', $surname_family, $id_house, $numero_familia);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $id_family ? 'Familia actualizada' : 'Familia creada']);
            } else {
                throw new Exception('Error al guardar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($mode === 'get') {
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM family WHERE id_family = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode($result);
        exit;
    }
    
    if ($mode === 'delete') {
        try {
            $id = $_POST['id_family'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM family WHERE id_family = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Familia eliminada']);
            } else {
                throw new Exception('Error al eliminar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Manejo de operaciones para viviendas
if ($view === 'viviendas' && isset($_GET['mode'])) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    $mode = $_GET['mode'];
    
    if ($mode === 'save') {
        try {
            $number_house = $_POST['number_house'] ?? '';
            $id_square = $_POST['id_square'] ?? '';
            $id_house = $_POST['id_house'] ?? null;
            
            if (empty($number_house) || empty($id_square)) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            if ($id_house) {
                $stmt = $conn->prepare("UPDATE house SET number_house = ?, id_square = ? WHERE id_house = ?");
                $stmt->bind_param('sii', $number_house, $id_square, $id_house);
            } else {
                $stmt = $conn->prepare("INSERT INTO house (number_house, id_square) VALUES (?, ?)");
                $stmt->bind_param('si', $number_house, $id_square);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $id_house ? 'Vivienda actualizada' : 'Vivienda creada']);
            } else {
                throw new Exception('Error al guardar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($mode === 'get') {
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM house WHERE id_house = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode($result);
        exit;
    }
    
    if ($mode === 'delete') {
        try {
            $id = $_POST['id_house'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM house WHERE id_house = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Vivienda eliminada']);
            } else {
                throw new Exception('Error al eliminar: ' . $conn->error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Incluir header
require_once $views_dir . 'layout/header.php';

// Incluir vista
$view_file = $views_dir . $view . '.php';
if (file_exists($view_file)) {
    require_once $view_file;
} else {
    echo '<div class="alert alert-danger">Vista no encontrada</div>';
}

// Incluir footer
require_once $views_dir . 'layout/footer.php';
