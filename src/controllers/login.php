<?php
// Deprecated duplicate login handler left in place for compatibility.
// Use `src/controllers/auth.php` (action=login) as the canonical login endpoint.
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'error', 'message' => 'Endpoint obsoleto. Use action=login on src/controllers/auth.php']);
exit;
