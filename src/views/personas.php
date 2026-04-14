
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
                        <td class="actions">
                            <button class="btn btn-sm btn-warning" onclick="editPerson(<?= $p['id_person'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deletePerson(<?= $p['id_person'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                            <!-- Botón PDF corregido -->
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
            <form id="personaForm" method="POST" action="">
                <input type="hidden" id="id_person" name="id_person" value="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name_person" class="form-label">Nombre de la Persona</label>
                        <input type="text" class="form-control" id="name_person" name="name_person" required placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="mb-3">
                        <label for="ci_person" class="form-label">Cédula de Identidad</label>
                        <input type="number" class="form-control" id="ci_person" name="ci_person" required placeholder="Ej: 12345678">
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
            dom: '<"row"<"col-sm-12"f>t>'
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
    if (!confirm('¿Está seguro de eliminar esta persona?')) {
        return;
    }

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
}
</script>
