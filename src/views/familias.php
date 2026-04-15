<?php
// Vista de Familias
$title = 'Familias - Consejo Comunal';
$active_page = 'familias';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-house-door"></i> Gestión de Familias</h1>
    <div class="page-actions">
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV('familiasTable', 'familias.csv')">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="modal" data-bs-target="#familiaModal">
                <i class="bi bi-plus"></i> Nueva Familia
            </button>
        </div>
    </div>
</div>

<!-- Tabla de familias -->
<div class="table-container fade-in">
    <table class="table table-striped table-hover table-sm" id="familiasTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Apellido Familia</th>
                <th>Vivienda</th>
                <th>Calle</th>
                <th>Manzana</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($familias)): ?>
                <?php foreach ($familias as $f): ?>
                    <tr data-id="<?= $f['id_family'] ?>">
                        <td><?= $f['id_family'] ?></td>
                        <td><?= htmlspecialchars($f['surname_family']) ?></td>
                        <td><?= htmlspecialchars($f['number_house'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($f['name_street'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($f['codigo_square'] ?? 'Sin asignar') ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-warning" onclick="editFamilia(<?= $f['id_family'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteFamilia(<?= $f['id_family'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay familias registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Nueva/Editar Familia -->
<div class="modal fade" id="familiaModal" tabindex="-1" aria-labelledby="familiaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="familiaModalLabel">Nueva Familia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="familiaForm" method="POST" action="?view=familias&mode=save">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="surname_family" class="form-label">Apellido de la Familia</label>
                        <input type="text" class="form-control" id="surname_family" name="surname_family" required placeholder="Ej: Pérez">
                    </div>
                    <div class="mb-3">
                        <label for="id_house" class="form-label">Vivienda</label>
                        <select class="form-select" id="id_house" name="id_house" required>
                            <option value="">Seleccionar Vivienda</option>
                            <?php if (!empty($viviendas)): ?>
                                <?php foreach ($viviendas as $v): ?>
                                    <option value="<?= $v['id_house'] ?>">
                                        <?= htmlspecialchars($v['number_house'] . ' - ' . ($v['name_street'] ?? 'Sin calle')) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="numero_familia" class="form-label">Número de Familia</label>
                        <input type="number" class="form-control" id="numero_familia" name="numero_familia" placeholder="Ej: 1">
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
// Initialize DataTable for familias table
$(document).ready(function() {
    var $table = $('#familiasTable');
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

// Function to handle familia form submission
$('#familiaForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#familiaModal').modal('hide');
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

function editFamilia(id) {
    // Load familia data and populate form
    $.getJSON('?view=familias&mode=get&id=' + id, function(data) {
        $('#familiaModalLabel').text('Editar Familia');
        $('input[name="surname_family"]').val(data.surname_family);
        $('select[name="id_house"]').val(data.id_house);
        $('input[name="numero_familia"]').val(data.numero_familia);
        $('#familiaForm').append('<input type="hidden" name="id_family" value="' + id + '">');
        $('#familiaModal').modal('show');
    });
}

function deleteFamilia(id) {
    if (confirm('¿Está seguro de eliminar esta familia?')) {
        $.post('?view=familias&mode=delete', { id_family: id, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
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
