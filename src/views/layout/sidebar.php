<?php
// src/views/layout/sidebar.php
$userLevel = (int) ($_SESSION['id_level'] ?? 3);
$sidebarItems = obtenerMenuItemsPorNivel($userLevel);
?>
<div class="app-container">
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-menu-button-wide"></i> Menú</h3>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <?php foreach ($sidebarItems as $menuItem): ?>
                    <?php $label = obtenerLabelMenu($menuItem['view'], $userLevel) ?: $menuItem['label']; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page === $menuItem['view'] ? 'active' : '') ?>" href="<?= $base_url ?>/<?= $menuItem['route'] ?>">
                            <i class="bi <?= htmlspecialchars($menuItem['icon']) ?>"></i>
                            <?= htmlspecialchars($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <div class="main-wrapper">
        <main class="main-content">
<?php
// Modal portal - will be used to move modals outside the sidebar container
$GLOBALS['modalPortalNeeded'] = true;
?>
