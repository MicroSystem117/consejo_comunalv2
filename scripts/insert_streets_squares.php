<?php
/**
 * Script para insertar calles y manzanas en la base de datos
 * Lógica: Cada calle tiene 2 manzanas, las manzanas se superponen entre calles consecutivas
 */

require_once __DIR__ . '/../config/DataBaseManager.php';

$db = DataBase::connect('comunity');

// Insertar calles (1 al 7)
$streets = [
    ['id' => 1, 'name' => 'Calle 1'],
    ['id' => 2, 'name' => 'Calle 2'],
    ['id' => 3, 'name' => 'Calle 3'],
    ['id' => 4, 'name' => 'Calle 4'],
    ['id' => 5, 'name' => 'Calle 5'],
    ['id' => 6, 'name' => 'Calle 6'],
    ['id' => 7, 'name' => 'Calle 7'],
];

echo "Insertando calles...\n";
foreach ($streets as $street) {
    $stmt = $db->prepare("INSERT IGNORE INTO street (id_street, name_street) VALUES (?, ?)");
    $stmt->bind_param('is', $street['id'], $street['name']);
    $stmt->execute();
    $stmt->close();
    echo "  - {$street['name']}\n";
}

// Insertar manzanas por calle
$squares = [
    // Calle 1: Manzana 16 y 17
    ['id_street' => 1, 'codigo' => 'M-16'],
    ['id_street' => 1, 'codigo' => 'M-17'],
    // Calle 2: Manzana 17 y 18
    ['id_street' => 2, 'codigo' => 'M-17'],
    ['id_street' => 2, 'codigo' => 'M-18'],
    // Calle 3: Manzana 18 y 19
    ['id_street' => 3, 'codigo' => 'M-18'],
    ['id_street' => 3, 'codigo' => 'M-19'],
    // Calle 4: Manzana 19 y 20
    ['id_street' => 4, 'codigo' => 'M-19'],
    ['id_street' => 4, 'codigo' => 'M-20'],
    // Calle 5: Manzana 20 y 21
    ['id_street' => 5, 'codigo' => 'M-20'],
    ['id_street' => 5, 'codigo' => 'M-21'],
    // Calle 6: Manzana 21 y 22
    ['id_street' => 6, 'codigo' => 'M-21'],
    ['id_street' => 6, 'codigo' => 'M-22'],
    // Calle 7: Manzana 22 y 23
    ['id_street' => 7, 'codigo' => 'M-22'],
    ['id_street' => 7, 'codigo' => 'M-23'],
];

echo "\nInsertando manzanas...\n";
foreach ($squares as $square) {
    $stmt = $db->prepare("INSERT IGNORE INTO square (id_street, codigo_square) VALUES (?, ?)");
    $stmt->bind_param('is', $square['id_street'], $square['codigo']);
    $stmt->execute();
    $stmt->close();
    echo "  - Calle {$square['id_street']}: {$square['codigo']}\n";
}

// Verificar los datos
echo "\nDatos insertados:\n";
$result = $db->query("
    SELECT 
        s.name_street AS Calle,
        GROUP_CONCAT(sq.codigo_square ORDER BY sq.id_square SEPARATOR ', ') AS Manzanos
    FROM street s
    LEFT JOIN square sq ON s.id_street = sq.id_street
    GROUP BY s.id_street
    ORDER BY s.id_street
");

while ($row = $result->fetch_assoc()) {
    echo "  {$row['Calle']}: {$row['Manzanos']}\n";
}

echo "\n¡Listo!\n";
