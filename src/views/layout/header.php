<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestion del Consejo Comunal">
    <title><?= $title ?? 'Consejo Comunal' ?></title>
    <link rel="stylesheet" href="<?= $base_url ?>/public/vendor/bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>/public/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $base_url ?>/public/vendor/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>/public/css/app.css">
    <script src="<?= $base_url ?>/public/js/jquery.min.js"></script>
</head>
<body data-base-url="<?= $base_url ?? '' ?>">
<script>window.baseUrl = '<?= $base_url ?? '' ?>';</script>
<?php if (!isset($layout) || $layout === 'full'): ?>
    <!-- Layout completo para paginas con sidebar -->
    <header class="app-header">
        <button class="btn btn-sm btn-light me-2 menu-toggle-btn" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <script>function toggleSidebar() { document.querySelector('.app-sidebar').classList.toggle('show'); }</script>
        <div class="logo">
            <img src="<?= $base_url ?>/public/images/logo.png" alt="Consejo Comunal" class="navbar-logo">
            <span>Consejo Comunal</span>
        </div>
        <div class="user-menu">
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar small">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline"><?= $_SESSION['user_name'] ?? 'Usuario' ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" onclick="openPasswordModal(); return false;">
                        <i class="bi bi-key"></i> Cambiar Contraseña
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="openSecurityQuestionsModal(); return false;">
                        <i class="bi bi-shield-lock"></i> Preguntas de Seguridad
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="<?= $base_url ?>/src/controllers/auth.php" method="POST" id="logoutForm" style="display:inline;">
                            <input type="hidden" name="action" value="logout">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right"></i> Salir
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <script>
        function openPasswordModal() { 
            var modal = new bootstrap.Modal(document.getElementById('passwordModal'));
            modal.show();
        }
        function openSecurityQuestionsModal() { 
            var modal = new bootstrap.Modal(document.getElementById('securityQuestionsModal'));
            modal.show();
        }
    </script>
    
    <!-- Modal para Cambiar Contraseña -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel"><i class="bi bi-key"></i> Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="passwordForm" method="POST" action="<?= $base_url ?>/src/controllers/auth.php?action=change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Contraseña Actual</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-rules mt-2" style="font-size: 0.75rem;">
                                <span class="me-2" id="ruleLengthCP"><i class="bi bi-circle" style="font-size: 0.6rem;"></i> 8+</span>
                                <span class="me-2" id="ruleUpperCP"><i class="bi bi-circle" style="font-size: 0.6rem;"></i> Mayús</span>
                                <span class="me-2" id="ruleLowerCP"><i class="bi bi-circle" style="font-size: 0.6rem;"></i> Minús</span>
                                <span class="me-2" id="ruleDigitCP"><i class="bi bi-circle" style="font-size: 0.6rem;"></i> Número</span>
                                <span id="ruleSpecialCP"><i class="bi bi-circle" style="font-size: 0.6rem;"></i> Especial</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para Preguntas de Seguridad -->
    <div id="securityQuestionsModal" class="modal fade" tabindex="-1" aria-labelledby="securityQuestionsModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="securityQuestionsModalLabel"><i class="bi bi-shield-lock"></i> Preguntas de Seguridad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="securityQuestionsForm" method="POST" action="<?= $base_url ?>/src/controllers/auth.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="modal-body">
                        <p class="small text-muted mb-3">Selecciona 2 preguntas para recuperar tu contraseña.</p>
                        
                        <div class="mb-3">
                            <label for="sq1" class="form-label small fw-bold">Pregunta 1 *</label>
                            <select class="form-control" id="sq1" name="sq1" required>
                                <option value="">Selecciona...</option>
                                <option value="¿Cuál es el nombre de tu primera mascota?">¿Cuál es el nombre de tu primera mascota?</option>
                                <option value="¿En qué ciudad naciste?">¿En qué ciudad naciste?</option>
                                <option value="¿Cuál es tu comida favorita?">¿Cuál es tu comida favorita?</option>
                                <option value="¿Cuál es el nombre de tu mejor amigo?">¿Cuál es el nombre de tu mejor amigo?</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sa1" class="form-label small fw-bold">Respuesta 1 *</label>
                            <input type="text" class="form-control" id="sa1" name="sa1" placeholder="Tu respuesta" maxlength="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sq2" class="form-label small fw-bold">Pregunta 2 *</label>
                            <select class="form-control" id="sq2" name="sq2" required>
                                <option value="">Selecciona...</option>
                                <option value="¿Cuál es el nombre de tu escuela?">¿Cuál es el nombre de tu escuela?</option>
                                <option value="¿Cuál es tu película favorita?">¿Cuál es tu película favorita?</option>
                                <option value="¿Cuál es tu artista favorito?">¿Cuál es tu artista favorito?</option>
                                <option value="¿Qué país te gustaría visitar?">¿Qué país te gustaría visitar?</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sa2" class="form-label small fw-bold">Respuesta 2 *</label>
                            <input type="text" class="form-control" id="sa2" name="sa2" placeholder="Tu respuesta" maxlength="100" required>
                        </div>
                        
                        <div id="sqAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Preguntas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="app-container">
        <aside class="app-sidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-menu-button-wide"></i> Menu</h3>
            </div>
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'dashboard' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php">
                            <i class="bi bi-speedometer2"></i>
                            Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'personas' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=personas">
                            <i class="bi bi-people"></i>
                            Personas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'familias' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=familias">
                            <i class="bi bi-house-door"></i>
                            Familias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'calendar' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=calendar">
                            <i class="bi bi-calendar-event"></i>
                            Calendario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'statistics' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=statistics">
                            <i class="bi bi-bar-chart-line"></i>
                            Estadísticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'viviendas' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=viviendas">
                            <i class="bi bi-house"></i>
                            Viviendas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'calles' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=calles">
                            <i class="bi bi-signpost"></i>
                            Calles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'manzana' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=manzana">
                            <i class="bi bi-grid-3x3"></i>
                            Manzana
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'backup' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=backup">
                            <i class="bi bi-database-check"></i>
                            Respaldo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'usuarios' ? 'active' : '' ?>" href="<?= $base_url ?>/index.php?view=usuarios">
                            <i class="bi bi-people"></i>
                            Usuarios
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <div class="main-wrapper">
            <main class="main-content">
<?php else: ?>
    <!-- Layout simple (para login) -->
    <div class="login-container">
        <div class="login-card">
            <?php include_once __DIR__ . '/../login.php'; ?>
        </div>
    </div>
    
    <!-- Modal de Preguntas de Seguridad -->
    <div id="securityQuestionsModal" class="modal-overlay" style="display: none; z-index: 9999;">
        <div class="modal" style="max-width: 380px; margin: 10px;">
            <div class="modal-header">
                <h5 style="font-size: 1rem;"><i class="bi bi-shield-lock"></i> Preguntas de Seguridad</h5>
                <button type="button" class="modal-close" onclick="closeSecurityModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <p class="small text-muted mb-2">Selecciona 2 preguntas para recuperar tu contrasena.</p>
                
                <div class="form-group mb-2">
                    <label class="form-label small fw-bold">Pregunta 1 *</label>
                    <select class="form-control form-control-sm" id="sq1">
                        <option value="">Selecciona...</option>
                        <option value="¿Cuál es el nombre de tu primera mascota?">Nombre de tu primera mascota</option>
                        <option value="¿En qué ciudad naciste?">Ciudad donde naciste</option>
                        <option value="¿Cuál es tu comida favorita?">Tu comida favorita</option>
                        <option value="¿Cuál es el nombre de tu mejor amigo?">Tu mejor amigo</option>
                    </select>
                </div>
                
                <div class="form-group mb-2">
                    <label class="form-label small fw-bold">Respuesta 1 *</label>
                    <input type="text" class="form-control form-control-sm" id="sa1" placeholder="Tu respuesta" maxlength="100">
                </div>
                
                <div class="form-group mb-2">
                    <label class="form-label small fw-bold">Pregunta 2 *</label>
                    <select class="form-control form-control-sm" id="sq2">
                        <option value="">Selecciona...</option>
                        <option value="¿Cuál es el nombre de tu escuela?">Nombre de tu escuela</option>
                        <option value="¿Cuál es tu película favorita?">Tu película favorita</option>
                        <option value="¿Cuál es tu artista favorito?">Tu artista favorito</option>
                        <option value="¿Qué país te gustaría visitar?">País que te gustaría visitar</option>
                    </select>
                </div>
                
                <div class="form-group mb-2">
                    <label class="form-label small fw-bold">Respuesta 2 *</label>
                    <input type="text" class="form-control form-control-sm" id="sa2" placeholder="Tu respuesta" maxlength="100">
                </div>
                
                <div id="sqAlert"></div>
            </div>
            <div class="modal-footer" style="padding: 10px 15px;">
                <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveSecurityQuestions()">Guardar Preguntas</button>
            </div>
        </div>
    </div>
<?php endif; ?>
