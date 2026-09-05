<?php
$tituloPagina = 'Reportería';
$activeMenu = 'reporteria';
require __DIR__ . '/../layout/header.php';

$fmtQ = fn($monto) => 'Q' . number_format((float) $monto, 2);

$etiquetasTipo = [
    'ingresos' => 'Reporte de Ingresos',
    'egresos' => 'Reporte de Egresos',
    'consolidado' => 'Reporte Consolidado (Ingresos y Egresos)',
];

$queryExport = http_build_query([
    'c' => 'reporteria',
    'tipo' => $filtros['tipo'],
    'categoria' => $filtros['categoria'],
    'desde' => $filtros['desde'],
    'hasta' => $filtros['hasta'],
]);
?>

<h1 class="h4 mb-1">Generar Reporte</h1>
<p class="text-muted mb-4">Configure los parámetros del reporte y visualice o exporte los resultados</p>

<div class="card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Parámetros del Reporte</h2>

        <form method="get" action="index.php" class="row g-3 align-items-end">
            <input type="hidden" name="c" value="reporteria">
            <input type="hidden" name="a" value="index">

            <div class="col-md-3">
                <label class="form-label">Tipo de Reporte <span class="text-danger">*</span></label>
                <select id="tipo" name="tipo" class="form-select">
                    <?php foreach ($etiquetasTipo as $valor => $etiqueta): ?>
                        <option value="<?= $valor ?>" <?= $filtros['tipo'] === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3" id="grupo-categoria">
                <label class="form-label">Categoría</label>
                <select id="categoria" name="categoria" class="form-select">
                    <option value="">Todas</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Fecha Desde <span class="text-danger">*</span></label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Fecha Hasta <span class="text-danger">*</span></label>
                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>" required>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-teal flex-fill"><i class="bi bi-file-earmark-bar-graph"></i> Generar</button>
                <a href="index.php?c=reporteria&a=index" class="btn btn-outline-secondary" title="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<?php if ($resultado): ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h6 mb-1">Vista Previa del Reporte</h2>
                    <p class="text-muted small mb-0">
                        Reporte: <?= $etiquetasTipo[$filtros['tipo']] ?> ·
                        Período: <?= htmlspecialchars($filtros['desde']) ?> al <?= htmlspecialchars($filtros['hasta']) ?> ·
                        Generado: <?= $resultado['generado'] ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?c=reporteria&a=exportarExcel&<?= $queryExport ?>" class="btn btn-warning text-dark btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                    </a>
                    <a href="index.php?c=reporteria&a=exportarPdf&<?= $queryExport ?>" class="btn btn-warning text-dark btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                    </a>
                </div>
            </div>

            <?php if ($filtros['tipo'] === 'consolidado'): ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card card-total-suave h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">Total Ingresos</p>
                                <p class="h5 mb-0 text-teal-dark"><?= $fmtQ($resultado['total_ingresos']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-total-suave h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">Total Egresos</p>
                                <p class="h5 mb-0 text-teal-dark"><?= $fmtQ($resultado['total_egresos']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-total-suave h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">Balance</p>
                                <p class="h5 mb-0 <?= $resultado['total'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtQ($resultado['total']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">Registros</p>
                                <p class="h5 mb-0"><?= $resultado['cantidad'] ?></p>
                                <span class="small text-muted"><?= $resultado['cantidad_ingresos'] ?> ingresos · <?= $resultado['cantidad_egresos'] ?> egresos</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card card-total-suave h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">Total Período</p>
                                <p class="h5 mb-0 text-teal-dark"><?= $fmtQ($resultado['total']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">Registros</p>
                                <p class="h5 mb-0"><?= $resultado['cantidad'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">Promedio</p>
                                <p class="h5 mb-0"><?= $fmtQ($resultado['promedio']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-dark">
                        <?php if ($filtros['tipo'] === 'egresos'): ?>
                            <tr><th>Fecha</th><th>Categoría</th><th>Beneficiario</th><th>Descripción</th><th class="text-end">Monto</th></tr>
                        <?php elseif ($filtros['tipo'] === 'consolidado'): ?>
                            <tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Detalle</th><th class="text-end">Monto</th></tr>
                        <?php else: ?>
                            <tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Aportante</th><th class="text-end">Monto</th></tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if (empty($resultado['filas'])): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay registros en este período.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($resultado['filas'] as $fila): ?>
                            <?php if ($filtros['tipo'] === 'egresos'): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fila['fecha_egreso']) ?></td>
                                    <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                                    <td><?= htmlspecialchars($fila['beneficiario']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($fila['descripcion']) ?></td>
                                    <td class="text-end"><?= $fmtQ($fila['monto']) ?></td>
                                </tr>
                            <?php elseif ($filtros['tipo'] === 'consolidado'): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fila['fecha']) ?></td>
                                    <td>
                                        <span class="badge <?= $fila['tipo_movimiento'] === 'Ingreso' ? 'text-bg-success' : 'text-bg-danger' ?>">
                                            <?= htmlspecialchars($fila['tipo_movimiento']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                                    <td><?= htmlspecialchars($fila['detalle']) ?></td>
                                    <td class="text-end"><?= $fmtQ($fila['monto']) ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><?= htmlspecialchars($fila['fecha_ingreso']) ?></td>
                                    <td><?= htmlspecialchars($fila['tipo_nombre']) ?></td>
                                    <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                                    <td><?= htmlspecialchars($fila['aportante_nombre'] ?? '—') ?></td>
                                    <td class="text-end"><?= $fmtQ($fila['monto']) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php if ($filtros['tipo'] === 'consolidado'): ?>
                            <tr class="table-light fw-semibold">
                                <td colspan="4" class="text-end">TOTAL INGRESOS:</td>
                                <td class="text-end"><?= $fmtQ($resultado['total_ingresos']) ?></td>
                            </tr>
                            <tr class="table-light fw-semibold">
                                <td colspan="4" class="text-end">TOTAL EGRESOS:</td>
                                <td class="text-end"><?= $fmtQ($resultado['total_egresos']) ?></td>
                            </tr>
                            <tr class="table-warning fw-semibold">
                                <td colspan="4" class="text-end">BALANCE:</td>
                                <td class="text-end"><?= $fmtQ($resultado['total']) ?></td>
                            </tr>
                        <?php else: ?>
                            <tr class="table-light fw-semibold">
                                <td colspan="4" class="text-end">TOTAL DEL PERÍODO:</td>
                                <td class="text-end"><?= $fmtQ($resultado['total']) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    const categoriasIngreso = <?= json_encode($categoriasIngreso) ?>;
    const categoriasEgreso = <?= json_encode($categoriasEgreso) ?>;
    const selectTipo = document.getElementById('tipo');
    const selectCategoria = document.getElementById('categoria');
    const grupoCategoria = document.getElementById('grupo-categoria');
    const categoriaSeleccionada = '<?= htmlspecialchars($filtros['categoria']) ?>';

    function cargarCategorias() {
        if (selectTipo.value === 'consolidado') {
            grupoCategoria.style.display = 'none';
            selectCategoria.value = '';
            return;
        }

        grupoCategoria.style.display = '';
        const lista = selectTipo.value === 'egresos' ? categoriasEgreso : categoriasIngreso;
        selectCategoria.innerHTML = '<option value="">Todas</option>';
        lista.forEach(function (cat) {
            const opcion = document.createElement('option');
            opcion.value = cat.id_categoria;
            opcion.textContent = cat.nombre;
            if (String(cat.id_categoria) === categoriaSeleccionada) {
                opcion.selected = true;
            }
            selectCategoria.appendChild(opcion);
        });
    }

    selectTipo.addEventListener('change', cargarCategorias);
    cargarCategorias();
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
