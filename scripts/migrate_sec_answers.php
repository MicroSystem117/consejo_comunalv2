<?php
// Migration script: hash existing SecQuestion answers if they are stored in plain text.
// Usage: php scripts/migrate_sec_answers.php

require_once __DIR__ . "/../src/models/dbuser.php";

$dbUser = new DbUser();
$conn = $dbUser->getConnection();

echo "Starting migration of SecQuestion answers...\n";

$q = $conn->query("SELECT id_SecQuest, AnswerOne, AnswerTwo FROM SecQuestion");
if (!$q) {
    echo "Failed to read SecQuestion: " . $conn->error . "\n";
    exit(1);
}

$count = 0;
while ($row = $q->fetch_assoc()) {
    $id = $row['id_SecQuest'];
    $a1 = $row['AnswerOne'];
    $a2 = $row['AnswerTwo'];
    $updated = false;

    // Skip if already looks like a password_hash (starts with $2y$ or $argon2i$)
    if ($a1 && !preg_match('/^\$2y\$|^\$2a\$|^\$argon2/', $a1)) {
        $ha1 = password_hash($a1, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE SecQuestion SET AnswerOne = ? WHERE id_SecQuest = ?");
        $stmt->bind_param('si', $ha1, $id);
        $stmt->execute();
        $stmt->close();
        $updated = true;
    }
    if ($a2 && !preg_match('/^\$2y\$|^\$2a\$|^\$argon2/', $a2)) {
        $ha2 = password_hash($a2, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE SecQuestion SET AnswerTwo = ? WHERE id_SecQuest = ?");
        $stmt->bind_param('si', $ha2, $id);
        $stmt->execute();
        $stmt->close();
        $updated = true;
    }

    if ($updated) $count++;
}

echo "Migration complete. Rows updated: $count\n";

?>
