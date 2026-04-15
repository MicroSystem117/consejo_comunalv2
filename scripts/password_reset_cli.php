<?php
// Usage: php scripts/password_reset_cli.php action key=value ...
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$argv = isset($argv) ? $argv : [];
$action = $argv[1] ?? 'request';
$_POST = [];
$_POST['action'] = $action;
for ($i = 2; $i < count($argv); $i++) {
    $part = $argv[$i];
    if (strpos($part, '=') !== false) {
        list($k, $v) = explode('=', $part, 2);
        $_POST[$k] = $v;
    }
}
require_once __DIR__ . "/../src/controllers/password_reset.php";

?>
