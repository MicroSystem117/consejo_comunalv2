
<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Vista de Personas
$title = 'Personas - Consejo Comunal';
$active_page = 'personas';
require_once __DIR__ . '/../controllers/personas_data.php';
$personas = obtenerTodasLasPersonas();
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-people"></i> Gestión de Personas</h1>
    <div class="page-actions">
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV('personasTable', 'personas.csv')">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="modal" data-bs-target="#personaModal">
                <i class="bi bi-person-plus"></i> Nueva Persona
            </button>
        </div>
    </div>
</div>

<!-- Mensajes -->
<div id="alertContainer"></div>

<!-- Tabla de personas -->
<div class="table-container fade-in">
    <table class="table table-striped table-hover table-sm" id="personasTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Cédula</th>
                <th>Fecha Nac.</th>
                <th>Familia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="personasBody">
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $p): ?>
                    <tr data-id="<?= $p['id_person'] ?>">
                        <td><?= $p['id_person'] ?></td>
                        <td><?= htmlspecialchars($p['name_person']) ?></td>
                        <td><?= number_format($p['ci_person'], 0, '', '.') ?></td>
                        <td><?= $p['birth_person'] ? date('d/m/Y', strtotime($p['birth_person'])) : '-' ?></td>
                        <td><?= htmlspecialchars($p['surname_family'] ?? 'Sin asignar') ?></td>
                        <?php $userLevel = $_SESSION['id_level'] ?? 3; ?>
                        <td class="actions">
                            <?php if (in_array($userLevel, [1, 2, 3], true)): ?>
                                <button class="btn btn-sm btn-info" onclick="showMoreInfo(<?= $p['id_person'] ?>)">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($userLevel, [1, 2], true)): ?>
                                <button class="btn btn-sm btn-warning" onclick="editPerson(<?= $p['id_person'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            <?php endif; ?>

                            <?php if ($userLevel === 1): ?>
                                <button class="btn btn-sm btn-danger" onclick="deletePerson(<?= $p['id_person'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            <?php endif; ?>

                            <a class="btn btn-sm btn-success" href="src/controllers/comunity_person.php?action=pdf&id=<?= $p['id_person'] ?>" target="_blank" title="Generar Constancia PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay personas registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Nueva/Editar Persona -->
<div class="modal fade" id="personaModal" tabindex="-1" aria-labelledby="personaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="personaModalLabel">Nueva Persona</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="personaForm" method="POST" action="" accept-charset="UTF-8">
                <input type="hidden" id="id_person" name="id_person" value="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name_person" class="form-label">Nombre de la Persona</label>
                        <input type="text" class="form-control" id="name_person" name="name_person" required placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="mb-3">
                        <label for="ci_person" class="form-label">Cédula de Identidad</label>
                        <input type="number" class="form-control" id="ci_person" name="ci_person" required placeholder="Ej: 12345678" maxlength="9" max="999999999" oninput="if(this.value.length>9) this.value=this.value.slice(0,9);">
                    </div>
                    <div class="mb-3">
                        <label for="birth_person" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="birth_person" name="birth_person">
                    </div>
                    <div class="mb-3">
                        <label for="id_family" class="form-label">Familia existente (opcional)</label>
                        <select class="form-select" id="id_family" name="id_family">
                            <option value="">Crear nueva familia</option>
                        </select>
                    </div>

                    <div class="alert alert-info small">Si no seleccionas familia existente, completa los campos de Calle / Manzana / Vivienda / Apellido para crear en secuencia.</div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="id_street" class="form-label">Calle</label>
                            <select class="form-select" id="id_street" name="id_street">
                                <option value="">Seleccionar Calle</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="id_square" class="form-label">Manzana</label>
                            <select class="form-select" id="id_square" name="id_square">
                                <option value="">Seleccionar Manzana</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="number_house" class="form-label">Número de Vivienda</label>
                        <input type="text" class="form-control" id="number_house" name="number_house" placeholder="Ej: 12A">
                    </div>
                    <div class="mb-3">
                        <label for="surname_family" class="form-label">Apellido Familia</label>
                        <input type="text" class="form-control" id="surname_family" name="surname_family" placeholder="Ej: Pérez">
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

        <style>
        /* Forzamos al elemento raíz del modal a usar toda la pantalla real solo cuando está visible */
        body div#personInfoModal.modal.show {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            max-width: 100vw !important;
            max-height: 100vh !important;
            z-index: 1060 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: transparent !important;
            transform: none !important;
        }

        /* Hidden bootstrap modal fallback */
        body div#personInfoModal.modal:not(.show) {
            display: none !important;
        }

        /* Forzamos al cuadro dialog a desvincularse de la columna izquierda */
        body div#personInfoModal.modal.show .modal-dialog {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important; /* Centrado matemático perfecto en pantalla */
            margin: 0 !important;
            width: 85% !important;
            max-width: 850px !important;
        }

        /* Asegurar transparencia de los contenedores intermedios */
        body div#personInfoModal .modal-content,
        body div#personInfoModal .modal-body {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
        }

        /* Estilo estricto para las tarjetas flotantes */
        body div#personInfoModal .info-card {
            background: #ffffff !important;
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        }

        /* Título superior en blanco para resaltar con el desenfoque */
        body div#personInfoModal .modal-title {
            color: #ffffff !important;
            font-weight: 700 !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.4) !important;
        }

        /* Poner la X de cerrar en blanco */
        body div#personInfoModal .btn-close {
            filter: invert(1) grayscale(1) brightness(2) !important;
        }
        </style>

        <!-- Modal Más Información: Vivienda y Familia (rediseñado con cards y glassmorphism) -->
        <div class="modal fade" id="personInfoModal" tabindex="-1" aria-labelledby="personInfoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 bg-transparent">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="personInfoModalLabel">Información de Persona</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card info-card">
                                        <div class="card-body">
                                            <h6 class="card-title">Datos personales</h6>
                                            <p class="mb-1"><strong>Nombre:</strong> <span id="info_name">-</span></p>
                                            <p class="mb-1"><strong>Cédula:</strong> <span id="info_ci">-</span></p>
                                            <p class="mb-1"><strong>Fecha Nac.:</strong> <span id="info_birth">-</span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card info-card">
                                        <div class="card-body">
                                            <h6 class="card-title">Vivienda</h6>
                                            <p class="mb-1"><strong>Número Vivienda:</strong> <span id="info_number_house">-</span></p>
                                            <p class="mb-1"><strong>Manzana (Código):</strong> <span id="info_codigo_square">-</span></p>
                                            <p class="mb-1"><strong>Calle:</strong> <span id="info_name_street">-</span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card info-card">
                                        <div class="card-body">
                                            <h6 class="card-title">Familia</h6>
                                            <p class="mb-1"><strong>Apellido Familia:</strong> <span id="info_surname">-</span></p>
                                            <p class="mb-1"><strong>ID Familia:</strong> <span id="info_id_family">-</span></p>
                                            <hr>
                                            <h6 class="small mb-2">Miembros</h6>
                                            <ul id="info_members" class="list-unstyled small mb-0"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

<script>
var baseUrl = '<?= $base_url ?>';

function loadStreets() {
    $.getJSON(baseUrl + '/src/controllers/comunity_street.php?action=list', function(response) {
        if (response.status === 'ok') {
            var $street = $('#id_street');
            $street.html('<option value="">Seleccionar Calle</option>');
            response.data.forEach(function(item) {
                $street.append('<option value="' + item.id_street + '">' + item.name_street + '</option>');
            });
        }
    });
}

function loadSquares(streetId) {
    $.getJSON(baseUrl + '/src/controllers/comunity_square.php?action=list', function(response) {
        if (response.status === 'ok') {
            var $square = $('#id_square');
            $square.html('<option value="">Seleccionar Manzana</option>');
            response.data.filter(function(item) {
                return streetId ? item.id_street == streetId : true;
            }).forEach(function(item) {
                $square.append('<option value="' + item.id_square + '">' + item.codigo_square + '</option>');
            });
        }
    });
}

function loadFamilies() {
    $.getJSON(baseUrl + '/src/controllers/comunity_family.php?action=list', function(response) {
        if (response.status === 'ok') {
            var $family = $('#id_family');
            var current = $family.val();
            $family.html('<option value="">Crear nueva familia</option>');
            response.data.forEach(function(item) {
                $family.append('<option value="' + item.id_family + '">' + item.surname_family + ' (' + item.number_house + ', ' + item.codigo_square + ', ' + item.name_street + ')</option>');
            });
            if (current) $family.val(current);
        }
    });
}

function resetPersonaForm() {
    $('#id_person').val('');
    $('#personaModalLabel').text('Nueva Persona');
    $('#personaForm')[0].reset();
    loadStreets();
    loadSquares(null);
    loadFamilies();
}

$(document).ready(function() {
    var $table = $('#personasTable');
    var dataRows = $table.find('tbody tr[data-id]');
    if (dataRows.length > 0) {
        $table.DataTable({
            language: { url: baseUrl + '/public/vendor/datatables/es-ES.json' },
            responsive: true,
            paging: true,
            pagingType: 'simple_numbers',
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
            dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>t<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>'
        });
    }

    loadStreets();
    loadSquares(null);
    loadFamilies();

    $('#id_street').on('change', function() {
        loadSquares($(this).val());
    });

    $('#personaForm').on('submit', function(e) {
        e.preventDefault();

        var idPerson = $('#id_person').val();
        var action = idPerson ? 'update' : 'create';

        var data = {
            name: $('#name_person').val(),
            ci: $('#ci_person').val(),
            birth: $('#birth_person').val(),
            id_family: $('#id_family').val() || '',
            id_street: $('#id_street').val() || '',
            id_square: $('#id_square').val() || '',
            number_house: $('#number_house').val() || '',
            surname_family: $('#surname_family').val() || ''
        };

        if (idPerson) data.id = idPerson;

        $.ajax({
            url: baseUrl + '/src/controllers/comunity_person.php?action=' + action,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'ok') {
                    $('#personaModal').modal('hide');
                    showToast('success', idPerson ? 'Persona actualizada correctamente' : 'Persona creada correctamente');
                    setTimeout(function() {
                        window.location.reload();
                    }, 700);
                } else {
                    showToast('error', response.message || 'Error al guardar persona');
                }
            },
            error: function() {
                showToast('error', 'Error al procesar la solicitud');
            }
        });
    });
});

function editPerson(id) {
    $.getJSON('src/controllers/comunity_person.php?action=get&id=' + id, function(response) {
        if (response.status !== 'ok') {
            showToast('error', response.message || 'Persona no encontrada');
            return;
        }

        resetPersonaForm();
        var persona = response.data;
        $('#id_person').val(persona.id_person);
        $('#name_person').val(persona.name_person);
        $('#ci_person').val(persona.ci_person);
        $('#birth_person').val(persona.birth_person);
        $('#id_family').val(persona.id_family || '');

        if (persona.id_family) {
            $('#surname_family').val(persona.surname_family || '');
        }

        $('#personaModalLabel').text('Editar Persona');
        $('#personaModal').modal('show');
    });
}

function deletePerson(id) {
    showConfirm('¿Eliminar persona?', '¿Está seguro de eliminar esta persona? Esta acción no se puede deshacer.', 'Eliminar', 'Cancelar')
        .then(function(confirmed) {
            if (!confirmed) return;

            $.post('src/controllers/comunity_person.php?action=delete', {
                id: id,
                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
            }, function(response) {
                if (response.status === 'ok') {
                    showToast('success', 'Persona eliminada correctamente');
                    setTimeout(function() { window.location.reload(); }, 700);
                } else {
                    showToast('error', response.message || 'No se pudo eliminar la persona');
                }
            }, 'json');
        });
}

function showMoreInfo(id) {
    $.getJSON(baseUrl + '/src/controllers/comunity_person.php?action=details&id=' + id, function(response) {
        if (response.status !== 'ok') {
            showToast('error', response.message || 'No se pudo obtener información');
            return;
        }

        var data = response.data;
        var person = data.person || {};
        var members = data.members || [];

        $('#info_name').text(person.name_person || '-');
        $('#info_ci').text(person.ci_person || '-');
        $('#info_birth').text(person.birth_person ? new Date(person.birth_person).toLocaleDateString() : '-');
        $('#info_surname').text(person.surname_family || '-');
        $('#info_id_family').text(person.id_family || '-');
        $('#info_number_house').text(person.number_house || '-');
        $('#info_codigo_square').text(person.codigo_square || '-');
        $('#info_name_street').text(person.name_street || '-');

        var $members = $('#info_members');
        $members.empty();
        if (members.length === 0) {
            $members.append('<li class="list-group-item small text-muted">No hay miembros registrados</li>');
        } else {
            members.forEach(function(m) {
                var b = m.birth_person ? (new Date(m.birth_person)).toLocaleDateString() : '-';
                $members.append('<li class="list-group-item small">' + $('<div>').text(m.name_person).html() + ' — C.I. ' + (m.ci_person || '-') + ' — Nac: ' + b + '</li>');
            });
        }

        var modalEl = document.getElementById('personInfoModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    }).fail(function() {
        showToast('error', 'Error al solicitar información');
    });
}

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
    // Campos de solo letras: Nombre y Apellido
    let letterRegex = /[^\p{L}\s]/gu;
    restrictInput(document.querySelector('input[name="name_person"]'), letterRegex);
    restrictInput(document.querySelector('input[name="surname_family"]'), letterRegex);
    
    // Campo de solo números: Cédula
    let numberRegex = /[^\d]/g;
    restrictInput(document.querySelector('input[name="ci_person"]'), numberRegex);
});

// Añadir clase al body cuando el modal de info está abierto para estilizar el backdrop
$(document).on('show.bs.modal', '#personInfoModal', function() {
    $('body').addClass('person-info-open');
});

$(document).on('hidden.bs.modal', '#personInfoModal', function() {
    $('body').removeClass('person-info-open');
});
</script>
