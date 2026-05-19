<?php
// src/controllers/personas_data.php
// Devuelve un array $personas para la vista personas.php
require_once __DIR__ . '/../models/comunity_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function obtenerTodasLasPersonas() {
    $db = new ComunityDb();
    $conn = $db->getConnection();

    $userLevel = (int) ($_SESSION['id_level'] ?? 3);
    if ($userLevel === 3) {
        $userCi = intval($_SESSION['user_ci'] ?? 0);
        if (!$userCi) {
            return [];
        }
        $stmt = $conn->prepare("SELECT p.id_person, p.name_person, p.ci_person, p.birth_person, p.id_family, f.surname_family FROM person p LEFT JOIN family f ON p.id_family = f.id_family WHERE p.ci_person = ? ORDER BY p.id_person DESC");
        $stmt->bind_param('i', $userCi);
    } else {
        $stmt = $conn->prepare("SELECT p.id_person, p.name_person, p.ci_person, p.birth_person, p.id_family, f.surname_family FROM person p LEFT JOIN family f ON p.id_family = f.id_family ORDER BY p.id_person DESC");
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    return $rows;
}
