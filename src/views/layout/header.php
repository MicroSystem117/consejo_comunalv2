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
                        <?php
                            $initials = 'U';
                            $firstName = trim($_SESSION['user_name'] ?? '');
                            $lastName = trim($_SESSION['user_surname'] ?? '');
                            if ($firstName && $lastName) {
                                $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                            } elseif ($firstName) {
                                $initials = strtoupper(substr($firstName, 0, 1));
                            }
                            echo htmlspecialchars($initials);
                        ?>
                    </div>
                    <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_fullname'] ?? $_SESSION['user_name'] ?? 'Usuario') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end user-profile-menu">
                    <li class="px-3 py-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar" style="width:48px;height:48px;font-size:1.1rem;display:inline-flex;align-items:center;justify-content:center;">
                                <?php
                                    $initialsPanel = 'U';
                                    $fn = trim($_SESSION['user_name'] ?? '');
                                    $ln = trim($_SESSION['user_surname'] ?? '');
                                    if ($fn && $ln) {
                                        $initialsPanel = strtoupper(substr($fn,0,1) . substr($ln,0,1));
                                    } elseif ($fn) {
                                        $initialsPanel = strtoupper(substr($fn,0,1));
                                    }
                                    echo htmlspecialchars($initialsPanel);
                                ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($_SESSION['user_fullname'] ?? $_SESSION['user_name'] ?? 'Usuario') ?></div>
                                <?php if (!empty($_SESSION['user_ci'])): ?>
                                    <div class="small text-muted">Cédula: <?= htmlspecialchars($_SESSION['user_ci']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($_SESSION['user_birth'])): ?>
                                    <div class="small text-muted">Nac.: <?= htmlspecialchars($_SESSION['user_birth']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="openPasswordModal(); return false;"><i class="bi bi-key"></i> Cambiar Contraseña</a></li>
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
    
    <?php require_once __DIR__ . '/sidebar.php'; ?>
<?php else: ?>
    <!-- Layout simple (para login) -->
    <div class="login-container">
        <div class="login-card">
            <?php include_once __DIR__ . '/../login.php'; ?>
        </div>
    </div>
    
<?php endif; ?>
