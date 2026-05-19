<?php
// scripts/unify_databases.php
// Usage: php scripts/unify_databases.php
// This script copies the legacy credentials schema into the unified comunity database.

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$sourceDb = 'credentials';
$targetDb = 'comunity';

$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    fwrite(STDERR, "Connection error: " . $mysqli->connect_error . "\n");
    exit(1);
}

$mysqli->set_charset('utf8mb4');

if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    fwrite(STDERR, "Unable to create target database: " . $mysqli->error . "\n");
    exit(1);
}

if (!$mysqli->query("SET FOREIGN_KEY_CHECKS=0")) {
    fwrite(STDERR, "Unable to disable foreign key checks: " . $mysqli->error . "\n");
    exit(1);
}

if (!$mysqli->query("USE `{$targetDb}`")) {
    fwrite(STDERR, "Unable to switch to target DB: " . $mysqli->error . "\n");
    exit(1);
}

$tablesResult = $mysqli->query("SHOW TABLES FROM `{$sourceDb}`");
if (!$tablesResult) {
    fwrite(STDERR, "Unable to read source database tables: " . $mysqli->error . "\n");
    exit(1);
}

$tables = [];
while ($row = $tablesResult->fetch_row()) {
    $tables[] = $row[0];
}

if (empty($tables)) {
    echo "No tables found in {$sourceDb}.\n";
    exit(0);
}

foreach ($tables as $table) {
    echo "Migrating table: {$table}\n";

    $createResult = $mysqli->query("SHOW CREATE TABLE `{$sourceDb}`.`{$table}`");
    if (!$createResult) {
        fwrite(STDERR, "Unable to read CREATE for {$table}: " . $mysqli->error . "\n");
        continue;
    }

    $row = $createResult->fetch_assoc();
    $createSql = $row['Create Table'];
    $createSql = str_replace("`{$sourceDb}`.", '`', $createSql);
    $createSql = str_replace("{$sourceDb}.", '', $createSql);

    $checkTarget = $mysqli->query("SHOW TABLES FROM `{$targetDb}` LIKE '{$table}'");
    if ($checkTarget && $checkTarget->num_rows > 0) {
        echo "  Table {$table} already exists in {$targetDb}, skipping creation.\n";
    } else {
        if (!$mysqli->query("USE `{$targetDb}`")) {
            fwrite(STDERR, "Unable to switch to target DB: " . $mysqli->error . "\n");
            continue;
        }
        if (!$mysqli->query($createSql)) {
            fwrite(STDERR, "Unable to create table {$table}: " . $mysqli->error . "\n");
            continue;
        }
        echo "  Created table {$table} in {$targetDb}.\n";
    }

    if (!$mysqli->query("INSERT IGNORE INTO `{$targetDb}`.`{$table}` SELECT * FROM `{$sourceDb}`.`{$table}`")) {
        fwrite(STDERR, "Unable to copy data for {$table}: " . $mysqli->error . "\n");
        continue;
    }
    echo "  Copied data for {$table}.\n";
}

if (!$mysqli->query("SET FOREIGN_KEY_CHECKS=1")) {
    fwrite(STDERR, "Unable to enable foreign key checks: " . $mysqli->error . "\n");
}

echo "\nMigration complete. Review the unified database and then remove the legacy {$sourceDb} database if desired.\n";
$mysqli->close();
