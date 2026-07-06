<?php

function splitSqlStatements($sql) {
    $statements = [];
    $current = '';
    $length = strlen($sql);
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($inLineComment) {
            $current .= $char;
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            $current .= $char;
            if ($char === '*' && $next === '/') {
                $current .= $next;
                $i++;
                $inBlockComment = false;
            }
            continue;
        }

        if ($inSingleQuote) {
            $current .= $char;
            if ($char === "'" && ($sql[$i - 1] ?? '') !== '\\') {
                $inSingleQuote = false;
            }
            continue;
        }

        if ($inDoubleQuote) {
            $current .= $char;
            if ($char === '"' && ($sql[$i - 1] ?? '') !== '\\') {
                $inDoubleQuote = false;
            }
            continue;
        }

        if ($inBacktick) {
            $current .= $char;
            if ($char === '`') {
                $inBacktick = false;
            }
            continue;
        }

        if ($char === '-' && $next === '-') {
            $current .= '--';
            $inLineComment = true;
            $i++;
            continue;
        }

        if ($char === '/' && $next === '*') {
            $current .= '/*';
            $inBlockComment = true;
            $i++;
            continue;
        }

        if ($char === "'") {
            $inSingleQuote = true;
            $current .= $char;
            continue;
        }

        if ($char === '"') {
            $inDoubleQuote = true;
            $current .= $char;
            continue;
        }

        if ($char === '`') {
            $inBacktick = true;
            $current .= $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim($current);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    $tail = trim($current);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function importSqlFile($filePath, $host, $user, $pass, $dbName = null, $options = []) {
    if (!is_file($filePath)) {
        throw new RuntimeException('El archivo SQL no existe.');
    }

    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer el archivo SQL.');
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        throw new RuntimeException('No se pudo conectar al servidor MySQL: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    $targetDb = !empty($dbName) ? $dbName : 'comunity';
    if (!$conn->select_db($targetDb)) {
        if (!$conn->query("CREATE DATABASE IF NOT EXISTS `{$targetDb}`")) {
            throw new RuntimeException('No se pudo crear la base de datos: ' . $conn->error);
        }
        if (!$conn->select_db($targetDb)) {
            throw new RuntimeException('No se pudo seleccionar la base de datos: ' . $conn->error);
        }
    }

    $allowSchema = !empty($options['allow_schema']);
    $debug = !empty($options['debug']);
    $debugLog = __DIR__ . '/../../tmp/import_log.txt';

    // Disable foreign key checks during import
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    $statements = splitSqlStatements($sql);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }

        // Strip leading SQL comments (single-line --, # and block /* */) that may
        // appear before a real statement on the same chunk produced by the splitter.
        $statement = preg_replace('/^(?:\s*(?:--[^\n]*|#.*|\/\*[\s\S]*?\*\/))+\s*/i', '', $statement);
        if ($statement === '') {
            continue;
        }
        // If schema changes are not allowed, skip statements that change schema
        if (!$allowSchema) {
            if (preg_match('/^\s*(SET|USE|DROP\s+DATABASE|CREATE\s+DATABASE|DROP\s+TABLE|ALTER\s+TABLE|LOCK\s+TABLES|UNLOCK\s+TABLES|CREATE\s+INDEX|CREATE\s+VIEW)/i', $statement)) {
                continue;
            }

            // If attempting to create a table and it already exists, skip it
            if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_.-]+)`?/i', $statement, $matches)) {
                $tableName = $matches[1] ?? null;
                if ($tableName) {
                    $check = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
                    if ($check && $check->num_rows > 0) {
                        $check->free();
                        continue;
                    }
                    if ($check) {
                        $check->free();
                    }
                }
            }

            // Convert INSERT to INSERT IGNORE to avoid duplicate key failures when not replacing schema
            if (preg_match('/^INSERT\s+INTO/i', $statement)) {
                $statement = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $statement, 1);
            }
        }

        if ($debug) {
            @file_put_contents($debugLog, "EXECUTING: " . substr($statement,0,400) . "\n", FILE_APPEND);
        }

        if (!$conn->query($statement)) {
            $err = $conn->error;
            if ($debug) {
                @file_put_contents($debugLog, "ERROR: " . $err . "\n", FILE_APPEND);
            }
            error_log('SQL import failed: ' . $err . ' | ' . substr($statement, 0, 400));
            // Re-enable FK checks before throwing
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            throw new RuntimeException('Error al ejecutar la sentencia SQL: ' . $err . ' | ' . substr($statement, 0, 200));
        } else {
            if ($debug) {
                @file_put_contents($debugLog, "OK\n", FILE_APPEND);
            }
        }
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    $conn->close();
}
