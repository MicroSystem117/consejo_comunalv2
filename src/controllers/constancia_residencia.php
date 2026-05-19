<?php
// src/controllers/constancia_residencia.php
// Controlador para generar la Constancia de Residencia en PDF

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/comunity_db.php';

use Mpdf\Mpdf;

function obtenerDatosResidencia($id_person) {
    $db = new ComunityDb();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('
        SELECT p.name_person, p.ci_person, f.surname_family, s.name_street, sq.codigo_square, h.number_house
        FROM person p
        JOIN family f ON p.id_family = f.id_family
        JOIN house h ON f.id_house = h.id_house
        JOIN square sq ON h.id_square = sq.id_square
        JOIN street s ON sq.id_street = s.id_street
        WHERE p.id_person = ?
    ');
    $stmt->bind_param('i', $id_person);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row;
}

function generarConstanciaResidenciaPDF($id_person) {
    $datos = obtenerDatosResidencia($id_person);
    if (!$datos) {
        die('Datos no encontrados para la persona.');
    }
    extract(['person' => $datos]);
    ob_start();
    include __DIR__ . '/../views/pdf_template.php';
    $html = ob_get_clean();

    $tmpPath = realpath(__DIR__ . '/../../tmp');
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'Letter',
        'default_font_size' => 10,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_left' => 15,
        'margin_right' => 15,
        'default_font' => 'Arial',
        'tempDir' => $tmpPath ?: __DIR__ . '/../../tmp',
    ]);
    $mpdf->WriteHTML($html);
    $mpdf->Output('constancia_residencia.pdf', 'I');
    exit;
}

// Ejemplo de uso: /src/controllers/constancia_residencia.php?id=123
if (isset($_GET['id'])) {
    generarConstanciaResidenciaPDF((int)$_GET['id']);
} else {
    echo 'ID de persona no especificado.';
}
