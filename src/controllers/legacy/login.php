<?php
// Legacy login handler moved to /src/controllers/legacy/
// Original file preserved for reference. Prefer using `auth.php`.
require_once __DIR__ . "/../models/dbuser.php";

header('Content-Type: application/json; charset=utf-8');
session_start();

$dbUser = new DbUser();
$conn = $dbUser->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$ci = isset($_POST['ci']) ? intval($_POST['ci']) : 0;
$password = $_POST['pass'] ?? '';

if ($ci <= 0 || $password === '') {
    echo json_encode(["status" => "error", "message" => "Campos requeridos"]);
    exit;
}

$stmt = $conn->prepare("SELECT id_user, pass, id_level, name, surname FROM `user` WHERE ci = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "DB error"]);
    exit;
}

$stmt->bind_param("i", $ci);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $hash = $row['pass'];

    if (password_verify($password, $hash)) {
        $_SESSION['user'] = [
            'id' => (int)$row['id_user'],
            'ci' => $ci,
            'level' => (int)$row['id_level'],
            'name' => $row['name'] ?? ''
        ];

        echo json_encode(["status" => "success", "message" => "Bienvenido"]);
        $stmt->close();
        exit;
    }
}

echo json_encode(["status" => "error", "message" => "Verifique sus credenciales"]);
$stmt->close();
