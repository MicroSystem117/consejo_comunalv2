<?php
// Vista de Personas
$title = 'Personas - Consejo Comunal';
$active_page = 'personas';
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
            <form id="personaForm" method="POST" action="?view=personas&mode=save">
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
                        <label for="id_family" class="form-label">Familia</label>
                        <select class="form-select" id="id_family" name="id_family">
                            <option value="">Seleccionar Familia</option>
                            <?php if (!empty($familias)): ?>
                                <?php foreach ($familias as $f): ?>
                                    <option value="<?= $f['id_family'] ?>">
                                        <?= htmlspecialchars($f['surname_family']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
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
var personas = <?= json_encode($personas ?? []) ?>;

// Initialize DataTable for personas
$(document).ready(function() {
    var $table = $('#personasTable');
    var dataRows = $table.find('tbody tr[data-id]');
    
    if (dataRows.length > 0) {
        $table.DataTable({
            language: {
                url: '<?= $base_url ?>/public/vendor/datatables/es-ES.json'
            },
            responsive: true,
            dom: '<"row"<"col-sm-12"f>t>'
        });
    }
});

// Function to handle persona form submission
$('#personaForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#personaModal').modal('hide');
                showToast('success', response.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('error', response.message);
            }
        },
        error: function(xhr) {
            showToast('error', 'Error al procesar la solicitud');
        }
    });
});

function editPerson(id) {
    // Load persona data and populate form
    $.getJSON('?view=personas&mode=get&id=' + id, function(data) {
        $('#personaModalLabel').text('Editar Persona');
        $('input[name="name_person"]').val(data.name_person);
        $('input[name="ci_person"]').val(data.ci_person);
        $('input[name="birth_person"]').val(data.birth_person);
        $('select[name="id_family"]').val(data.id_family);
        $('#personaForm').append('<input type="hidden" name="id_person" value="' + id + '">');
        $('#personaModal').modal('show');
    });
}

function deletePerson(id) {
    if (confirm('¿Está seguro de eliminar esta persona?')) {
        $.post('?view=personas&mode=delete', { id_person: id, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
            if (response.success) {
                showToast('success', response.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('error', response.message);
            }
        }, 'json');
    }
}
</script>
