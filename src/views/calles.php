<?php
// Vista de Calles
$title = 'Calles - Consejo Comunal';
$active_page = 'calles';
$userLevel = (int) ($_SESSION['id_level'] ?? 3);
$showActions = $userLevel !== 3;
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-signpost"></i> Gestión de Calles</h1>
    <div class="page-actions">
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportCallesCSV()">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
            <?php if ($userLevel !== 3): ?>
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="modal" data-bs-target="#calleModal">
                    <i class="bi bi-plus"></i> Nueva Calle
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Grid de calles -->
<div class="table-container fade-in">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Calles</h5>
            <small class="text-muted">Vistas en tarjetas</small>
        </div>
        <div class="ms-2">
            <input id="callesSearch" type="search" class="form-control form-control-sm" placeholder="Buscar por código o nombre...">
        </div>
    </div>

    <div id="callesGrid" class="row g-3">
        <?php if (!empty($calles)): ?>
            <?php foreach ($calles as $c): ?>
                <div class="col-12 col-sm-6 col-md-4" data-id="<?= $c['id_street'] ?>">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-1"><?= htmlspecialchars($c['codigo_street']) ?></h6>
                            <p class="fw-bold mb-2"><?= htmlspecialchars($c['name_street']) ?></p>
                            <div class="d-flex gap-2">
                                <?php if ($showActions): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editCalle(<?= $c['id_street'] ?>)">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCalle(<?= $c['id_street'] ?>)">
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
                <div class="alert alert-info mb-0">No hay calles registradas</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Nueva/Editar Calle -->
<div class="modal fade" id="calleModal" tabindex="-1" aria-labelledby="calleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calleModalLabel">Nueva Calle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="calleForm" method="POST" action="?view=calles&mode=save" accept-charset="UTF-8">
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
// Grid search and export for calles
$(document).ready(function() {
    $('#callesSearch').on('input', function() {
        var q = $(this).val().toLowerCase().trim();
        $('#callesGrid').find('[data-id]').each(function() {
            var code = $(this).find('.card-title').text().toLowerCase();
            var name = $(this).find('.fw-bold').text().toLowerCase();
            if (!q || code.indexOf(q) !== -1 || name.indexOf(q) !== -1) $(this).show(); else $(this).hide();
        });
    });

    window.exportCallesCSV = function() {
        var rows = [];
        rows.push(['codigo', 'name']);
        $('#callesGrid').find('[data-id]').each(function() {
            var $c = $(this);
            var codigo = $c.find('.card-title').text().trim();
            var nombre = $c.find('.fw-bold').text().trim();
            rows.push([codigo, nombre]);
        });
        var csv = rows.map(function(r){ return r.map(function(cell){ return '"' + (String(cell).replace(/"/g,'""')) + '"'; }).join(','); }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'calles.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };
});

// Function to handle calle form submission
$('#calleForm').on('submit', function(e) {
    e.preventDefault();

    // Ensure modal-stored id is included if present (robust against missing hidden inputs)
    var modalId = $('#calleModal').data('id');
    if (modalId) {
        if ($('#calleForm').find('input[name="id_street"]').length === 0) {
            $('#calleForm').append('<input type="hidden" name="id_street" value="' + modalId + '">');
        }
    }

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
        // ensure no duplicate hidden id inputs
        $('#calleForm').find('input[name="id_street"]').remove();
        $('#calleForm').append('<input type="hidden" name="id_street" value="' + id + '">');
        // also store id on modal element to be extra-safe
        $('#calleModal').data('id', id);
        $('#calleModal').modal('show');
    });
}

// Clean up modal when hidden
$('#calleModal').on('hidden.bs.modal', function() {
    $('#calleForm').find('input[name="id_street"]').remove();
    $(this).removeData('id');
    $('#calleForm')[0].reset();
    $('#calleModalLabel').text('Nueva Calle');
});

function deleteCalle(id) {
    showConfirm('¿Eliminar calle?', '¿Está seguro de eliminar esta calle? Esta acción no se puede deshacer.', 'Eliminar', 'Cancelar')
        .then(function(confirmed) {
            if (!confirmed) return;

            $.post('?view=calles&mode=delete', { id_street: id, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
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
    // Campo alfanumérico: Nombre de la Calle (letras, números, espacios y guiones)
    let alnumRegex = /[^\p{L}\p{N}\s\-]/gu;
    restrictInput(document.querySelector('input[name="name_street"]'), alnumRegex);
});
</script>
