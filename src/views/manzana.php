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
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportManzanaCSV()">
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Manzanas</h5>
            <small class="text-muted">Vistas en cuadrícula</small>
        </div>
        <div class="ms-2">
            <input id="manzanaSearch" type="search" class="form-control form-control-sm" placeholder="Buscar por código o nombre...">
        </div>
    </div>

    <div id="manzanaGrid" class="row g-3">
        <?php if (!empty($manzana)): ?>
            <?php foreach ($manzana as $m): ?>
                <div class="col-12 col-sm-6 col-md-4" data-id="<?= $m['id_square'] ?>">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-1"><?= htmlspecialchars($m['codigo_square']) ?></h6>
                            <p class="card-text small text-muted mb-2">Calle: <?= htmlspecialchars($m['name_street'] ?? 'Sin asignar') ?></p>
                            <p class="fw-bold mb-2"><?= htmlspecialchars($m['name_square']) ?></p>
                            <div class="d-flex gap-2">
                                <?php if ($showActions): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editManzana(<?= $m['id_square'] ?>)">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteManzana(<?= $m['id_square'] ?>)">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">No hay manzanas registradas</div>
            </div>
        <?php endif; ?>
    </div>
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
// Grid search and export
$(document).ready(function() {
    $('#manzanaSearch').on('input', function() {
        var q = $(this).val().toLowerCase().trim();
        $('#manzanaGrid').find('[data-id]').each(function() {
            var code = $(this).find('.card-title').text().toLowerCase();
            var name = $(this).find('.fw-bold').text().toLowerCase();
            if (!q || code.indexOf(q) !== -1 || name.indexOf(q) !== -1) $(this).show(); else $(this).hide();
        });
    });

    window.exportManzanaCSV = function() {
        var rows = [];
        rows.push(['codigo', 'calle', 'manzana']);
        $('#manzanaGrid').find('[data-id]').each(function() {
            var $c = $(this);
            var codigo = $c.find('.card-title').text().trim();
            var calle = $c.find('.card-text').text().replace(/^Calle:\s*/i, '').trim();
            var nombre = $c.find('.fw-bold').text().trim();
            rows.push([codigo, calle, nombre]);
        });
        var csv = rows.map(function(r){ return r.map(function(cell){ return '"' + (String(cell).replace(/"/g,'""')) + '"'; }).join(','); }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'manzana.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };
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
        // DEBUG: log server response to help diagnose missing fields
        console.log('editManzana response:', data);

        $('#manzanaModalLabel').text('Editar Manzana');
        $('input[name="codigo_square"]').val(data.codigo_square || '');
        $('select[name="id_street"]').val(data.id_street || '');
        $('input[name="name_square"]').val(data.name_square || '');
        // ensure no duplicate hidden id inputs
        $('#manzanaForm').find('input[name="id_square"]').remove();
        $('#manzanaForm').append('<input type="hidden" name="id_square" value="' + id + '">');
        $('#manzanaModal').data('id', id);
        $('#manzanaModal').modal('show');
    });
}

// Ensure id included on submit and clean modal on hide
$('#manzanaForm').on('submit', function(e) {
    e.preventDefault();
    var modalId = $('#manzanaModal').data('id');
    if (modalId) {
        if ($('#manzanaForm').find('input[name="id_square"]').length === 0) {
            $('#manzanaForm').append('<input type="hidden" name="id_square" value="' + modalId + '">');
        }
    }

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

$('#manzanaModal').on('hidden.bs.modal', function() {
    $('#manzanaForm').find('input[name="id_square"]').remove();
    $(this).removeData('id');
    $('#manzanaForm')[0].reset();
    $('#manzanaModalLabel').text('Nueva Manzana');
});

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
