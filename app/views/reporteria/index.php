<?php
$tituloPagina = 'Reportería';
$activeMenu = 'reporteria';
require __DIR__ . '/../layout/header.php';

$fmtQ = fn($monto) => 'Q' . number_format((float) $monto, 2);
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
                    <option value="ingresos" <?= $filtros['tipo'] === 'ingresos' ? 'selected' : '' ?>>Reporte de Ingresos</option>
                    <option value="egresos" <?= $filtros['tipo'] === 'egresos' ? 'selected' : '' ?>>Reporte de Egresos</option>
                </select>
            </div>

            <div class="col-md-3">
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
                        Reporte: <?= $filtros['tipo'] === 'egresos' ? 'Egresos' : 'Ingresos' ?> ·
                        Período: <?= htmlspecialchars($filtros['desde']) ?> al <?= htmlspecialchars($filtros['hasta']) ?> ·
                        Generado: <?= $resultado['generado'] ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?c=reporteria&a=exportarCsv&<?= $queryExport ?>" class="btn btn-warning text-dark btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                    </a>
                    <a href="index.php?c=reporteria&a=exportarPdf&<?= $queryExport ?>" target="_blank" class="btn btn-warning text-dark btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card bg-light border-0 h-100">
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

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-dark">
                        <?php if ($filtros['tipo'] === 'egresos'): ?>
                            <tr><th>Fecha</th><th>Categoría</th><th>Beneficiario</th><th>Descripción</th><th class="text-end">Monto</th></tr>
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
                        <tr class="table-light fw-semibold">
                            <td colspan="4" class="text-end">TOTAL DEL PERÍODO:</td>
                            <td class="text-end"><?= $fmtQ($resultado['total']) ?></td>
                        </tr>
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
    const categoriaSeleccionada = '<?= htmlspecialchars($filtros['categoria']) ?>';

    function cargarCategorias() {
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
