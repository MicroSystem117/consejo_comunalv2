<?php
// src/controllers/personas_data.php
// Devuelve un array $personas para la vista personas.php
require_once __DIR__ . '/../models/comunity_db.php';

function obtenerTodasLasPersonas() {
    $db = new ComunityDb();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT p.id_person, p.name_person, p.ci_person, p.birth_person, p.id_family, f.surname_family FROM person p LEFT JOIN family f ON p.id_family = f.id_family ORDER BY p.id_person DESC");
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    return $rows;
}
