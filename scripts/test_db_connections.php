<?php
require_once __DIR__ . "/../src/models/dbuser.php";
require_once __DIR__ . "/../src/models/comunity_db.php";

function listTables($mysqli) {
    $out = [];
    $res = $mysqli->query("SHOW TABLES");
    if ($res) {
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            $out[] = $row[0];
        }
        $res->free();
    } else {
        $out[] = 'ERROR: '.$mysqli->error;
    }
    return $out;
}

try {
    echo "Checking unified comunity DB via DbUser...\n";
    $u = new DbUser();
    $conn1 = $u->getConnection();
    if ($conn1->connect_error) throw new Exception('comunity connect error: '.$conn1->connect_error);
    $res = $conn1->query("SELECT DATABASE() AS dbname");
    $db = $res ? $res->fetch_assoc()['dbname'] : '(unknown)';
    echo "Connected to: $db\n";
    $tables = listTables($conn1);
    echo "Tables (unified): " . implode(', ', array_slice($tables,0,10)) . "\n\n";

    echo "Checking comunity DB via ComunityDb...\n";
    $c = new ComunityDb();
    $conn2 = $c->getConnection();
    if ($conn2->connect_error) throw new Exception('comunity connect error: '.$conn2->connect_error);
    $res2 = $conn2->query("SELECT DATABASE() AS dbname");
    $db2 = $res2 ? $res2->fetch_assoc()['dbname'] : '(unknown)';
    echo "Connected to: $db2\n";
    $tables2 = listTables($conn2);
    echo "Tables (comunity): " . implode(', ', array_slice($tables2,0,10)) . "\n\n";

    echo "Done.\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(1);
}

?>
