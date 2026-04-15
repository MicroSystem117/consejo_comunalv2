<?php
// Vista de Calles
$title = 'Calles - Consejo Comunal';
$active_page = 'calles';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-signpost"></i> Gestión de Calles</h1>
    <div class="page-actions">
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV('callesTable', 'calles.csv')">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="modal" data-bs-target="#calleModal">
                <i class="bi bi-plus"></i> Nueva Calle
            </button>
        </div>
    </div>
</div>

<!-- Tabla de calles -->
<div class="table-container fade-in">
    <table class="table table-striped table-hover table-sm" id="callesTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre de la Calle</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($calles)): ?>
                <?php foreach ($calles as $c): ?>
                    <tr data-id="<?= $c['id_street'] ?>">
                        <td><?= $c['id_street'] ?></td>
                        <td><?= htmlspecialchars($c['codigo_street']) ?></td>
                        <td><?= htmlspecialchars($c['name_street']) ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-warning" onclick="editCalle(<?= $c['id_street'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCalle(<?= $c['id_street'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">No hay calles registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Nueva/Editar Calle -->
<div class="modal fade" id="calleModal" tabindex="-1" aria-labelledby="calleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calleModalLabel">Nueva Calle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="calleForm" method="POST" action="?view=calles&mode=save">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="codigo_street" class="form-label">Código de Calle</label>
                        <input type="text" class="form-control" id="codigo_street" name="codigo_street" required placeholder="Ej: C-1">
                    </div>
                    <div class="mb-3">
                        <label for="name_street" class="form-label">Nombre de la Calle</label>
                        <input type="text" class="form-control" id="name_street" name="name_street" required placeholder="Ej: Calle 1">
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
// Initialize DataTable for calles table
$(document).ready(function() {
    var $table = $('#callesTable');
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

// Function to handle calle form submission
$('#calleForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#calleModal').modal('hide');
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

function editCalle(id) {
    // Load calle data and populate form
    $.getJSON('?view=calles&mode=get&id=' + id, function(data) {
        $('#calleModalLabel').text('Editar Calle');
        $('input[name="codigo_street"]').val(data.codigo_street);
        $('input[name="name_street"]').val(data.name_street);
        $('#calleForm').append('<input type="hidden" name="id_street" value="' + id + '">');
        $('#calleModal').modal('show');
    });
}

function deleteCalle(id) {
    if (confirm('¿Está seguro de eliminar esta calle?')) {
        $.post('?view=calles&mode=delete', { id_street: id, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
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
