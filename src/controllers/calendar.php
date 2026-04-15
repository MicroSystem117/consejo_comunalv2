<?php
require_once __DIR__ . "/../models/comunity_db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $db = new ComunityDb();
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// Crear tabla si no existe
$conn->query(
    "CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        event_date DATE NOT NULL,
        event_time VARCHAR(10) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$action = $_REQUEST['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $conn->prepare("SELECT * FROM calendar_events ORDER BY event_date ASC, event_time ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo json_encode(['status' => 'ok', 'data' => $events]);
    exit;
}

if ($action === 'get') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }
    $stmt = $conn->prepare("SELECT * FROM calendar_events WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    if ($event) {
        echo json_encode(['status' => 'ok', 'data' => $event]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Evento no encontrado']);
    }
    exit;
}

if (in_array($action, ['create', 'update', 'delete'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido']);
        exit;
    }
}

if ($action === 'create' || $action === 'update') {
    $title = trim($_POST['title'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = trim($_POST['event_time'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id = intval($_POST['id'] ?? 0);

    if (!$title || !$event_date) {
        echo json_encode(['status' => 'error', 'message' => 'Título y fecha son obligatorios']);
        exit;
    }

    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO calendar_events (title, event_date, event_time, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $title, $event_date, $event_time, $description);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else {
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID de evento inválido']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE calendar_events SET title = ?, event_date = ?, event_time = ?, description = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $title, $event_date, $event_time, $description, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID de evento inválido']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM calendar_events WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
