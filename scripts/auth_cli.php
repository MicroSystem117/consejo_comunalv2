<?php
// Usage: php scripts/auth_cli.php action ci pass
// Simula un POST a src/controllers/auth.php en un proceso separado.
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
// parse args: first is action, following can be key=value pairs
$argv = isset($argv) ? $argv : [];
$action = $argv[1] ?? 'login';
$_POST = [];
$_POST['action'] = $action;
for ($i = 2; $i < count($argv); $i++) {
	$part = $argv[$i];
	if (strpos($part, '=') !== false) {
		list($k, $v) = explode('=', $part, 2);
		$_POST[$k] = $v;
	}
}
// Include the controller; it will echo JSON
require_once __DIR__ . "/../src/controllers/auth.php";

?>
