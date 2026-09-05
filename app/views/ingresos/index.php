<?php
$tituloPagina = 'Ingresos';
$activeMenu = 'ingresos';
require __DIR__ . '/../layout/header.php';

$fmtQ = fn($monto) => 'Q' . number_format((float) $monto, 2);

$construirQuery = function (array $filtros, $pagina) {
    return http_build_query([
        'c' => 'ingresos',
        'a' => 'index',
        'desde' => $filtros['desde'],
        'hasta' => $filtros['hasta'],
        'categoria' => $filtros['id_categoria'],
        'tipo' => $filtros['id_tipo_ingreso'],
        'pagina' => $pagina,
    ]);
};
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h4 mb-1">Ingresos</h1>
    <?php if ($puedeGestionar): ?>
        <a href="index.php?c=ingresos&a=create" class="btn btn-warning text-dark fw-semibold">
            <i class="bi bi-plus-lg"></i> Nuevo Ingreso
        </a>
    <?php endif; ?>
</div>
<p class="text-muted mb-4">Registro de diezmos, ofrendas, donaciones y actividades especiales — <?= htmlspecialchars($mesActualNombre) ?></p>

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
                <p class="text-muted small mb-1">Promedio Diario</p>
                <p class="h4 mb-0"><?= $fmtQ($kpis['promedio_diario']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="ingresos">
            <input type="hidden" name="a" value="index">

            <div class="col-md-3">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>">
            </div>
            <div class="col-md-3">
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
            <div class="col-md-2">
                <label class="form-label small text-muted">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= $tipo['id_tipo_ingreso'] ?>" <?= (string) $filtros['id_tipo_ingreso'] === (string) $tipo['id_tipo_ingreso'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tipo['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-teal w-100"><i class="bi bi-funnel"></i></button>
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
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Aportante</th>
                    <th class="text-end">Monto</th>
                    <th class="text-center">Comprobante</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ingresos)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se encontraron ingresos.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($ingresos as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['fecha_ingreso']) ?></td>
                        <td><?= htmlspecialchars($fila['tipo_nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['aportante_nombre'] ?? '—') ?></td>
                        <td class="text-end fw-semibold"><?= $fmtQ($fila['monto']) ?></td>
                        <td class="text-center">
                            <?php if (!empty($fila['factura_numero'])): ?>
                                <a href="index.php?c=facturas&a=verArchivo&id=<?= $fila['id_factura'] ?>" target="_blank" title="Ver documento <?= htmlspecialchars($fila['factura_numero']) ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                            <?php elseif ($fila['tiene_adjunto_legado']): ?>
                                <a href="index.php?c=ingresos&a=verComprobante&id=<?= $fila['id_ingreso'] ?>" target="_blank" title="Ver comprobante">
                                    <i class="bi bi-eye"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($puedeGestionar): ?>
                                <a
                                    href="index.php?c=ingresos&a=edit&id=<?= $fila['id_ingreso'] ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Editar"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="post"
                                    action="index.php?c=ingresos&a=anular"
                                    class="d-inline"
                                    onsubmit="return confirm(<?= !empty($fila['factura_numero']) ? "'¿Anular este ingreso? También se anulará el documento vinculado (" . htmlspecialchars($fila['factura_numero'], ENT_QUOTES) . ").'" : "'¿Anular este ingreso? Esta acción no se puede deshacer desde el listado.'" ?>);"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= $fila['id_ingreso'] ?>">
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
        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="text-muted small">Mostrando <?= count($ingresos) ?> de <?= $total ?> ingresos</span>
            <span class="chip-total-filtrado"><i class="bi bi-calculator"></i> Total filtrado: <?= $fmtQ($totalFiltrado) ?></span>
        </div>

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
