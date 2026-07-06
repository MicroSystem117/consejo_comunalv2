<?php
// config/permissions.php
// RBAC configuration para el sistema de vistas de la aplicación.

$rbacPermissions = [
    'dashboard' => [1, 2, 3],
    'personas' => [1, 2, 3],
    'familias' => [1, 2],
    'viviendas' => [1, 2],
    'calles' => [1, 2, 3],
    'manzana' => [1, 2, 3],
    'calendar' => [1, 2, 3],
    'statistics' => [1, 2],
    'backup' => [1],
    'usuarios' => [1, 2, 3],
];

$rbacMenuItems = [
    [
        'view' => 'dashboard',
        'label' => 'Inicio',
        'icon' => 'bi-speedometer2',
        'route' => 'index.php',
        'levels' => [1, 2, 3],
    ],
    [
        'view' => 'personas',
        'label' => 'Censo de Personas',
        'icon' => 'bi-people',
        'route' => 'index.php?view=personas',
        'levels' => [1, 2, 3],
    ],
    [
        'view' => 'familias',
        'label' => 'Censo de Familias',
        'icon' => 'bi-house-door',
        'route' => 'index.php?view=familias',
        'levels' => [1, 2],
    ],
    [
        'view' => 'viviendas',
        'label' => 'Censo de Viviendas',
        'icon' => 'bi-house',
        'route' => 'index.php?view=viviendas',
        'levels' => [1, 2],
    ],
    [
        'view' => 'calles',
        'label' => 'Calles',
        'icon' => 'bi-signpost',
        'route' => 'index.php?view=calles',
        'levels' => [1, 2, 3],
    ],
    [
        'view' => 'manzana',
        'label' => 'Manzana',
        'icon' => 'bi-grid-3x3',
        'route' => 'index.php?view=manzana',
        'levels' => [1, 2, 3],
    ],
    [
        'view' => 'calendar',
        'label' => 'Calendario',
        'icon' => 'bi-calendar-event',
        'route' => 'index.php?view=calendar',
        'levels' => [1, 2, 3],
    ],
    [
        'view' => 'statistics',
        'label' => 'Estadísticas',
        'icon' => 'bi-bar-chart-line',
        'route' => 'index.php?view=statistics',
        'levels' => [1, 2],
    ],
    [
        'view' => 'usuarios',
        'label' => 'Editar Usuario',
        'icon' => 'bi-person-gear',
        'route' => 'index.php?view=usuarios',
        'levels' => [1, 2, 3],
    ],
    [
        'view' => 'backup',
        'label' => 'Respaldo',
        'icon' => 'bi-database-check',
        'route' => 'index.php?view=backup',
        'levels' => [1],
    ],
];

/**
 * Valida si el usuario actual puede acceder a la vista solicitada.
 *
 * @param string $vista
 * @return bool
 */
function validarAccesoVista(string $vista): bool
{
    global $rbacPermissions;
    $id_level = (int) ($_SESSION['id_level'] ?? 3);

    // El administrador (nivel 1) tiene acceso completo a todas las vistas.
    if ($id_level === 1) {
        return true;
    }

    return isset($rbacPermissions[$vista]) && in_array($id_level, $rbacPermissions[$vista], true);
}

/**
 * Retorna los items de menú permitidos para un nivel de usuario.
 *
 * @param int $id_level
 * @return array
 */
function obtenerMenuItemsPorNivel(int $id_level): array
{
    global $rbacMenuItems;

    // El Administrador puede ver todos los elementos del menú.
    if ($id_level === 1) {
        return $rbacMenuItems;
    }

    return array_values(array_filter($rbacMenuItems, function ($item) use ($id_level) {
        return in_array($id_level, $item['levels'], true);
    }));
}

/**
 * Retorna el label adaptado según el nivel de rol.
 *
 * @param string $view
 * @param int $id_level
 * @return string
 */
function obtenerLabelMenu(string $view, int $id_level): string
{
    if ($view === 'calles' && $id_level === 3) {
        return 'Ver Datos de Calle';
    }
    if ($view === 'calendar' && $id_level === 3) {
        return 'Incidencias';
    }
    return '';
}
