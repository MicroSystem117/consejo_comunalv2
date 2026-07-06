<?php
// Direct migration script: connect to unified 'comunity' DB and hash SecQuestion answers.
// Usage: php scripts/migrate_sec_answers_direct.php
// Run this after the credentials schema has been merged into comunity.

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'comunity';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    echo "Connect failed: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

echo "Connected to unified {$db} DB\n";

$res = $mysqli->query("SELECT id_SecQuest, AnswerOne, AnswerTwo FROM SecQuestion");
if (!$res) {
    echo "Query failed: " . $mysqli->error . "\n";
    exit(1);
}

$count = 0;
while ($row = $res->fetch_assoc()) {
    $id = $row['id_SecQuest'];
    $a1 = $row['AnswerOne'];
    $a2 = $row['AnswerTwo'];
    $updated = false;

    if ($a1 && !preg_match('/^\$2y\$|^\$2a\$|^\$argon2/', $a1)) {
        $ha1 = password_hash($a1, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE SecQuestion SET AnswerOne = ? WHERE id_SecQuest = ?");
        $stmt->bind_param('si', $ha1, $id);
        $stmt->execute();
        $stmt->close();
        $updated = true;
    }
    if ($a2 && !preg_match('/^\$2y\$|^\$2a\$|^\$argon2/', $a2)) {
        $ha2 = password_hash($a2, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE SecQuestion SET AnswerTwo = ? WHERE id_SecQuest = ?");
        $stmt->bind_param('si', $ha2, $id);
        $stmt->execute();
        $stmt->close();
        $updated = true;
    }

    if ($updated) $count++;
}

echo "Migration complete. Rows updated: $count\n";

$mysqli->close();

?>
