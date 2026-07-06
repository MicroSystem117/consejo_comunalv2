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
                <th>Número</th>
                <th>Manzana</th>
                <th>Calle</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($viviendas)): ?>
                <?php foreach ($viviendas as $v): ?>
                    <tr data-id="<?= $v['id_house'] ?>">
                        <td><?= htmlspecialchars($v['number_house']) ?></td>
                        <td><?= htmlspecialchars($v['codigo_square'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($v['name_street'] ?? 'Sin asignar') ?></td>
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
                    <td colspan="4" class="text-center text-muted">No hay viviendas registradas</td>
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
            <form id="viviendaForm" method="POST" action="?view=viviendas&mode=save" accept-charset="UTF-8">
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
                    <!-- Tipo de vivienda eliminado: no existe columna en la tabla `house` -->
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
            paging: true,
            pagingType: 'simple_numbers',
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
            dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>t<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>'
        });
    }
});

// Function to handle vivienda form submission
$('#viviendaForm').on('submit', function(e) {
    e.preventDefault();
    // Ensure modal-stored id is included if present
    var modalId = $('#viviendaModal').data('id');
    if (modalId) {
        if ($('#viviendaForm').find('input[name="id_house"]').length === 0) {
            $('#viviendaForm').append('<input type="hidden" name="id_house" value="' + modalId + '">');
        }
    }
    // If editing and the select for id_square is empty, temporarily remove its name and required
    var $select = $('#viviendaForm').find('select[name="id_square"]');
    var selectName = $select.attr('name');
    var selectWasRequired = $select.prop('required');
    var removedSelect = false;
    if (modalId && (!$select.val() || $select.val() === '')) {
        $select.removeAttr('name');
        $select.prop('required', false);
        removedSelect = true;
    }

    var dataToSend = $(this).serialize();

    // restore select attributes
    if (removedSelect) {
        $select.attr('name', selectName);
        if (selectWasRequired) $select.prop('required', true);
    }

    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: dataToSend,
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

function resetViviendaForm() {
    $('#viviendaForm')[0].reset();
    $('#viviendaForm').find('input[name="id_house"]').remove();
    $('#viviendaModalLabel').text('Nueva Vivienda');
        // ensure select is required by default for new records
        $('#viviendaForm').find('select[name="id_square"]').prop('required', true);
}

function editVivienda(id) {
    // Load vivienda data and populate form
    $.getJSON('?view=viviendas&mode=get&id=' + id, function(data) {
        resetViviendaForm();
        $('#viviendaModalLabel').text('Editar Vivienda');

        // number_house: prefer server value, fallback to visible table cell
        var numberVal = data.number_house || '';
        if (!numberVal) {
            var rowText = $('tr[data-id="' + id + '"]').find('td').first().text() || '';
            numberVal = rowText.trim();
        }
        $('input[name="number_house"]').val(numberVal);

        // Ensure select has the option for id_square; if not, try to add a friendly option
        var $select = $('select[name="id_square"]');
        var squareId = data.id_square || '';
        if (squareId && $select.find('option[value="' + squareId + '"]').length === 0) {
            var label = data.codigo_square ? (data.codigo_square + ' - ' + (data.name_street || '')) : ('Plaza ' + squareId);
            $select.append('<option value="' + squareId + '">' + label + '</option>');
        }
        $select.val(squareId || '');
        // If editing and there's no square selected, disable HTML5 required so Enter doesn't block submission
        if (!squareId) {
            $select.prop('required', false);
            $('#viviendaModal').data('clearSelectRequired', true);
        } else {
            $select.prop('required', true);
            $('#viviendaModal').removeData('clearSelectRequired');
        }

        // ensure no duplicate hidden id inputs
        $('#viviendaForm').find('input[name="id_house"]').remove();
        $('#viviendaForm').append('<input type="hidden" name="id_house" value="' + id + '">');
        $('#viviendaModal').data('id', id);
        $('#viviendaModal').modal('show');
    });
}

$('#viviendaModal').on('hidden.bs.modal', function() {
    resetViviendaForm();
    $(this).removeData('id');
        // restore select required if we had cleared it
        var $select = $('#viviendaForm').find('select[name="id_square"]');
        if ($(this).data('clearSelectRequired')) {
            $select.prop('required', true);
            $(this).removeData('clearSelectRequired');
        }
});

function deleteVivienda(id) {
    showConfirm('¿Eliminar vivienda?', '¿Está seguro de eliminar esta vivienda? Esta acción no se puede deshacer.', 'Eliminar', 'Cancelar')
        .then(function(confirmed) {
            if (!confirmed) return;

            $.post('?view=viviendas&mode=delete', { id_house: id, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('error', response.message);
                }
            }, 'json');
        });
}
</script>
