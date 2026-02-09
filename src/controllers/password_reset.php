<?php
// Password reset flow using security questions and one-time tokens.
require_once __DIR__ . "/../models/dbuser.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

// CSRF Validation
function validateCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token || empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF invalido']);
        return false;
    }
    return true;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metodo no soportado']);
    exit;
}

$action = $_POST['action'] ?? '';
$dbUser = new DbUser();
$conn = $dbUser->getConnection();

// Rate-limiting helpers (same policy: 5 attempts -> 1 hour)
function get_client_ip_pr() {
    return $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '0.0.0.0');
}

function check_lock_pr($conn, $ci, $action, $ip) {
    $stmt = $conn->prepare("SELECT locked_until FROM auth_attempts WHERE (ci = ? AND action = ?) OR (ip = ? AND action = ?) LIMIT 1");
    $stmt->bind_param('isss', $ci, $action, $ip, $action);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($row && $row['locked_until']) {
        $locked = new DateTime($row['locked_until']);
        $nowdt = new DateTime();
        if ($nowdt < $locked) return $locked;
    }
    return false;
}

function record_failure_pr($conn, $ci, $action, $ip) {
    $now = (new DateTime())->format('Y-m-d H:i:s');
    if ($ci > 0) {
        $stmt = $conn->prepare("SELECT attempts FROM auth_attempts WHERE ci = ? AND action = ? LIMIT 1");
        $stmt->bind_param('is', $ci, $action);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row) {
            $attempts = $row['attempts'] + 1;
            $locked_until = null;
            if ($attempts >= 5) $locked_until = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE auth_attempts SET attempts = ?, last_attempt = ?, locked_until = ? WHERE ci = ? AND action = ?");
            $stmt->bind_param('issis', $attempts, $now, $locked_until, $ci, $action);
            $stmt->execute();
            $stmt->close();
        } else {
            $attempts = 1;
            $locked_until = null;
            $stmt = $conn->prepare("INSERT INTO auth_attempts (ci, ip, attempts, last_attempt, locked_until, action) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('isisss', $ci, $ip, $attempts, $now, $locked_until, $action);
            $stmt->execute();
            $stmt->close();
        }
    }
    $stmt = $conn->prepare("SELECT attempts FROM auth_attempts WHERE ip = ? AND action = ? LIMIT 1");
    $stmt->bind_param('ss', $ip, $action);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($row) {
        $attempts = $row['attempts'] + 1;
        $locked_until = null;
        if ($attempts >= 5) $locked_until = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE auth_attempts SET attempts = ?, last_attempt = ?, locked_until = ? WHERE ip = ? AND action = ?");
        $stmt->bind_param('issss', $attempts, $now, $locked_until, $ip, $action);
        $stmt->execute();
        $stmt->close();
    } else {
        $attempts = 1;
        $locked_until = null;
        $stmt = $conn->prepare("INSERT INTO auth_attempts (ci, ip, attempts, last_attempt, locked_until, action) VALUES (NULL,?,?,?,?,?)");
        $stmt->bind_param('issss', $ip, $attempts, $now, $locked_until, $action);
        $stmt->execute();
        $stmt->close();
    }
}

function reset_attempts_pr($conn, $ci, $action, $ip) {
    $stmt = $conn->prepare("DELETE FROM auth_attempts WHERE (ci = ? AND action = ?) OR (ip = ? AND action = ?)");
    $stmt->bind_param('isss', $ci, $action, $ip, $action);
    $stmt->execute();
    $stmt->close();
}

function fetch_user_by_ci($conn, $ci) {
    $stmt = $conn->prepare("SELECT id_user, name FROM `user` WHERE ci = ?");
    $stmt->bind_param('i', $ci);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $user;
}

if ($action === 'request') {
    $ci = (int) ($_POST['ci'] ?? 0);
    if (!$ci) {
        echo json_encode(['status' => 'error', 'message' => 'Cedula requerida']);
        exit;
    }
    $user = fetch_user_by_ci($conn, $ci);
    if (!$user) {
        echo json_encode(['status' => 'ok', 'questions' => []]);
        exit;
    }
    $uid = $user['id_user'];
    $stmt = $conn->prepare("SELECT QuestOne, QuestTwo FROM SecQuestion WHERE id_user = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $q = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    $questions = [];
    if ($q) {
        $questions = [ 'q1' => $q['QuestOne'], 'q2' => $q['QuestTwo'] ];
    }
    echo json_encode(['status' => 'ok', 'questions' => $questions]);
    exit;
}

if ($action === 'verify') {
    // Validate CSRF
    if (!validateCsrfToken()) exit;
    
    $ci = (int) ($_POST['ci'] ?? 0);
    $a1 = trim($_POST['answerOne'] ?? '');
    $a2 = trim($_POST['answerTwo'] ?? '');
    if (!$ci || $a1 === '' || $a2 === '') {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }
    $ip = get_client_ip_pr();
    $locked = check_lock_pr($conn, $ci, 'password_reset', $ip);
    if ($locked) {
        $remaining = (new DateTime())->diff($locked);
        $mins = ($remaining->h * 60) + $remaining->i + ($remaining->s > 0 ? 1 : 0);
        echo json_encode(['status' => 'error', 'message' => "Demasiados intentos. Intenta en {$mins} minutos."]);
        exit;
    }
    $user = fetch_user_by_ci($conn, $ci);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }
    $uid = $user['id_user'];
    $stmt = $conn->prepare("SELECT AnswerOne, AnswerTwo FROM SecQuestion WHERE id_user = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Preguntas no configuradas']);
        exit;
    }

    $ok1 = password_verify($a1, $row['AnswerOne']);
    $ok2 = password_verify($a2, $row['AnswerTwo']);
    if ($ok1 && $ok2) {
        // create token
        $token = bin2hex(random_bytes(32));
        $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?,?,?)");
        $stmt->bind_param('iss', $uid, $token, $expires);
        $stmt->execute();
        $stmt->close();
        // reset attempts on success
        reset_attempts_pr($conn, $ci, 'password_reset', $ip);
        echo json_encode(['status' => 'success', 'message' => 'Respuestas correctas', 'token' => $token]);
    } else {
        // record failure
        record_failure_pr($conn, $ci, 'password_reset', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Respuestas incorrectas']);
    }
    exit;
}

// Offline-friendly: verify answers and reset password immediately (no email/token)
if ($action === 'reset') {
    // Validate CSRF
    if (!validateCsrfToken()) exit;
    
    $ci = (int) ($_POST['ci'] ?? 0);
    $a1 = trim($_POST['answerOne'] ?? '');
    $a2 = trim($_POST['answerTwo'] ?? '');
    $newpass = $_POST['new_password'] ?? '';
    if (!$ci || $a1 === '' || $a2 === '' || $newpass === '') {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }
    $user = fetch_user_by_ci($conn, $ci);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }
    $ip = get_client_ip_pr();
    $locked = check_lock_pr($conn, $ci, 'password_reset', $ip);
    if ($locked) {
        $remaining = (new DateTime())->diff($locked);
        $mins = ($remaining->h * 60) + $remaining->i + ($remaining->s > 0 ? 1 : 0);
        echo json_encode(['status' => 'error', 'message' => "Demasiados intentos. Intenta en {$mins} minutos."]);
        exit;
    }
    $uid = $user['id_user'];
    $stmt = $conn->prepare("SELECT AnswerOne, AnswerTwo FROM SecQuestion WHERE id_user = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Preguntas no configuradas']);
        exit;
    }
    $ok1 = password_verify($a1, $row['AnswerOne']);
    $ok2 = password_verify($a2, $row['AnswerTwo']);
    if (!($ok1 && $ok2)) {
        // record failure
        record_failure_pr($conn, $ci, 'password_reset', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Respuestas incorrectas']);
        exit;
    }
    // validate new password
    function password_meets_criteria_local2($p) {
        if (strlen($p) < 8) return false;
        if (!preg_match('/[A-Z]/', $p)) return false;
        if (!preg_match('/[a-z]/', $p)) return false;
        if (!preg_match('/[0-9]/', $p)) return false;
        if (!preg_match('/[^A-Za-z0-9]/', $p)) return false;
        return true;
    }
    if (!password_meets_criteria_local2($newpass)) {
        echo json_encode(['status' => 'error', 'message' => 'Contrasena no cumple requisitos']);
        exit;
    }
    $hash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE `user` SET pass = ? WHERE id_user = ?");
    $stmt->bind_param('si', $hash, $uid);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        // reset attempts on success
        reset_attempts_pr($conn, $ci, 'password_reset', $ip);
        echo json_encode(['status' => 'success', 'message' => 'Contrasena actualizada']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error actualizando contrasena']);
    }
    exit;
}

if ($action === 'complete') {
    // Validate CSRF
    if (!validateCsrfToken()) exit;
    
    $ci = (int) ($_POST['ci'] ?? 0);
    $token = $_POST['token'] ?? '';
    $newpass = $_POST['new_password'] ?? '';
    if (!$ci || !$token || !$newpass) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }
    // validate password strength (reuse same rules)
    function password_meets_criteria_local($p) {
        if (strlen($p) < 8) return false;
        if (!preg_match('/[A-Z]/', $p)) return false;
        if (!preg_match('/[a-z]/', $p)) return false;
        if (!preg_match('/[0-9]/', $p)) return false;
        if (!preg_match('/[^A-Za-z0-9]/', $p)) return false;
        return true;
    }
    if (!password_meets_criteria_local($newpass)) {
        echo json_encode(['status' => 'error', 'message' => 'Contrasena no cumple requisitos']);
        exit;
    }
    $user = fetch_user_by_ci($conn, $ci);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }
    $uid = $user['id_user'];
    $stmt = $conn->prepare("SELECT id, expires_at, used FROM password_reset_tokens WHERE token = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param('si', $token, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Token invalido']);
        exit;
    }
    if ($row['used']) {
        echo json_encode(['status' => 'error', 'message' => 'Token ya usado']);
        exit;
    }
    $now = new DateTime();
    $exp = new DateTime($row['expires_at']);
    if ($now > $exp) {
        echo json_encode(['status' => 'error', 'message' => 'Token expirado']);
        exit;
    }
    // update password
    $hash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE `user` SET pass = ? WHERE id_user = ?");
    $stmt->bind_param('si', $hash, $uid);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        // mark token used
        $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Contrasena actualizada']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error actualizando contrasena']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Accion no valida']);

?>
