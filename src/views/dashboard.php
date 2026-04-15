<?php
// Vista del Dashboard / Panel Principal
$title = 'Dashboard - Consejo Comunal';
$active_page = 'dashboard';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-speedometer2"></i> Panel de Control</h1>
    <div class="page-actions">
        <button class="btn btn-outline-primary" onclick="refreshData()">
            <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
    </div>
</div>

<!-- Tarjetas de estadisticas -->
<div class="row mb-4 fade-in">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['personas'] ?? 0 ?></h3>
                <p class="text-muted">Personas Registradas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-house-door text-success" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['familias'] ?? 0 ?></h3>
                <p class="text-muted">Familias</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-house text-info" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['viviendas'] ?? 0 ?></h3>
                <p class="text-muted">Viviendas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-signpost text-warning" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['calles'] ?? 0 ?></h3>
                <p class="text-muted">Calles</p>
            </div>
        </div>
    </div>
</div>

<!-- Acciones rapidas -->
<div class="row fade-in">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-lightning"></i> Acciones Rapidas</span>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?view=personas&action=add" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Nueva Persona
                    </a>
                    <a href="?view=familias&action=add" class="btn btn-success">
                        <i class="bi bi-house-add"></i> Nueva Familia
                    </a>
                    <a href="?view=viviendas&action=add" class="btn btn-info">
                        <i class="bi bi-house-check"></i> Nueva Vivienda
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-info-circle"></i> Informacion del Sistema</span>
            </div>
            <div class="card-body">
                <p><strong>Version:</strong> 2.0.0</p>
                <p><strong>Fecha:</strong> <?= date('d/m/Y') ?></p>
                <p><strong>Usuario:</strong> <?= $_SESSION['user_name'] ?? 'Invitado' ?></p>
            </div>
        </div>
    </div>
</div>

<script>
function refreshData() {
    window.location.reload();
}
</script>
