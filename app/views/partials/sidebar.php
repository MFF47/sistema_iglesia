<?php
require_once __DIR__ . '/../../../core/Permisos.php';

$menuSidebar = [
    [
        'titulo' => 'PRINCIPAL',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'href' => 'index.php?c=home&a=index'],
        ],
    ],
    [
        'titulo' => 'MOVIMIENTOS',
        'items' => [
            ['key' => 'ingresos', 'label' => 'Ingresos', 'icon' => 'bi-arrow-down-circle', 'href' => 'index.php?c=ingresos&a=index'],
            ['key' => 'egresos', 'label' => 'Egresos', 'icon' => 'bi-arrow-up-circle', 'href' => 'index.php?c=egresos&a=index'],
            ['key' => 'facturas', 'label' => 'Facturas y Recibos', 'icon' => 'bi-receipt', 'href' => 'index.php?c=facturas&a=index'],
        ],
    ],
    [
        'titulo' => 'GESTIÓN',
        'items' => [
            ['key' => 'categorias', 'label' => 'Categorías', 'icon' => 'bi-tags', 'href' => 'index.php?c=categorias&a=index'],
            ['key' => 'aportantes', 'label' => 'Aportantes', 'icon' => 'bi-person-badge', 'href' => 'index.php?c=aportantes&a=index'],
            ['key' => 'usuarios', 'label' => 'Usuarios y Roles', 'icon' => 'bi-people', 'href' => 'index.php?c=usuarios&a=index'],
        ],
    ],
    [
        'titulo' => 'REPORTES',
        'items' => [
            ['key' => 'reporteria', 'label' => 'Reportería', 'icon' => 'bi-bar-chart', 'href' => 'index.php?c=reporteria&a=index'],
            ['key' => 'bitacora', 'label' => 'Bitácora', 'icon' => 'bi-journal-text', 'href' => 'index.php?c=bitacora&a=index'],
        ],
    ],
];
$rolActual = $usuario['rol_nombre'] ?? null;
?>
<aside class="sidebar">
    <?php foreach ($menuSidebar as $seccion): ?>
        <?php
        $itemsVisibles = array_filter(
            $seccion['items'],
            fn($item) => Permisos::puedeAcceder($item['key'], $rolActual)
        );
        ?>
        <?php if (empty($itemsVisibles)) continue; ?>

        <div class="sidebar-section-label"><?= htmlspecialchars($seccion['titulo']) ?></div>
        <?php foreach ($itemsVisibles as $item): ?>
            <a
                href="<?= htmlspecialchars($item['href']) ?>"
                class="sidebar-link <?= ($activeMenu ?? '') === $item['key'] ? 'active' : '' ?>"
            >
                <i class="bi <?= htmlspecialchars($item['icon']) ?>"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
</aside>
