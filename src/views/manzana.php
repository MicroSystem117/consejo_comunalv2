<?php
// Vista de Manzana
$title = 'Manzana - Consejo Comunal';
$active_page = 'manzana';
$userLevel = (int) ($_SESSION['id_level'] ?? 3);
$showActions = $userLevel !== 3;
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-grid-3x3"></i> Gestión de Manzana</h1>
    <div class="page-actions">
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV('manzanaTable', 'manzana.csv')">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
            <?php if ($userLevel !== 3): ?>
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="modal" data-bs-target="#manzanaModal">
                    <i class="bi bi-plus"></i> Nueva Manzana
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabla de Manzana -->
<div class="table-container fade-in">
    <table id="manzanaTable" class="table table-striped table-hover table-sm">
        <thead>
            <tr>
                <th>Código</th>
                <th>Calle</th>
                <th>Manzana</th>
                <?php if ($showActions): ?>
                    <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($manzana)): ?>
                <?php foreach ($manzana as $m): ?>
                    <tr data-id="<?php echo $m['id_square']; ?>">
                        <td><?php echo htmlspecialchars($m['codigo_square']); ?></td>
                        <td><?php echo htmlspecialchars($m['name_street'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo htmlspecialchars($m['name_square']); ?></td>
                        <?php if ($showActions): ?>
                            <td class="actions">
                                <button class="btn btn-sm btn-warning" onclick="editManzana(<?php echo $m['id_square']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteManzana(<?php echo $m['id_square']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $showActions ? 4 : 3 ?>" class="text-center text-muted">No hay manzanas registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Nueva/Editar Manzana -->
<div class="modal fade" id="manzanaModal" tabindex="-1" aria-labelledby="manzanaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manzanaModalLabel">Nueva Manzana</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="manzanaForm" method="POST" action="?view=manzana&mode=save" accept-charset="UTF-8">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="codigo_square" class="form-label">Código de Manzana</label>
                        <input type="text" class="form-control" id="codigo_square" name="codigo_square" required placeholder="Ej: M-1">
                    </div>
                    <div class="mb-3">
                        <label for="id_street" class="form-label">Calle</label>
                        <select class="form-select" id="id_street" name="id_street" required>
                            <option value="">Seleccionar Calle</option>
                            <?php if (!empty($calles)): ?>
                                <?php foreach ($calles as $calle): ?>
                                    <option value="<?php echo $calle['id_street']; ?>">
                                        <?php echo htmlspecialchars($calle['name_street']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name_square" class="form-label">Nombre de Manzana</label>
                        <input type="text" class="form-control" id="name_square" name="name_square" required placeholder="Ej: Manzana 1">
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
// Initialize DataTable for manzana table
$(document).ready(function() {
    var $table = $('#manzanaTable');
    var dataRows = $table.find('tbody tr[data-id]');
    
    if (dataRows.length > 0) {
        $table.DataTable({
            language: {
                url: '<?= $base_url ?>/public/vendor/datatables/es-ES.json'
            },
            responsive: true,
            paging: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>t<"row"<"col-sm-6"i><"col-sm-6"p>>'
        });
    }
});

// Function to handle manzana form submission
$('#manzanaForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#manzanaModal').modal('hide');
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

function editManzana(id) {
    // Load manzana data and populate form
    $.getJSON('?view=manzana&mode=get&id=' + id, function(data) {
        $('#manzanaModalLabel').text('Editar Manzana');
        $('input[name="codigo_square"]').val(data.codigo_square);
        $('select[name="id_street"]').val(data.id_street);
        $('input[name="name_square"]').val(data.name_square);
        $('#manzanaForm').append('<input type="hidden" name="id_square" value="' + id + '">');
        $('#manzanaModal').modal('show');
    });
}

function deleteManzana(id) {
    showConfirm('¿Eliminar manzana?', '¿Está seguro de eliminar esta manzana? Esta acción no se puede deshacer.', 'Eliminar', 'Cancelar')
        .then(function(confirmed) {
            if (!confirmed) return;

            $.post('?view=manzana&mode=delete', { id_square: id, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('error', response.message);
                }
            }, 'json');
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
    // Campo alfanumérico: Nombre de la Manzana (letras, números, espacios y guiones)
    let alnumRegex = /[^\p{L}\p{N}\s\-]/gu;
    restrictInput(document.querySelector('input[name="name_square"]'), alnumRegex);
});
</script>
