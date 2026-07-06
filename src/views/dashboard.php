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
        <div class="card dashboard-stat-card">
            <div class="card-body text-center">
                <i class="bi bi-people text-primary dashboard-stat-icon"></i>
                <h3 class="dashboard-stat-number"><?= $stats['personas'] ?? 0 ?></h3>
                <p class="text-muted dashboard-stat-label">Personas Registradas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stat-card">
            <div class="card-body text-center">
                <i class="bi bi-house-door text-success dashboard-stat-icon"></i>
                <h3 class="dashboard-stat-number"><?= $stats['familias'] ?? 0 ?></h3>
                <p class="text-muted dashboard-stat-label">Familias</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stat-card">
            <div class="card-body text-center">
                <i class="bi bi-house text-info dashboard-stat-icon"></i>
                <h3 class="dashboard-stat-number"><?= $stats['viviendas'] ?? 0 ?></h3>
                <p class="text-muted dashboard-stat-label">Viviendas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stat-card">
            <div class="card-body text-center">
                <i class="bi bi-signpost text-warning dashboard-stat-icon"></i>
                <h3 class="dashboard-stat-number"><?= $stats['calles'] ?? 0 ?></h3>
                <p class="text-muted dashboard-stat-label">Calles</p>
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

<!-- Graficos de pastel -->
<div class="row mt-4 fade-in">
    <div class="col-md-6">
        <div class="card dashboard-chart-card">
            <div class="card-header">
                <span><i class="bi bi-pie-chart"></i> Distribución Principal</span>
            </div>
            <div class="card-body">
                <canvas id="mainPieChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card dashboard-chart-card">
            <div class="card-header">
                <span><i class="bi bi-pie-chart-fill"></i> Eventos vs Otros</span>
            </div>
            <div class="card-body">
                <canvas id="eventsPieChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Incluir Chart.js localmente (coloca aquí la librería Chart.js v4 UMD) -->
<script src="<?= $base_url ?>/public/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('mainPieChart').getContext('2d');
    var mainData = [
        <?= (int)($stats['personas'] ?? 0) ?>,
        <?= (int)($stats['familias'] ?? 0) ?>,
        <?= (int)($stats['viviendas'] ?? 0) ?>,
        <?= (int)($stats['calles'] ?? 0) ?>
    ];
    var mainLabels = ['Personas','Familias','Viviendas','Calles'];
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: mainLabels,
            datasets: [{
                data: mainData,
                backgroundColor: ['#4e73df','#1cc88a','#36b9cc','#f6c23e']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    var ctx2 = document.getElementById('eventsPieChart').getContext('2d');
    var eventsCount = <?= (int)($stats['eventos'] ?? 0) ?>;
    var others = (<?= (int)($stats['personas'] ?? 0) ?> + <?= (int)($stats['familias'] ?? 0) ?> + <?= (int)($stats['viviendas'] ?? 0) ?> + <?= (int)($stats['calles'] ?? 0) ?>) - eventsCount;
    new Chart(ctx2, {
        type: 'pie',
        data: {
            labels: ['Eventos','Otros'],
            datasets: [{ data: [eventsCount, Math.max(0, others)], backgroundColor: ['#e74a3b','#858796'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>

<script>
function refreshData() {
    window.location.reload();
}
</script>
