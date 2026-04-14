<?php
// Vista de Login/Registro
$title = 'Login - Consejo Comunal';
$layout = 'simple';
?>

<div class="login-header">
    <h1><i class="bi bi-building"></i> Consejo Comunal</h1>
    <p>Sistema de Gestion Comunitaria</p>
</div>

<div class="login-body">
    <div class="login-tabs">
        <button class="active" id="tabLogin" onclick="switchTab('login')">Iniciar Sesion</button>
        <button id="tabRegister" onclick="switchTab('register')">Registrarse</button>
    </div>
    
    <!-- Mensajes -->
    <div id="loginAlert"></div>
    
    <!-- Formulario de Login -->
    <form id="loginForm">
        <input type="hidden" name="action" value="login">
        
        <div class="form-group">
            <label class="form-label">Cedula</label>
            <input type="number" class="form-control" name="ci" required placeholder="Ingrese su cedula">
        </div>
        
        <div class="form-group">
            <label class="form-label">Contrasena</label>
            <div class="input-group">
                <input type="password" class="form-control" id="loginPass" name="pass" required placeholder="Ingrese su contrasena">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('loginPass', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100">Iniciar Sesion</button>
    </form>
    
    <!-- Formulario de Registro -->
    <form id="registerForm" style="display: none;">
        <input type="hidden" name="action" value="register">
        
        <div class="row g-1">
            <div class="col-6">
                <div class="form-group mb-1">
                    <label class="form-label small fw-bold">Nombre *</label>
                    <input type="text" class="form-control form-control-sm py-1" name="name" required placeholder="Nombre">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group mb-1">
                    <label class="form-label small fw-bold">Apellido</label>
                    <input type="text" class="form-control form-control-sm py-1" name="surname" placeholder="Apellido">
                </div>
            </div>
        </div>
        
        <div class="form-group mb-1">
            <label class="form-label small fw-bold">Cedula *</label>
            <input type="number" class="form-control form-control-sm py-1" name="ci" required placeholder="Cedula">
        </div>
        
        <div class="form-group mb-1">
            <label class="form-label small fw-bold">Nacimiento</label>
            <input type="date" class="form-control form-control-sm py-1" name="birth">
        </div>
        
        <div class="form-group mb-1">
            <label class="form-label small fw-bold">Contrasena *</label>
            <div class="input-group input-group-sm">
                <input type="password" class="form-control py-1" id="regPass" name="pass" required placeholder="Min 8 caracteres">
                <button class="btn btn-outline-secondary py-1" type="button" onclick="togglePassword('regPass', this)">
                    <i class="bi bi-eye" style="font-size: 0.8rem;"></i>
                </button>
            </div>
            <div class="password-rules mt-1" id="passwordRules" style="font-size: 0.65rem;">
                <span class="me-2" id="ruleLength"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> 8+</span>
                <span class="me-2" id="ruleUpper"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Mayus</span>
                <span class="me-2" id="ruleLower"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Minus</span>
                <span class="me-2" id="ruleDigit"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Num</span>
                <span id="ruleSpecial"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Esp</span>
            </div>
        </div>
        
        <div class="form-group mb-1">
            <label class="form-label small fw-bold">Confirmar *</label>
            <div class="input-group input-group-sm">
                <input type="password" class="form-control py-1" id="regPassConfirm" name="pass_confirm" required placeholder="Repetir contrasena">
                <button class="btn btn-outline-secondary py-1" type="button" onclick="togglePassword('regPassConfirm', this)">
                    <i class="bi bi-eye" style="font-size: 0.8rem;"></i>
                </button>
            </div>
        </div>
        
        <button type="submit" class="btn btn-success btn-sm w-100 mt-2 py-1">Registrarse</button>
    </form>
    
    <!-- Enlace de recuperacion -->
    <div class="text-center mt-3">
        <a href="#" id="showReset" onclick="showResetSection(); return false;">
            ¿Olvidaste tu contrasena?
        </a>
    </div>
    
    <!-- Seccion de recuperacion -->
    <div id="resetSection" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
        <h6 class="mb-2">Recuperar Contrasena</h6>
        <div class="form-group mb-1">
            <label class="form-label small fw-bold">Cedula</label>
            <input type="number" class="form-control form-control-sm py-1" id="resetCi" placeholder="Ingrese su cedula">
        </div>
        <button class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="getSecurityQuestions()">
            Obtener Preguntas
        </button>
        
        <div id="resetQuestions" style="display: none;">
            <div class="form-group mb-1">
                <label class="form-label small" id="q1Label">Pregunta 1</label>
                <input type="text" class="form-control form-control-sm py-1" id="q1Answer" placeholder="Respuesta 1">
            </div>
            <div class="form-group mb-1">
                <label class="form-label small" id="q2Label">Pregunta 2</label>
                <input type="text" class="form-control form-control-sm py-1" id="q2Answer" placeholder="Respuesta 2">
            </div>
            <div class="form-group mb-1">
                <label class="form-label small fw-bold">Nueva Contrasena</label>
                <div class="input-group input-group-sm">
                    <input type="password" class="form-control py-1" id="newPass" placeholder="Nueva contrasena">
                    <button class="btn btn-outline-secondary py-1" type="button" onclick="togglePassword('newPass', this)">
                        <i class="bi bi-eye" style="font-size: 0.8rem;"></i>
                    </button>
                </div>
                <div class="password-rules mt-1" id="resetPasswordRules" style="font-size: 0.65rem;">
                    <span class="me-2" id="resetRuleLength"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> 8+</span>
                    <span class="me-2" id="resetRuleUpper"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Mayus</span>
                    <span class="me-2" id="resetRuleLower"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Minus</span>
                    <span class="me-2" id="resetRuleDigit"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Num</span>
                    <span id="resetRuleSpecial"><i class="bi bi-circle" style="font-size: 0.5rem;"></i> Esp</span>
                </div>
            </div>
            <div class="form-group mb-1">
                <label class="form-label small fw-bold">Confirmar</label>
                <div class="input-group input-group-sm">
                    <input type="password" class="form-control py-1" id="newPassConfirm" placeholder="Repita nueva contrasena">
                    <button class="btn btn-outline-secondary py-1" type="button" onclick="togglePassword('newPassConfirm', this)">
                        <i class="bi bi-eye" style="font-size: 0.8rem;"></i>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary btn-sm w-100 mt-1 py-1" onclick="resetPassword()">Restablecer</button>
        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
function togglePassword(inputId, button) {
    var input = document.getElementById(inputId);
    var icon = button.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// CSRF Token
var csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
var baseUrl = '<?= $base_url ?? '' ?>';

// Switch entre login y registro
function switchTab(tab) {
    document.getElementById('tabLogin').classList.toggle('active', tab === 'login');
    document.getElementById('tabRegister').classList.toggle('active', tab === 'register');
    document.getElementById('loginForm').style.display = tab === 'login' ? 'block' : 'none';
    document.getElementById('registerForm').style.display = tab === 'register' ? 'block' : 'none';
    document.getElementById('resetSection').style.display = 'none';
    document.getElementById('loginAlert').innerHTML = '';
}

// Validacion de contrasena
document.getElementById('regPass').addEventListener('input', function() {
    var pass = this.value;
    var rules = [
        { id: 'ruleLength', valid: pass.length >= 8 },
        { id: 'ruleUpper', valid: /[A-Z]/.test(pass) },
        { id: 'ruleLower', valid: /[a-z]/.test(pass) },
        { id: 'ruleDigit', valid: /[0-9]/.test(pass) },
        { id: 'ruleSpecial', valid: /[^A-Za-z0-9]/.test(pass) }
    ];
    
    var validCount = rules.filter(function(r) { return r.valid; }).length;
    var strength = 'weak';
    if (validCount >= 5) strength = 'very-strong';
    else if (validCount >= 4) strength = 'strong';
    else if (validCount >= 3) strength = 'medium';
    
    var strengthFill = document.getElementById('strengthFill');
    if (strengthFill) {
        strengthFill.className = 'strength-fill ' + strength;
    }
    
    rules.forEach(function(r) {
        var el = document.getElementById(r.id);
        if (el) {
            el.className = 'rule ' + (r.valid ? 'valid' : 'invalid');
            var icon = el.querySelector('i');
            if (icon) {
                icon.className = r.valid ? 'bi bi-check-circle' : 'bi bi-circle';
            }
        }
    });
});

// Login
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('csrf_token', csrfToken);
    
    fetch(baseUrl + '/src/controllers/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var alert = document.getElementById('loginAlert');
        if (data.status === 'success') {
            alert.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(function() { window.location.href = data.redirect || baseUrl + '/'; }, 1000);
        } else {
            alert.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(function() {
        document.getElementById('loginAlert').innerHTML = '<div class="alert alert-danger">Error de conexion</div>';
    });
});

// Registro
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var pass = document.getElementById('regPass').value;
    var passConfirm = document.getElementById('regPassConfirm').value;
    
    if (pass !== passConfirm) {
        document.getElementById('loginAlert').innerHTML = '<div class="alert alert-danger">Las contrasenas no coinciden</div>';
        return;
    }
    
    if (!passwordValid(pass)) {
        document.getElementById('loginAlert').innerHTML = '<div class="alert alert-danger">La contrasena no cumple los requisitos</div>';
        return;
    }
    
    var formData = new FormData(this);
    formData.append('csrf_token', csrfToken);
    
    fetch(baseUrl + '/src/controllers/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var alert = document.getElementById('loginAlert');
        if (data.status === 'success') {
            alert.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            // Mostrar modal de preguntas de seguridad
            setTimeout(function() {
                showSecurityQuestionsModal(data.ci);
            }, 1000);
        } else {
            alert.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    });
});

// Validacion de contrasena en recuperacion
document.getElementById('newPass').addEventListener('input', function() {
    var pass = this.value;
    var rules = [
        { id: 'resetRuleLength', valid: pass.length >= 8 },
        { id: 'resetRuleUpper', valid: /[A-Z]/.test(pass) },
        { id: 'resetRuleLower', valid: /[a-z]/.test(pass) },
        { id: 'resetRuleDigit', valid: /[0-9]/.test(pass) },
        { id: 'resetRuleSpecial', valid: /[^A-Za-z0-9]/.test(pass) }
    ];
    
    rules.forEach(function(r) {
        var el = document.getElementById(r.id);
        if (el) {
            el.className = r.valid ? 'rule valid' : 'rule';
            var icon = el.querySelector('i');
            if (icon) {
                icon.className = r.valid ? 'bi bi-check-circle' : 'bi bi-circle';
            }
        }
    });
});

function passwordValid(p) {
    return p.length >= 8 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p);
}

// Recuperacion de contrasena
function showResetSection() {
    document.getElementById('resetSection').style.display = 'block';
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('registerForm').style.display = 'none';
    document.getElementById('showReset').style.display = 'none';
}

function getSecurityQuestions() {
    var ci = document.getElementById('resetCi').value;
    if (!ci) {
        alert('Ingrese su cedula');
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'request');
    formData.append('ci', ci);
    formData.append('csrf_token', csrfToken);
    
    fetch(baseUrl + '/src/controllers/password_reset.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.questions && data.questions.q1) {
            document.getElementById('q1Label').textContent = data.questions.q1;
            document.getElementById('q2Label').textContent = data.questions.q2;
            document.getElementById('resetQuestions').style.display = 'block';
        } else {
            alert('No hay preguntas configuradas para esta cedula');
        }
    });
}

function resetPassword() {
    var ci = document.getElementById('resetCi').value;
    var a1 = document.getElementById('q1Answer').value;
    var a2 = document.getElementById('q2Answer').value;
    var newPass = document.getElementById('newPass').value;
    var newPassConfirm = document.getElementById('newPassConfirm').value;
    
    if (newPass !== newPassConfirm) {
        alert('Las contrasenas no coinciden');
        return;
    }
    
    if (!passwordValid(newPass)) {
        alert('La contrasena no cumple los requisitos');
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'reset');
    formData.append('ci', ci);
    formData.append('answerOne', a1);
    formData.append('answerTwo', a2);
    formData.append('new_password', newPass);
    formData.append('csrf_token', csrfToken);
    
    fetch(baseUrl + '/src/controllers/password_reset.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        alert(data.message);
        if (data.status === 'success') {
            switchTab('login');
            document.getElementById('resetSection').style.display = 'none';
            document.getElementById('showReset').style.display = 'inline';
        }
    });
}

// Modal de Preguntas de Seguridad
var currentCi = null;

function showSecurityQuestionsModal(ci) {
    currentCi = ci;
    document.getElementById('securityQuestionsModal').classList.add('active');
    document.getElementById('sqAlert').innerHTML = '';
    document.getElementById('sq1').value = '';
    document.getElementById('sa1').value = '';
    document.getElementById('sq2').value = '';
    document.getElementById('sa2').value = '';
}

function closeSecurityModal() {
    document.getElementById('securityQuestionsModal').classList.remove('active');
    // Redirigir al login
    switchTab('login');
}

function saveSecurityQuestions() {
    var q1 = document.getElementById('sq1').value;
    var a1 = document.getElementById('sa1').value.trim();
    var q2 = document.getElementById('sq2').value;
    var a2 = document.getElementById('sa2').value.trim();
    
    if (!q1 || !a1 || !q2 || !a2) {
        document.getElementById('sqAlert').innerHTML = '<div class="alert alert-danger">Todos los campos son requeridos</div>';
        return;
    }
    
    if (q1 === q2) {
        document.getElementById('sqAlert').innerHTML = '<div class="alert alert-danger">Las preguntas deben ser distintas</div>';
        return;
    }
    
    if (a1.length > 200 || a2.length > 200) {
        document.getElementById('sqAlert').innerHTML = '<div class="alert alert-danger">Las respuestas no pueden exceder 200 caracteres</div>';
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'save_questions');
    formData.append('ci', currentCi);
    formData.append('q1', q1);
    formData.append('a1', a1);
    formData.append('q2', q2);
    formData.append('a2', a2);
    formData.append('csrf_token', csrfToken);
    
    fetch(baseUrl + '/src/controllers/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            alert(data.message);
            closeSecurityModal();
        } else {
            document.getElementById('sqAlert').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(function() {
        document.getElementById('sqAlert').innerHTML = '<div class="alert alert-danger">Error de conexion</div>';
    });
}
</script>
