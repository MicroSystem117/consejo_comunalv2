<?php
// Vista de Backup
$title = 'Respaldo y Restauración - Consejo Comunal';
$active_page = 'backup';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-database-check"></i> Respaldo y Restauración</h1>
</div>

<!-- Mensajes -->
<div id="alertContainer"></div>

<div class="row fade-in">
    <!-- Backup Section -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-download"></i> Crear Respaldo</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Genera un archivo SQL con la estructura y datos completos de las bases de datos seleccionadas.</p>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Base de datos: comunity
                        <span class="badge bg-secondary">Unificada</span>
                    </li>
                </ul>
                <button type="button" class="btn btn-primary w-100" onclick="performBackup()">
                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar Respaldo (.sql)
                </button>
            </div>
        </div>
    </div>
    
    <!-- Restore Section -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-upload"></i> Restaurar Respaldo</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Restaura la base de datos desde un archivo de respaldo SQL previamente descargado.</p>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Información:</strong> Se importará el archivo SQL seleccionado en la base de datos <strong>comunity</strong>.
                </div>
                <form id="restoreForm" enctype="multipart/form-data" accept-charset="UTF-8">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="action" value="restore">
                    <div class="mb-3">
                        <label for="backup_file" class="form-label">Archivo de Respaldo (.sql)</label>
                        <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100" id="restoreBtn">
                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar Base de Datos
                    </button>
                </form>
                <div id="restoreProgress" class="mt-3" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">
                            Restaurando...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup History -->
<div class="row fade-in">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Respaldos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="backupHistoryTable">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Fecha</th>
                                <th>Tamaño</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="backupHistoryBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <?php if (!is_dir(__DIR__ . '/../../backups') || count(glob(__DIR__ . '/../../backups/*.sql')) === 0): ?>
                    <p class="text-center text-muted">No hay respaldos guardados</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Perform backup
function performBackup() {
    // Create form and submit to trigger download through index.php
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = window.baseUrl + '/index.php?view=backup&action=backup';
    
    var csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = '<?php echo $_SESSION['csrf_token']; ?>';
    form.appendChild(csrfToken);
    
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Handle restore form
$('#restoreForm').on('submit', function(e) {
    e.preventDefault();
    
    var fileInput = document.getElementById('backup_file');
    var file = fileInput.files[0];
    
    if (!file) {
        showToast('error', 'Por favor seleccione un archivo');
        return;
    }
    
    if (!file.name.endsWith('.sql')) {
        showToast('error', 'El archivo debe tener extensión .sql');
        return;
    }
    
    var form = this;
    showConfirm('¿Restaurar respaldo?', 'Esta acción importará el respaldo en la base de datos actual.', 'Continuar', 'Cancelar')
        .then(function(confirmed) {
            if (!confirmed) return;

            $('#restoreProgress').show();
            $('#restoreBtn').prop('disabled', true);

            var formData = new FormData(form);

            $.ajax({
                url: window.baseUrl + '/src/controllers/backup.php?action=restore',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    $('#restoreProgress').hide();
                    $('#restoreBtn').prop('disabled', false);
                    
                    if (response.success) {
                        showToast('success', response.message);
                        $('#restoreForm')[0].reset();
                        loadBackupHistory();
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#restoreProgress').hide();
                    $('#restoreBtn').prop('disabled', false);
                    var responseText = (xhr.responseText || '').trim();
                    var message = 'Error al restaurar: ' + error;
                    if (responseText) {
                        try {
                            var parsed = JSON.parse(responseText);
                            if (parsed && parsed.message) {
                                message = parsed.message;
                            }
                        } catch (e) {
                            message = responseText;
                        }
                    }
                    showToast('error', message);
                }
            });
        });
});

// Load backup history
function loadBackupHistory() {
    $.getJSON(window.baseUrl + '/src/controllers/backup.php?mode=list', function(data) {
        var tbody = $('#backupHistoryBody');
        tbody.empty();
        
        if (data.backups && data.backups.length > 0) {
            data.backups.forEach(function(backup) {
                var row = $('<tr>');
                row.append($('<td>').html('<i class="bi bi-filetype-sql"></i> ' + backup.name));
                row.append($('<td>').text(backup.date));
                row.append($('<td>').text(backup.size));
                
                var actions = $('<td>');
                actions.append($('<a>')
                    .attr('href', '<?= $base_url ?>/backups/' + backup.name)
                    .attr('download', '')
                    .addClass('btn btn-sm btn-primary me-1')
                    .html('<i class="bi bi-download"></i>'));
                
                actions.append($('<button>')
                    .addClass('btn btn-sm btn-danger')
                    .html('<i class="bi bi-trash"></i>')
                    .on('click', function() {
                        showConfirm('¿Eliminar respaldo?', '¿Eliminar este respaldo? Esta acción no se puede deshacer.', 'Eliminar', 'Cancelar')
                            .then(function(confirmed) {
                                if (confirmed) {
                                    deleteBackup(backup.name);
                                }
                            });
                    })
                );

                row.append(actions);
                tbody.append(row);
            });
            
            // Initialize DataTable if not already done
                if (!$.fn.DataTable.isDataTable('#backupHistoryTable')) {
                $('#backupHistoryTable').DataTable({
                    language: {
                        url: '<?= $base_url ?>/public/vendor/datatables/es-ES.json'
                    },
                    paging: true,
                    pagingType: 'simple_numbers',
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                    dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>t<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>',
                    order: [[1, 'desc']]
                });
            }
        } else {
            tbody.append($('<tr>')
                .append($('<td colspan="4" class="text-center text-muted">').text('No hay respaldos guardados')));
        }
    }).fail(function() {
        $('#backupHistoryBody').append($('<tr>')
            .append($('<td colspan="4" class="text-center text-muted">').text('No hay respaldos guardados')));
    });
}

// Delete backup
function deleteBackup(filename) {
    $.post('?view=backup&mode=delete', { file: filename, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' }, function(response) {
        if (response.success) {
            showToast('success', response.message);
            loadBackupHistory();
        } else {
            showToast('error', response.message);
        }
    }, 'json');
}

// Initialize on page load
$(document).ready(function() {
    loadBackupHistory();
});
</script>
