<?php
/**
 * Vista de Gestión de Usuarios
 * CRUD para usuarios del sistema
 */

$title = 'Gestión de Usuarios - Consejo Comunal';
$active_page = 'usuarios';

// Get current user info for permission check
$current_user_id = $_SESSION['user_id'] ?? 0;
$current_user_level = 3;

// Check if current user is admin
$is_admin = false;
try {
    require_once __DIR__ . "/../models/dbuser.php";
    $dbUser = new DbUser();
    $conn = $dbUser->getConnection();
    $stmt = $conn->prepare("SELECT id_level FROM `user` WHERE id_user = ?");
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $current_user_level = $row['id_level'];
        $is_admin = ($current_user_level == 1);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}
?>

<div class="page-header">
    <h2><i class="bi bi-people"></i> Gestión de Usuarios</h2>
    <p class="text-muted">Administra los usuarios del sistema</p>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-table"></i> Lista de Usuarios</h5>
        <?php if ($is_admin): ?>
        <button class="btn btn-primary btn-sm" onclick="openUserModal()">
            <i class="bi bi-plus-circle"></i> Nuevo Usuario
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table id="usersTable" class="table table-striped table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Cédula</th>
                    <th>Nacimiento</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel"><i class="bi bi-person-plus"></i> Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id_user" id="id_user" value="">
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="mb-2">
                                <label for="name" class="form-label small fw-bold">Nombre *</label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name" required placeholder="Nombre">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-2">
                                <label for="surname" class="form-label small fw-bold">Apellido</label>
                                <input type="text" class="form-control form-control-sm" id="surname" name="surname" placeholder="Apellido">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label for="ci" class="form-label small fw-bold">Cédula *</label>
                        <input type="number" class="form-control form-control-sm" id="ci" name="ci" required placeholder="Cédula de identidad">
                    </div>
                    
                    <div class="mb-2">
                        <label for="birth" class="form-label small fw-bold">Fecha de Nacimiento</label>
                        <input type="date" class="form-control form-control-sm" id="birth" name="birth">
                    </div>
                    
                    <div class="mb-2">
                        <label for="id_level" class="form-label small fw-bold">Rol *</label>
                        <select class="form-control form-control-sm" id="id_level" name="id_level" required>
                            <option value="">Seleccionar rol...</option>
                            <option value="1">Administrador</option>
                            <option value="2">Operador</option>
                            <option value="3">Usuario</option>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label for="pass" class="form-label small fw-bold">Contraseña <?php echo $is_admin ? '*' : ''; ?></label>
                        <input type="password" class="form-control form-control-sm" id="pass" name="pass" <?php echo $is_admin ? 'required' : ''; ?> placeholder="Mínimo 8 caracteres">
                        <small class="text-muted d-block">8+ caracteres, mayúscula, minúscula, número y especial</small>
                    </div>
                    
                    <div id="userAlert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// CSRF Token
var csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
var baseUrl = '<?php echo $base_url; ?>';
var isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;

// Initialize DataTable
var usersTable;

$(document).ready(function() {
    // Initialize DataTable
    usersTable = $('#usersTable').DataTable({
        ajax: {
            url: baseUrl + '/src/controllers/comunity_user.php?mode=list',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id_user', width: '50px' },
            { data: 'name' },
            { data: 'surname' },
            { data: 'ci' },
            { 
                data: 'birth',
                render: function(data) {
                    return data ? data : '-';
                }
            },
            { 
                data: 'user_role',
                render: function(data, type, row) {
                    var badgeClass = 'bg-secondary';
                    if (row.id_level == 1) badgeClass = 'bg-danger';
                    else if (row.id_level == 2) badgeClass = 'bg-warning text-dark';
                    else badgeClass = 'bg-info text-dark';
                    return '<span class="badge ' + badgeClass + '">' + (data || 'Usuario') + '</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    var buttons = '';
                    if (isAdmin) {
                        buttons = '<button class="btn btn-primary btn-sm me-1" onclick="editUser(' + data.id_user + ')"><i class="bi bi-pencil"></i></button>';
                        buttons += '<button class="btn btn-danger btn-sm" onclick="deleteUser(' + data.id_user + ', \'' + (data.name || '') + '\')"><i class="bi bi-trash"></i></button>';
                    } else {
                        buttons = '<span class="text-muted small">Sin permisos</span>';
                    }
                    return buttons;
                },
                orderable: false,
                width: '100px'
            }
        ],
        language: {
            url: baseUrl + '/public/vendor/datatables/es-ES.json'
        },
        order: [[0, 'desc']]
    });
    
    // User form submit
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        
        var id_user = $('#id_user').val();
        var name = $('#name').val();
        var ci = $('#ci').val();
        var pass = $('#pass').val();
        
        // Validate password if new user
        if (!id_user && !pass) {
            $('#userAlert').html('<div class="alert alert-danger">La contraseña es requerida</div>');
            return;
        }
        
        // Validate password strength
        if (pass && pass.length < 8) {
            $('#userAlert').html('<div class="alert alert-danger">La contraseña debe tener al menos 8 caracteres</div>');
            return;
        }
        if (pass && !/[A-Z]/.test(pass)) {
            $('#userAlert').html('<div class="alert alert-danger">La contraseña debe tener al menos una mayúscula</div>');
            return;
        }
        if (pass && !/[a-z]/.test(pass)) {
            $('#userAlert').html('<div class="alert alert-danger">La contraseña debe tener al menos una minúscula</div>');
            return;
        }
        if (pass && !/[0-9]/.test(pass)) {
            $('#userAlert').html('<div class="alert alert-danger">La contraseña debe tener al menos un número</div>');
            return;
        }
        if (pass && !/[^A-Za-z0-9]/.test(pass)) {
            $('#userAlert').html('<div class="alert alert-danger">La contraseña debe tener al menos un carácter especial</div>');
            return;
        }
        
        var formData = $(this).serialize();
        formData += '&action=save';
        
        $.ajax({
            url: baseUrl + '/src/controllers/comunity_user.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#userModal').modal('hide');
                    $('#userForm')[0].reset();
                    $('#userAlert').html('');
                    usersTable.ajax.reload();
                    showToast('success', response.message);
                } else {
                    $('#userAlert').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#userAlert').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });
});

// Open modal for new user
function openUserModal() {
    $('#userForm')[0].reset();
    $('#id_user').val('');
    $('#userModalLabel').html('<i class="bi bi-person-plus"></i> Nuevo Usuario');
    $('#userAlert').html('');
    $('#userModal').modal('show');
}

// Edit user
function editUser(id) {
    $.ajax({
        url: baseUrl + '/src/controllers/comunity_user.php?mode=get&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.id_user) {
                $('#id_user').val(response.id_user);
                $('#name').val(response.name || '');
                $('#surname').val(response.surname || '');
                $('#ci').val(response.ci || '');
                $('#birth').val(response.birth || '');
                $('#id_level').val(response.id_level || 3);
                $('#pass').val('').removeAttr('required');
                
                $('#userModalLabel').html('<i class="bi bi-person-gear"></i> Editar Usuario: ' + (response.name || ''));
                $('#userAlert').html('');
                $('#userModal').modal('show');
            } else {
                showToast('error', response.error || 'Usuario no encontrado');
            }
        },
        error: function() {
            showToast('error', 'Error al cargar usuario');
        }
    });
}

// Delete user
function deleteUser(id, name) {
    if (confirm('¿Está seguro de eliminar al usuario ' + (name || 'ID: ' + id) + '?')) {
        $.ajax({
            url: baseUrl + '/src/controllers/comunity_user.php',
            type: 'POST',
            data: {
                action: 'delete',
                csrf_token: csrfToken,
                id_user: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    usersTable.ajax.reload();
                    showToast('success', response.message);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'Error al eliminar usuario');
            }
        });
    }
}

// Show toast notification
function showToast(type, message) {
    var toastClass = type === 'success' ? 'bg-success' : 'bg-danger';
    var icon = type === 'success' ? 'bi-check-circle' : 'bi-x-circle';
    
    var toastHtml = '<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">' +
        '<div class="toast ' + toastClass + ' text-white" role="alert">' +
        '<div class="toast-header ' + toastClass + ' text-white">' +
        '<i class="bi ' + icon + ' me-2"></i>' +
        '<strong class="me-auto">Mensaje</strong>' +
        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>' +
        '</div>' +
        '<div class="toast-body">' + message + '</div>' +
        '</div></div>';
    
    $(toastHtml).appendTo('body');
    var toastEl = $('.toast').last();
    var toast = new bootstrap.Toast(toastEl);
    toast.show();
    
    toastEl.on('hidden.bs.toast', function() {
        $(this).parent().remove();
    });
}
</script>
