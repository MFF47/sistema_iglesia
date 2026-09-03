<?php
$tituloPagina = 'Facturas y Recibos';
$activeMenu = 'facturas';
require __DIR__ . '/../layout/header.php';

$fmtQ = fn($monto) => 'Q' . number_format((float) $monto, 2);

$etiquetasTipo = [
    'factura' => 'Factura',
    'recibo' => 'Recibo',
    'nota_credito' => 'Nota de Crédito',
];

$construirQuery = function (array $filtros, $pagina) {
    return http_build_query([
        'c' => 'facturas',
        'a' => 'index',
        'buscar' => $filtros['buscar'],
        'tipo' => $filtros['tipo_documento'],
        'desde' => $filtros['desde'],
        'hasta' => $filtros['hasta'],
        'pagina' => $pagina,
    ]);
};
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h4 mb-1">Facturas y Recibos</h1>
    <?php if ($puedeGestionar): ?>
        <a href="index.php?c=facturas&a=create" class="btn btn-warning text-dark fw-semibold">
            <i class="bi bi-plus-lg"></i> Nueva Factura
        </a>
    <?php endif; ?>
</div>
<p class="text-muted mb-4">Documentos que respaldan los movimientos económicos</p>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipo'] === 'success' ? 'success' : 'danger') ?>">
        <?= htmlspecialchars($flash['mensaje']) ?>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="facturas">
            <input type="hidden" name="a" value="index">

            <div class="col-md-4">
                <label class="form-label small text-muted">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Nº documento o beneficiario" value="<?= htmlspecialchars($filtros['buscar']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($etiquetasTipo as $valor => $etiqueta): ?>
                        <option value="<?= $valor ?>" <?= $filtros['tipo_documento'] === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>">
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
                    <th>Nº Documento</th>
                    <th>Tipo</th>
                    <th>Beneficiario</th>
                    <th>Fecha Emisión</th>
                    <th class="text-end">Monto</th>
                    <th>Vinculado a</th>
                    <th class="text-center">Archivo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facturas)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No se encontraron documentos.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($facturas as $fila): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($fila['numero_documento'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($etiquetasTipo[$fila['tipo_documento']] ?? $fila['tipo_documento']) ?></td>
                        <td><?= htmlspecialchars($fila['beneficiario'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($fila['fecha_emision']) ?></td>
                        <td class="text-end fw-semibold"><?= $fmtQ($fila['monto']) ?></td>
                        <td>
                            <?php if ($fila['egreso_vinculado_id']): ?>
                                <span class="badge text-bg-light border">Egreso #<?= $fila['egreso_vinculado_id'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">— Sin vincular</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($fila['ruta_archivo']): ?>
                                <a href="index.php?c=facturas&a=verArchivo&id=<?= $fila['id_factura'] ?>" target="_blank" title="Ver archivo">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <?= strtoupper(pathinfo($fila['ruta_archivo'], PATHINFO_EXTENSION)) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($puedeGestionar): ?>
                                <a
                                    href="index.php?c=facturas&a=edit&id=<?= $fila['id_factura'] ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Editar"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="post"
                                    action="index.php?c=facturas&a=anular"
                                    class="d-inline"
                                    onsubmit="return confirm('¿Anular este documento?');"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= $fila['id_factura'] ?>">
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
        <span class="text-muted small">Mostrando <?= count($facturas) ?> de <?= $total ?> documentos</span>

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
