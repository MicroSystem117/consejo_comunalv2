<?php
require_once __DIR__ . "/../models/dbuser.php";
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

try {
    $dbUser = new DbUser();
    $conn = $dbUser->getConnection();
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'No se pudo conectar a la base de datos: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metodo no soportado']);
    exit;
}

$action = $_POST['action'] ?? 'login';

// Base URL for redirects
$base_url = '/consejo_comunalv2.0.0';

// Logout action
if ($action === 'logout') {
    // Regenerate session ID to prevent session hijacking
    session_regenerate_id(true);
    // Clear all session variables
    $_SESSION = [];
    // Destroy the session
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Sesion cerrada', 'redirect' => $base_url . '/']);
    exit;
}

// Change password action
if ($action === 'change_password') {
    // Validate CSRF
    if (!validateCsrfToken()) {
        error_log("CSRF validation failed for change_password");
        exit;
    }
    
    // Check if user is logged in
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        error_log("User not logged in for change_password");
        echo json_encode(['status' => 'error', 'message' => 'Usuario no identificado']);
        exit;
    }
    
    error_log("Change password for user_id: " . $user_id);
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$current_password || !$new_password || !$confirm_password) {
        error_log("Missing password fields - current: " . strlen($current_password) . ", new: " . strlen($new_password) . ", confirm: " . strlen($confirm_password));
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son requeridos']);
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        error_log("Passwords don't match");
        echo json_encode(['status' => 'error', 'message' => 'Las contraseñas no coinciden']);
        exit;
    }
    
    if (strlen($new_password) < 6) {
        error_log("New password too short: " . strlen($new_password));
        echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }
    
    // Validate password strength
    function password_meets_criteria($p) {
        if (strlen($p) < 8) return 'La contraseña debe tener al menos 8 caracteres';
        if (!preg_match('/[A-Z]/', $p)) return 'La contraseña debe tener al menos una mayúscula';
        if (!preg_match('/[a-z]/', $p)) return 'La contraseña debe tener al menos una minúscula';
        if (!preg_match('/[0-9]/', $p)) return 'La contraseña debe tener al menos un número';
        if (!preg_match('/[^A-Za-z0-9]/', $p)) return 'La contraseña debe tener al menos un carácter especial';
        return null;
    }
    
    $strength_error = password_meets_criteria($new_password);
    if ($strength_error) {
        error_log("Password strength error: " . $strength_error);
        echo json_encode(['status' => 'error', 'message' => $strength_error]);
        exit;
    }
    
    error_log("All validation passed, querying database for user: " . $user_id);
    
    // Get current password hash
    $stmt = $conn->prepare("SELECT pass FROM `user` WHERE id_user = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos']);
        exit;
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if (!$res) {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Error al consultar usuario']);
        exit;
    }
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $stored_hash = $row['pass'];
        error_log("Found stored hash for user: " . substr($stored_hash, 0, 10) . "...");
        
        // Check if the stored hash is a valid password hash
        if (!password_get_info($stored_hash)['algo']) {
            error_log("Stored password is not a password_hash, may be MD5 or plain");
        }
        
        if (password_verify($current_password, $stored_hash)) {
            error_log("Current password verified successfully");
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            error_log("New hash generated: " . substr($new_hash, 0, 10) . "...");
            
            $update = $conn->prepare("UPDATE `user` SET pass = ? WHERE id_user = ?");
            if (!$update) {
                error_log("Update prepare failed: " . $conn->error);
                echo json_encode(['status' => 'error', 'message' => 'Error al preparar actualización']);
                exit;
            }
            
            $update->bind_param('si', $new_hash, $user_id);
            if ($update->execute()) {
                error_log("Password updated successfully for user: " . $user_id);
                echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada correctamente']);
            } else {
                error_log("Update execute failed: " . $update->error);
                echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la contraseña: ' . $update->error]);
            }
            $update->close();
        } else {
            error_log("Current password verification failed");
            echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta']);
        }
    } else {
        error_log("User not found in database for id: " . $user_id);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
    }
    $stmt->close();
    exit;
}

// Security questions action (from header dropdown)
if ($action === 'security_questions') {
    // Validate CSRF
    if (!validateCsrfToken()) exit;
    
    // Check if user is logged in
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Usuario no identificado']);
        exit;
    }
    
    $q1 = trim($_POST['sq1'] ?? '');
    $a1 = trim($_POST['sa1'] ?? '');
    $q2 = trim($_POST['sq2'] ?? '');
    $a2 = trim($_POST['sa2'] ?? '');
    
    if (!$q1 || !$a1 || !$q2 || !$a2) {
        echo json_encode(['success' => false, 'message' => 'Preguntas o respuestas incompletas']);
        exit;
    }
    
    if ($q1 === $q2) {
        echo json_encode(['success' => false, 'message' => 'Las preguntas deben ser diferentes']);
        exit;
    }
    
    $ha1 = password_hash($a1, PASSWORD_DEFAULT);
    $ha2 = password_hash($a2, PASSWORD_DEFAULT);
    
    // Check existing
    $stmt = $conn->prepare("SELECT id_SecQuest FROM SecQuestion WHERE id_user = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = ($res && $res->num_rows > 0);
    $stmt->close();
    
    if ($exists) {
        $update = $conn->prepare("UPDATE SecQuestion SET QuestOne = ?, QuestTwo = ?, AnswerOne = ?, AnswerTwo = ? WHERE id_user = ?");
        $update->bind_param('ssssi', $q1, $q2, $ha1, $ha2, $user_id);
        $ok = $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO SecQuestion (QuestOne, QuestTwo, AnswerOne, AnswerTwo, id_user) VALUES (?,?,?,?,?)");
        $insert->bind_param('ssssi', $q1, $q2, $ha1, $ha2, $user_id);
        $ok = $insert->execute();
        $insert->close();
    }
    
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Preguntas de seguridad guardadas']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar las preguntas']);
    }
    exit;
}

$dbUser = new DbUser();
$conn = $dbUser->getConnection();

// Rate-limiting helpers: 5 failed attempts -> lock 1 hour
function get_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '0.0.0.0');
}

function check_lock($conn, $ci, $action, $ip) {
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

function record_failure($conn, $ci, $action, $ip) {
    $now = (new DateTime())->format('Y-m-d H:i:s');
    // by ci
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
    // by ip
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

function reset_attempts($conn, $ci, $action, $ip) {
    $stmt = $conn->prepare("DELETE FROM auth_attempts WHERE (ci = ? AND action = ?) OR (ip = ? AND action = ?)");
    $stmt->bind_param('isss', $ci, $action, $ip, $action);
    $stmt->execute();
    $stmt->close();
}

if ($action === 'register') {
    try {
        // Validate CSRF
        if (!validateCsrfToken()) exit;
        
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $ci = (int) ($_POST['ci'] ?? 0);
        $birth = $_POST['birth'] ?? null;
        $pass = $_POST['pass'] ?? '';

        if (!$ci || !$pass || !$name || !$birth) {
            echo json_encode(['status' => 'error', 'message' => 'Campos requeridos faltantes']);
            exit;
        }

        $birthDate = DateTime::createFromFormat('Y-m-d', $birth);
        $birthErrors = DateTime::getLastErrors();
    $hasBirthErrors = !is_array($birthErrors)
        || (($birthErrors['warning_count'] ?? 0) > 0)
        || (($birthErrors['error_count'] ?? 0) > 0);

    if (!$birthDate || $hasBirthErrors) {
        }

        $age = $birthDate->diff(new DateTime())->y;
        if ($age < 18 || $age > 80) {
            echo json_encode(['status' => 'error', 'message' => 'Debe tener entre 18 y 80 años para registrarse']);
            exit;
        }

        // comprobar si ya existe ci
        error_log('Register start: name=' . $name . ', ci=' . $ci . ', birth=' . $birth);
        $stmt = $conn->prepare("SELECT id_user FROM `user` WHERE ci = ?");
        $stmt->bind_param('i', $ci);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            error_log('Register duplicate CI: ' . $ci);
            echo json_encode(['status' => 'error', 'message' => 'Cedula ya registrada']);
            exit;
        }
        $stmt->close();

        // Validate password strength server-side
        function password_meets_criteria($p) {
            if (strlen($p) < 8) return false;
            if (!preg_match('/[A-Z]/', $p)) return false;
            if (!preg_match('/[a-z]/', $p)) return false;
            if (!preg_match('/[0-9]/', $p)) return false;
            if (!preg_match('/[^A-Za-z0-9]/', $p)) return false;
            return true;
        }

        if (!password_meets_criteria($pass)) {
            echo json_encode(['status' => 'error', 'message' => 'La contraseña no cumple los requisitos de seguridad']);
            exit;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $id_level = 3;
        error_log('Register insert attempt: ci=' . $ci . ', name=' . $name);
        $stmt = $conn->prepare("INSERT INTO `user` (name, surname, ci, birth, pass, id_level) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssissi', $name, $surname, $ci, $birth, $hash, $id_level);
        if ($stmt->execute()) {
            error_log('Register insert success: inserted_id=' . $stmt->insert_id);
            $inserted_id = $stmt->insert_id;
            $_SESSION['user_id'] = $inserted_id;
            $_SESSION['id_level'] = $id_level;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_surname'] = $surname;
            $_SESSION['user_fullname'] = trim($name . ' ' . $surname);
            $_SESSION['user_ci'] = $ci;
            $_SESSION['user_birth'] = $birth;
            // Regenerate session ID for security
            session_regenerate_id(true);
            echo json_encode(['status' => 'success', 'message' => 'Registro exitoso', 'user_id' => $inserted_id, 'ci' => $ci, 'redirect' => $base_url . '/index.php']);
        } else {
            error_log('Register insert failed: ' . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log('Register error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'No se pudo completar el registro: ' . $e->getMessage()]);
    }
    exit;
}

// Save security questions for a user
if ($action === 'save_questions') {
    // Validate CSRF
    if (!validateCsrfToken()) exit;
    
    $q1 = trim($_POST['q1'] ?? '');
    $a1 = trim($_POST['a1'] ?? '');
    $q2 = trim($_POST['q2'] ?? '');
    $a2 = trim($_POST['a2'] ?? '');
    $ci = (int)($_POST['ci'] ?? 0);

    if (!$q1 || !$a1 || !$q2 || !$a2) {
        echo json_encode(['status' => 'error', 'message' => 'Preguntas o respuestas incompletas']);
        exit;
    }

    // ensure questions are distinct
    if ($q1 === $q2) {
        echo json_encode(['status' => 'error', 'message' => 'Las preguntas deben ser distintas']);
        exit;
    }

    // server-side answer length limits (match frontend)
    if (mb_strlen($a1) > 200 || mb_strlen($a2) > 200) {
        echo json_encode(['status' => 'error', 'message' => 'Respuestas demasiado largas']);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id && $ci) {
        $s = $conn->prepare("SELECT id_user FROM `user` WHERE ci = ? LIMIT 1");
        $s->bind_param('i', $ci);
        $s->execute();
        $r = $s->get_result();
        if ($r && $r->num_rows > 0) {
            $row = $r->fetch_assoc();
            $user_id = $row['id_user'];
        }
        $s->close();
    }

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario no identificado']);
        exit;
    }

    $ha1 = password_hash($a1, PASSWORD_DEFAULT);
    $ha2 = password_hash($a2, PASSWORD_DEFAULT);

    // check existing
    $s = $conn->prepare("SELECT id_SecQuest FROM SecQuestion WHERE id_user = ? LIMIT 1");
    $s->bind_param('i', $user_id);
    $s->execute();
    $r = $s->get_result();
    $exists = ($r && $r->num_rows > 0);
    $s->close();

    if ($exists) {
        $u = $conn->prepare("UPDATE SecQuestion SET QuestOne = ?, QuestTwo = ?, AnswerOne = ?, AnswerTwo = ? WHERE id_user = ?");
        $u->bind_param('ssssi', $q1, $q2, $ha1, $ha2, $user_id);
        $ok = $u->execute();
        $u->close();
    } else {
        $i = $conn->prepare("INSERT INTO SecQuestion (QuestOne, QuestTwo, AnswerOne, AnswerTwo, id_user) VALUES (?,?,?,?,?)");
        $i->bind_param('ssssi', $q1, $q2, $ha1, $ha2, $user_id);
        $ok = $i->execute();
        $i->close();
    }

    if ($ok) echo json_encode(['status' => 'success', 'message' => 'Preguntas de seguridad guardadas']);
    else echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar las preguntas']);
    exit;
}

// login
if ($action === 'login') {
    $ci = (int) ($_POST['ci'] ?? 0);
    $pass = $_POST['pass'] ?? '';
    if (!$ci || !$pass) {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales incompletas']);
        exit;
    }
    
    $ip = get_client_ip();
    $locked = check_lock($conn, $ci, 'login', $ip);
    if ($locked) {
        $remaining = (new DateTime())->diff($locked);
        $mins = ($remaining->h * 60) + $remaining->i + ($remaining->s > 0 ? 1 : 0);
        echo json_encode(['status' => 'error', 'message' => "Cuenta bloqueada. Intenta en {$mins} minutos."]);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id_user, pass, name, surname, ci, birth, id_level FROM `user` WHERE ci = ?");
    $stmt->bind_param('i', $ci);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (password_verify($pass, $row['pass'])) {
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['id_level'] = (int) ($row['id_level'] ?? 3);
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_surname'] = $row['surname'];
            $_SESSION['user_fullname'] = trim($row['name'] . ' ' . $row['surname']);
            $_SESSION['user_ci'] = $row['ci'];
            $_SESSION['user_birth'] = $row['birth'];
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            // reset attempts on success
            reset_attempts($conn, $ci, 'login', $ip);
            echo json_encode(['status' => 'success', 'message' => 'Autenticacion correcta', 'redirect' => $base_url . '/']);
        } else {
            // record failure
            record_failure($conn, $ci, 'login', $ip);
            echo json_encode(['status' => 'error', 'message' => 'Credenciales invalidas']);
        }
    } else {
        // record failure by IP when user not found
        record_failure($conn, $ci, 'login', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Accion no reconocida: ' . $action]);

?>
