<?php
// Vista de Login/Registro
$title = 'Login - Emprendedores de los Próceres II Etapa';
$layout = 'simple';
?>

<div class="login-header text-center">
    <img src="<?= $base_url ?>/public/images/logo.png" alt="Emprendedores de los Próceres II Etapa" class="login-logo mb-3" style="height: 72px; width: auto;">
    <h1>Emprendedores de los Próceres II Etapa</h1>
    <p class="text-muted">Sistema de Gestión Comunitaria</p>
</div>

<div id="login-body" class="login-body">
    <div class="login-tabs">
        <button class="active" id="tabLogin">Iniciar Sesion</button>
        <button id="tabRegister">Registrarse</button>
    </div>
    
    <!-- Mensajes -->
    <div id="loginAlert"></div>
    
    <!-- Formulario de Login -->
    <form id="loginForm" accept-charset="UTF-8">
        <input type="hidden" name="action" value="login">
        
        <div class="form-group">
            <label class="form-label">Cedula</label>
            <input type="number" class="form-control" name="ci" required placeholder="Ingrese su cedula" maxlength="9" max="999999999" oninput="if(this.value.length>9) this.value=this.value.slice(0,9);">
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
    <form id="registerForm" style="display: none;" accept-charset="UTF-8">
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
        
        <div class="row g-1">
            <div class="col-6">
                <div class="form-group mb-1">
                    <label class="form-label small fw-bold">Cedula *</label>
                    <input type="number" class="form-control form-control-sm py-1" name="ci" required placeholder="Cedula" maxlength="9" max="999999999" oninput="if(this.value.length>9) this.value=this.value.slice(0,9);">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group mb-1">
                    <label class="form-label small fw-bold">Nacimiento *</label>
                    <input type="date" class="form-control form-control-sm py-1" name="birth" required min="<?= date('Y-m-d', strtotime('-80 years')) ?>" max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                </div>
            </div>
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
        <a href="#" id="showReset">
            ¿Olvidaste tu contrasena?
        </a>
    </div>
    
    <!-- Seccion de recuperacion -->
    <div id="resetSection" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
        <h6 class="mb-2">Recuperar Contrasena</h6>
        <div class="form-group mb-1">
            <label class="form-label small fw-bold">Cedula</label>
            <input type="number" class="form-control form-control-sm py-1" id="resetCi" placeholder="Ingrese su cedula" maxlength="9" max="999999999" oninput="if(this.value.length>9) this.value=this.value.slice(0,9);">
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
    console.log('Switching to tab:', tab);
    document.getElementById('tabLogin').classList.toggle('active', tab === 'login');
    document.getElementById('tabRegister').classList.toggle('active', tab === 'register');
    document.getElementById('loginForm').style.display = tab === 'login' ? 'block' : 'none';
    document.getElementById('registerForm').style.display = tab === 'register' ? 'block' : 'none';
    document.getElementById('resetSection').style.display = 'none';
    var loginAlert = document.getElementById('loginAlert');
    if (loginAlert) {
        loginAlert.className = '';
        loginAlert.textContent = '';
    }
}

function passwordValid(p) {
    return p.length >= 8 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p);
}

function calculateAge(birth) {
    var birthDate = new Date(birth);
    if (isNaN(birthDate)) return null;
    var today = new Date();
    var age = today.getFullYear() - birthDate.getFullYear();
    var monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

function showLoginAlert(type, message) {
    var loginAlert = document.getElementById('loginAlert');
    if (!loginAlert) return;
    loginAlert.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
    loginAlert.textContent = message;
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
        showAlert('warning', 'Ingrese su cedula');
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
            showAlert('warning', 'No hay preguntas configuradas para esta cedula');
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
        showAlert('warning', 'Las contrasenas no coinciden');
        return;
    }
    
    if (!passwordValid(newPass)) {
        showAlert('warning', 'La contrasena no cumple los requisitos');
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
        showAlert(data.status === 'success' ? 'success' : 'error', data.message);
        if (data.status === 'success') {
            switchTab('login');
            document.getElementById('resetSection').style.display = 'none';
            document.getElementById('showReset').style.display = 'inline';
        }
    });
}

// Modal de Preguntas de Seguridad
// Función para restringir entrada en campos
function restrictInput(input, regex) {
    let isComposing = false;
    
    input.addEventListener('compositionstart', () => isComposing = true);
    input.addEventListener('compositionend', () => {
        isComposing = false;
        // Filtrar después de composición
        input.value = input.value.replace(regex, '');
    });
    
    input.addEventListener('input', function() {
        if (!isComposing) {
            this.value = this.value.replace(regex, '');
        }
    });
    
    input.addEventListener('paste', function(e) {
        let paste = (e.clipboardData || window.clipboardData).getData('text');
        let cleaned = paste.replace(regex, '');
        if (cleaned !== paste) {
            e.preventDefault();
            this.value += cleaned;
        }
    });
}

// Aplicar restricciones a los campos
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to tabs
    document.getElementById('tabLogin').addEventListener('click', function() {
        switchTab('login');
    });
    document.getElementById('tabRegister').addEventListener('click', function() {
        switchTab('register');
    });

    document.getElementById('showReset').addEventListener('click', function(e) {
        e.preventDefault();
        showResetSection();
    });

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
            if (data.status === 'success') {
                showLoginAlert('success', data.message);
                setTimeout(function() { window.location.href = data.redirect || baseUrl + '/'; }, 1000);
            } else {
                showLoginAlert('error', data.message);
            }
        })
        .catch(function() {
            showLoginAlert('error', 'Error de conexion. Intenta nuevamente.');
        });
    });

    // Registro
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Register submit handler called');
        var pass = document.getElementById('regPass').value;
        var passConfirm = document.getElementById('regPassConfirm').value;
        var birth = this.querySelector('input[name="birth"]').value;
        
        if (pass !== passConfirm) {
            showLoginAlert('error', 'Las contrasenas no coinciden');
            return;
        }
        
        if (!passwordValid(pass)) {
            showLoginAlert('error', 'La contrasena no cumple los requisitos');
            return;
        }

        if (!birth) {
            showLoginAlert('error', 'Debe ingresar su fecha de nacimiento');
            return;
        }

        var age = calculateAge(birth);
        if (age === null || age < 18 || age > 80) {
            showLoginAlert('error', 'Debe tener entre 18 y 80 años para registrarse');
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
            if (data.status === 'success') {
                window.location.href = data.redirect || 'index.php';
            } else {
                showLoginAlert('error', data.message);
            }
        })
        .catch(function() {
            showLoginAlert('error', 'Error de conexion. Intenta nuevamente.');
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

    // Campos de solo letras: Nombre y Apellido
    let letterRegex = /[^\p{L}\s]/gu;
    restrictInput(document.querySelector('input[name="name"]'), letterRegex);
    restrictInput(document.querySelector('input[name="surname"]'), letterRegex);
    
    // Campo de solo números: Cédula (en login y registro)
    let numberRegex = /[^\d]/g;
    restrictInput(document.querySelector('#loginForm input[name="ci"]'), numberRegex);
    restrictInput(document.querySelector('#registerForm input[name="ci"]'), numberRegex);
});
</script>

