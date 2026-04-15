<?php
// Vista de Estadísticas del sistema
$title = 'Estadísticas - Consejo Comunal';
$active_page = 'statistics';
?>

<div class="page-header fade-in">
    <h1><i class="bi bi-bar-chart-line"></i> Estadísticas de la Comunidad</h1>
    <div class="page-actions">
        <button class="btn btn-outline-primary" onclick="window.location.reload();">
            <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
    </div>
</div>

<div class="row mb-4 fade-in">
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['personas'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Personas</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-house-door text-success" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['familias'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Familias</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-house text-info" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['viviendas'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Viviendas</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-signpost text-warning" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['calles'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Calles</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-grid-3x3-gap text-secondary" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['manzana'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Manzanas</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-calendar-event text-danger" style="font-size: 2rem;"></i>
                <h3 class="mt-3"><?= $stats['eventos'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Eventos</p>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-bar-chart-line-fill"></i> Familias con más miembros</span>
            </div>
            <div class="card-body">
                <?php if (!empty($topFamilies)): ?>
                    <div class="list-group">
                        <?php foreach ($topFamilies as $family): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($family['surname_family']) ?></strong>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($family['number_house'] . ' - ' . $family['codigo_square'] . ' / ' . $family['name_street']) ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?= $family['members'] ?> miembros</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No hay familia registrada o no se encontraron datos.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-calendar-check"></i> Próximos eventos</span>
            </div>
            <div class="card-body">
                <?php if (!empty($upcomingEvents)): ?>
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div class="mb-3">
                            <h6 class="mb-1"><?= htmlspecialchars($event['title']) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($event['event_date'] . ($event['event_time'] ? ' • ' . $event['event_time'] : '')) ?></small>
                            <?php if (!empty($event['description'])): ?>
                                <p class="mb-0 mt-2 text-muted"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No hay eventos próximos en el calendario.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
