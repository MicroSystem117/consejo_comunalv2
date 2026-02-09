<?php
// Vista de Viviendas
$title = 'Viviendas - Consejo Comunal';
$active_page = 'viviendas';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-house"></i> Gestión de Viviendas</h1>
    <div class="page-actions">
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV('viviendasTable', 'viviendas.csv')">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="modal" data-bs-target="#viviendaModal">
                <i class="bi bi-plus"></i> Nueva Vivienda
            </button>
        </div>
    </div>
</div>

<!-- Tabla de viviendas -->
<div class="table-container fade-in">
    <table class="table table-striped table-hover table-sm" id="viviendasTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Número</th>
                <th>Manzana</th>
                <th>Calle</th>
                <th>Tipo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($viviendas)): ?>
                <?php foreach ($viviendas as $v): ?>
                    <tr data-id="<?= $v['id_house'] ?>">
                        <td><?= $v['id_house'] ?></td>
                        <td><?= htmlspecialchars($v['number_house']) ?></td>
                        <td><?= htmlspecialchars($v['codigo_square'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($v['name_street'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($v['type_house'] ?? '-') ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-warning" onclick="editVivienda(<?= $v['id_house'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteVivienda(<?= $v['id_house'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay viviendas registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Nueva/Editar Vivienda -->
<div class="modal fade" id="viviendaModal" tabindex="-1" aria-labelledby="viviendaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viviendaModalLabel">Nueva Vivienda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="viviendaForm" method="POST" action="?view=viviendas&mode=save">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="number_house" class="form-label">Número de Vivienda</label>
                        <input type="text" class="form-control" id="number_house" name="number_house" required placeholder="Ej: 1-A">
                    </div>
                    <div class="mb-3">
                        <label for="id_square" class="form-label">Manzana</label>
                        <select class="form-select" id="id_square" name="id_square" required>
                            <option value="">Seleccionar Manzana</option>
                            <?php if (!empty($manzana)): ?>
                                <?php foreach ($manzana as $m): ?>
                                    <option value="<?= $m['id_square'] ?>">
                                        <?= htmlspecialchars($m['codigo_square'] . ' - ' . ($m['name_street'] ?? 'Sin calle')) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type_house" class="form-label">Tipo de Vivienda</label>
                        <select class="form-select" id="type_house" name="type_house">
                            <option value="Casa">Casa</option>
                            <option value="Apartamento">Apartamento</option>
                            <option value="Rancho">Rancho</option>
                            <option value="Otro">Otro</option>
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
// Initialize DataTable for viviendas table
$(document).ready(function() {
    var $table = $('#viviendasTable');
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

// Function to handle vivienda form submission
$('#viviendaForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#viviendaModal').modal('hide');
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

function editVivienda(id) {
    // Load vivienda data and populate form
    $.getJSON('?view=viviendas&mode=get&id=' + id, function(data) {
        $('#viviendaModalLabel').text('Editar Vivienda');
        $('input[name="number_house"]').val(data.number_house);
        $('select[name="id_square"]').val(data.id_square);
        $('select[name="type_house"]').val(data.type_house);
        $('#viviendaForm').append('<input type="hidden" name="id_house" value="' + id + '">');
        $('#viviendaModal').modal('show');
    });
}

function deleteVivienda(id) {
    if (confirm('¿Está seguro de eliminar esta vivienda?')) {
        $.post('?view=viviendas&mode=delete', { id_house: id, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
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
