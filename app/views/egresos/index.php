<?php
$tituloPagina = 'Egresos';
$activeMenu = 'egresos';
require __DIR__ . '/../layout/header.php';

$fmtQ = fn($monto) => 'Q' . number_format((float) $monto, 2);

$construirQuery = function (array $filtros, $pagina) {
    return http_build_query([
        'c' => 'egresos',
        'a' => 'index',
        'desde' => $filtros['desde'],
        'hasta' => $filtros['hasta'],
        'categoria' => $filtros['id_categoria'],
        'beneficiario' => $filtros['beneficiario'],
        'pagina' => $pagina,
    ]);
};
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h4 mb-1">Egresos</h1>
    <?php if ($puedeGestionar): ?>
        <a href="index.php?c=egresos&a=create" class="btn btn-warning text-dark fw-semibold">
            <i class="bi bi-plus-lg"></i> Nuevo Egreso
        </a>
    <?php endif; ?>
</div>
<p class="text-muted mb-4">Registro de gastos operativos, proyectos y mantenimiento — <?= htmlspecialchars($mesActualNombre) ?></p>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipo'] === 'success' ? 'success' : 'danger') ?>">
        <?= htmlspecialchars($flash['mensaje']) ?>
    </div>
<?php endif; ?>

<div class="card card-disponible mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-safe2"></i> Disponible Caja General</span>
        <span class="h4 mb-0"><?= $fmtQ($disponibleCajaGeneral) ?></span>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Total de <?= htmlspecialchars($mesActualNombre) ?></p>
                <p class="h4 mb-0 text-teal-dark"><?= $fmtQ($kpis['total']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Cantidad de Registros</p>
                <p class="h4 mb-0"><?= $kpis['cantidad'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Monto Promedio</p>
                <p class="h4 mb-0"><?= $fmtQ($kpis['promedio']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="egresos">
            <input type="hidden" name="a" value="index">

            <div class="col-md-2">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Categoría</label>
                <select name="categoria" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>" <?= (string) $filtros['id_categoria'] === (string) $cat['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Beneficiario</label>
                <input type="text" name="beneficiario" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($filtros['beneficiario']) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-teal w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Beneficiario</th>
                    <th>Descripción</th>
                    <th class="text-end">Monto</th>
                    <th>Factura</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($egresos)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se encontraron egresos.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($egresos as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['fecha_egreso']) ?></td>
                        <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['beneficiario']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($fila['descripcion']) ?></td>
                        <td class="text-end fw-semibold"><?= $fmtQ($fila['monto']) ?></td>
                        <td>
                            <?php if ($fila['factura_numero']): ?>
                                <span class="badge text-bg-light border"><i class="bi bi-receipt"></i> <?= htmlspecialchars($fila['factura_numero']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($puedeGestionar): ?>
                                <a
                                    href="index.php?c=egresos&a=edit&id=<?= $fila['id_egreso'] ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Editar"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="post"
                                    action="index.php?c=egresos&a=anular"
                                    class="d-inline"
                                    onsubmit="return confirm(<?= !empty($fila['factura_numero']) ? "'¿Anular este egreso? También se anulará el documento vinculado (" . htmlspecialchars($fila['factura_numero'], ENT_QUOTES) . ").'" : "'¿Anular este egreso? Esta acción no se puede deshacer desde el listado.'" ?>);"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= $fila['id_egreso'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Anular">
                                        <i class="bi bi-slash-circle"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-body d-flex justify-content-between align-items-center border-top flex-wrap gap-2">
        <span class="text-muted small">
            Mostrando <?= count($egresos) ?> de <?= $total ?> egresos — Total filtrado: <?= $fmtQ($totalFiltrado) ?>
        </span>

        <?php if ($totalPaginas > 1): ?>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?<?= $construirQuery($filtros, $p) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
