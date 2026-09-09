<?php
$tituloPagina = 'Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../layout/header.php';

$fmtQ = fn($monto) => 'Q' . number_format((float) $monto, 2);

$badgeVariacion = function ($variacion) {
    if ($variacion['nuevo']) {
        return '<span class="small text-success"><i class="bi bi-arrow-up"></i> Nuevo</span>';
    }
    if ($variacion['pct'] === null) {
        return '';
    }
    $pct = round($variacion['pct'], 1);
    $clase = $pct >= 0 ? 'text-success' : 'text-danger';
    $icono = $pct >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
    return "<span class=\"small $clase\"><i class=\"bi $icono\"></i> " . abs($pct) . "% vs período anterior</span>";
};
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
    <div>
        <h1 class="h4 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Panel general de indicadores financieros — <?= htmlspecialchars($etiquetaPeriodo) ?></p>
    </div>
</div>

<?php
$nombresMeses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$anioActual = (int) date('Y');
?>
<form method="get" action="index.php" class="d-flex flex-wrap align-items-end gap-2 my-3">
    <input type="hidden" name="c" value="home">
    <input type="hidden" name="a" value="index">
    <div>
        <label class="form-label small text-muted mb-1">Mes</label>
        <select name="mes" class="form-select" onchange="this.form.submit()">
            <?php foreach ($nombresMeses as $num => $nombre): ?>
                <option value="<?= $num ?>" <?= $mes === $num ? 'selected' : '' ?>><?= $nombre ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label small text-muted mb-1">Año</label>
        <select name="anio" class="form-select" onchange="this.form.submit()">
            <?php for ($a = $anioActual - 4; $a <= $anioActual + 1; $a++): ?>
                <option value="<?= $a ?>" <?= $anio === $a ? 'selected' : '' ?>><?= $a ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-teal"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Total Ingresos</p>
                <p class="h4 mb-1"><?= $fmtQ($kpis['total_ingresos']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Total Egresos</p>
                <p class="h4 mb-1"><?= $fmtQ($kpis['total_egresos']) ?></p>
                <?= $badgeVariacion($variacionEgresos) ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Disponible Caja General</p>
                <p class="h4 mb-1 <?= $disponibleCajaGeneral >= 0 ? 'text-success' : 'text-danger' ?>"><?= $fmtQ($disponibleCajaGeneral) ?></p>
                <span class="small text-muted">Acumulado histórico</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Movimientos</p>
                <p class="h4 mb-1"><?= $kpis['movimientos'] ?></p>
                <span class="small text-muted"><?= $kpis['cantidad_ingresos'] ?> ingresos · <?= $kpis['cantidad_egresos'] ?> egresos</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Ingresos vs Egresos por Mes</h2>
                <div class="chart-box">
                    <canvas id="chartIngresosEgresos"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Distribución de Ingresos por Tipo</h2>
                <?php if (empty($distribucionIngresos)): ?>
                    <p class="text-muted small mb-0">Sin ingresos registrados en este período.</p>
                <?php else: ?>
                    <p class="text-center fw-semibold text-teal-dark mb-1" style="font-size: 1rem;" id="etiquetaDistribucion">Total</p>
                    <div class="chart-box chart-box-donut">
                        <canvas id="chartDistribucion"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Comparativo Mensual del Año (Balance)</h2>
                <div class="chart-box chart-box-comparativo">
                    <canvas id="chartComparativo"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Distribución de Egresos por Categoría</h2>
                <?php if (empty($distribucionEgresos)): ?>
                    <p class="text-muted small mb-0">Sin egresos registrados en este período.</p>
                <?php else: ?>
                    <p class="text-center fw-semibold text-teal-dark mb-1" style="font-size: 1rem;" id="etiquetaDistribucionEgresos">Total</p>
                    <div class="chart-box chart-box-donut">
                        <canvas id="chartDistribucionEgresos"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
(function () {
    const colorTeal = '#0f4c5c';
    const colorAmber = '#d98c22';
    const colorRed = '#c0392b';
    const colorGreen = '#2e9e6f';

    Chart.register(ChartDataLabels);
    Chart.defaults.set('plugins.datalabels', { display: false });

    const fmtQCompleto = function (valor) {
        const num = Number(valor) || 0;
        return 'Q' + num.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    new Chart(document.getElementById('chartIngresosEgresos'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($serieMensual['etiquetas']) ?>,
            datasets: [
                {
                    label: 'Ingresos',
                    data: <?= json_encode($serieMensual['ingresos']) ?>,
                    backgroundColor: colorGreen,
                },
                {
                    label: 'Egresos',
                    data: <?= json_encode($serieMensual['egresos']) ?>,
                    backgroundColor: colorRed,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 18 } },
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    display: true,
                    anchor: 'end',
                    align: 'top',
                    color: '#495057',
                    font: { size: 10, weight: '600' },
                    formatter: function (valor) { return valor > 0 ? fmtQCompleto(valor) : null; },
                },
            },
            scales: { y: { beginAtZero: true } },
        },
    });

    function crearDonutConTotal(canvasId, etiquetaElementId, datos, etiquetas, colores) {
        const total = datos.reduce(function (a, b) { return a + b; }, 0);
        const elementoEtiqueta = document.getElementById(etiquetaElementId);

        const pluginTotalCentro = {
            id: 'totalCentro',
            afterDraw: function (chart) {
                const indicesVisibles = [];
                datos.forEach(function (_, i) {
                    if (chart.getDataVisibility(i)) { indicesVisibles.push(i); }
                });

                let etiqueta = 'Total';
                let suma = total;

                if (indicesVisibles.length === 0) {
                    etiqueta = 'Total';
                    suma = 0;
                } else if (indicesVisibles.length === 1) {
                    etiqueta = etiquetas[indicesVisibles[0]];
                    suma = datos[indicesVisibles[0]];
                } else if (indicesVisibles.length < datos.length) {
                    etiqueta = 'Subtotal';
                    suma = indicesVisibles.reduce(function (acumulado, i) { return acumulado + datos[i]; }, 0);
                }

                // El nombre/etiqueta se muestra fuera de la dona (arriba, en el DOM)
                // para que no se corte cuando el nombre de la categoría es largo;
                // dentro del círculo solo queda el monto.
                if (elementoEtiqueta) {
                    elementoEtiqueta.textContent = etiqueta;
                }

                const ctx = chart.ctx;
                const area = chart.chartArea;
                const cx = (area.left + area.right) / 2;
                const cy = (area.top + area.bottom) / 2;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#16324a';
                ctx.font = 'bold 13px "Segoe UI", sans-serif';
                ctx.fillText(fmtQCompleto(suma), cx, cy);
                ctx.restore();
            },
        };

        return new Chart(document.getElementById(canvasId), {
            type: 'doughnut',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: datos,
                    backgroundColor: colores,
                }],
            },
            plugins: [pluginTotalCentro],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom' },
                    datalabels: {
                        display: true,
                        color: '#fff',
                        font: { size: 11, weight: '700' },
                        formatter: function (valor) {
                            if (!total) { return ''; }
                            const pct = (valor / total) * 100;
                            return pct >= 5 ? pct.toFixed(0) + '%' : '';
                        },
                    },
                },
            },
        });
    }

    <?php if (!empty($distribucionIngresos)): ?>
    crearDonutConTotal(
        'chartDistribucion',
        'etiquetaDistribucion',
        <?= json_encode(array_map('floatval', array_column($distribucionIngresos, 'total'))) ?>,
        <?= json_encode(array_column($distribucionIngresos, 'nombre')) ?>,
        [colorTeal, colorAmber, colorGreen, colorRed, '#7c92a3']
    );
    <?php endif; ?>

    <?php if (!empty($distribucionEgresos)): ?>
    crearDonutConTotal(
        'chartDistribucionEgresos',
        'etiquetaDistribucionEgresos',
        <?= json_encode(array_map('floatval', array_column($distribucionEgresos, 'total'))) ?>,
        <?= json_encode(array_column($distribucionEgresos, 'nombre')) ?>,
        [colorRed, colorAmber, colorTeal, colorGreen, '#7c92a3']
    );
    <?php endif; ?>

    new Chart(document.getElementById('chartComparativo'), {
        type: 'line',
        data: {
            labels: <?= json_encode($comparativoAnual['etiquetas']) ?>,
            datasets: [{
                label: 'Balance',
                data: <?= json_encode($comparativoAnual['balance']) ?>,
                borderColor: colorTeal,
                backgroundColor: 'rgba(15, 76, 92, 0.15)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: colorTeal,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 22 } },
            plugins: {
                legend: { display: false },
                datalabels: {
                    display: true,
                    align: 'top',
                    color: '#495057',
                    font: { size: 10, weight: '600' },
                    formatter: function (valor) { return valor !== 0 ? fmtQCompleto(valor) : null; },
                },
            },
            scales: { y: { beginAtZero: true } },
        },
    });
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
