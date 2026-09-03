<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de <?= $filtros['tipo'] === 'egresos' ? 'Egresos' : 'Ingresos' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print mb-3 text-end">
        <button class="btn btn-teal" onclick="window.print()">Imprimir / Guardar como PDF</button>
    </div>

    <h3 class="mb-1">Iglesia Centroamericana Nueva Vida</h3>
    <p class="text-muted">Reporte de <?= $filtros['tipo'] === 'egresos' ? 'Egresos' : 'Ingresos' ?></p>
    <p class="small">
        Período: <?= htmlspecialchars($filtros['desde']) ?> al <?= htmlspecialchars($filtros['hasta']) ?> ·
        Generado: <?= $resultado['generado'] ?>
    </p>

    <table class="table table-sm table-bordered mt-3">
        <thead class="table-dark">
            <?php if ($filtros['tipo'] === 'egresos'): ?>
                <tr><th>Fecha</th><th>Categoría</th><th>Beneficiario</th><th>Descripción</th><th class="text-end">Monto</th></tr>
            <?php else: ?>
                <tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Aportante</th><th class="text-end">Monto</th></tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php foreach ($resultado['filas'] as $fila): ?>
                <?php if ($filtros['tipo'] === 'egresos'): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['fecha_egreso']) ?></td>
                        <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['beneficiario']) ?></td>
                        <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                        <td class="text-end">Q<?= number_format($fila['monto'], 2) ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['fecha_ingreso']) ?></td>
                        <td><?= htmlspecialchars($fila['tipo_nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['aportante_nombre'] ?? '—') ?></td>
                        <td class="text-end">Q<?= number_format($fila['monto'], 2) ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td colspan="4" class="text-end">TOTAL DEL PERÍODO:</td>
                <td class="text-end">Q<?= number_format($resultado['total'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
