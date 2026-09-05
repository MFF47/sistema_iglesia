<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Bitacora.php';
require_once __DIR__ . '/../../core/ExcelExporter.php';
require_once __DIR__ . '/../../core/PdfExporter.php';

class ReporteriaController extends Controller {
    public function index() {
        Middleware::requirePermiso('reporteria');

        $filtros = $this->obtenerFiltros();
        $resultado = $filtros['generar'] ? $this->generar($filtros) : null;

        if ($resultado) {
            Bitacora::registrar(
                'Consultar',
                'Reportería',
                'Generación de reporte de ' . $this->etiquetaTipo($filtros['tipo'])
                    . " — Período {$filtros['desde']} al {$filtros['hasta']} — {$resultado['cantidad']} registros"
            );
        }

        $categoriaModel = new Categoria();

        $this->view('reporteria.index', [
            'usuario' => Auth::user(),
            'filtros' => $filtros,
            'resultado' => $resultado,
            'categoriasIngreso' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'ingreso', 'activo' => '1'], 1, 100),
            'categoriasEgreso' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'egreso', 'activo' => '1'], 1, 100),
        ]);
    }

    public function exportarExcel() {
        Middleware::requirePermiso('reporteria');

        $filtros = $this->obtenerFiltros();

        if (!$filtros['generar']) {
            $this->redirect('index.php?c=reporteria&a=index');
        }

        $resultado = $this->generar($filtros);

        Bitacora::registrar(
            'Exportar',
            'Reportería',
            'Exportación de reporte de ' . $this->etiquetaTipo($filtros['tipo'])
                . " — Período {$filtros['desde']} al {$filtros['hasta']} — Formato Excel"
        );

        if ($filtros['tipo'] === 'egresos') {
            $encabezados = ['Fecha', 'Categoría', 'Beneficiario', 'Descripción', 'Monto'];
            $anchos = [14, 22, 26, 40, 16];
            $filas = array_map(fn($f) => [$f['fecha_egreso'], $f['categoria_nombre'], $f['beneficiario'], $f['descripcion'], (float) $f['monto']], $resultado['filas']);
            $columnaVacia = array_fill(0, count($encabezados) - 2, '');
            $totales = array_merge($columnaVacia, ['TOTAL DEL PERÍODO:', (float) $resultado['total']]);
        } elseif ($filtros['tipo'] === 'consolidado') {
            $encabezados = ['Fecha', 'Tipo', 'Categoría', 'Detalle', 'Monto'];
            $anchos = [14, 12, 22, 26, 16];
            $filas = array_map(fn($f) => [$f['fecha'], $f['tipo_movimiento'], $f['categoria_nombre'], $f['detalle'], (float) $f['monto']], $resultado['filas']);
            $totales = [
                ['', '', '', 'TOTAL INGRESOS:', (float) $resultado['total_ingresos']],
                ['', '', '', 'TOTAL EGRESOS:', (float) $resultado['total_egresos']],
                ['', '', '', 'BALANCE:', (float) $resultado['total']],
            ];
        } else {
            $encabezados = ['Fecha', 'Tipo', 'Categoría', 'Aportante', 'Monto'];
            $anchos = [14, 18, 22, 26, 16];
            $filas = array_map(fn($f) => [$f['fecha_ingreso'], $f['tipo_nombre'], $f['categoria_nombre'], $f['aportante_nombre'] ?? '—', (float) $f['monto']], $resultado['filas']);
            $columnaVacia = array_fill(0, count($encabezados) - 2, '');
            $totales = array_merge($columnaVacia, ['TOTAL DEL PERÍODO:', (float) $resultado['total']]);
        }

        ExcelExporter::generar(
            'Reporte de ' . $this->etiquetaTipo($filtros['tipo']) . ' — Iglesia Centroamericana Nueva Vida',
            "Período: {$filtros['desde']} al {$filtros['hasta']} — Generado: " . date('d/m/Y H:i'),
            $encabezados,
            $filas,
            $anchos,
            'reporte_' . $filtros['tipo'] . '_' . $filtros['desde'] . '_a_' . $filtros['hasta'] . '.xlsx',
            $totales,
            [4]
        );
    }

    public function exportarPdf() {
        Middleware::requirePermiso('reporteria');

        $filtros = $this->obtenerFiltros();

        if (!$filtros['generar']) {
            $this->redirect('index.php?c=reporteria&a=index');
        }

        $resultado = $this->generar($filtros);

        Bitacora::registrar(
            'Exportar',
            'Reportería',
            'Exportación de reporte de ' . $this->etiquetaTipo($filtros['tipo'])
                . " — Período {$filtros['desde']} al {$filtros['hasta']} — Formato PDF"
        );

        $html = $this->construirHtmlPdf($filtros, $resultado);

        PdfExporter::generar($html, 'reporte_' . $filtros['tipo'] . '_' . $filtros['desde'] . '_a_' . $filtros['hasta'] . '.pdf');
    }

    private function etiquetaTipo($tipo) {
        return match ($tipo) {
            'egresos' => 'Egresos',
            'consolidado' => 'Consolidado',
            default => 'Ingresos',
        };
    }

    private function construirHtmlPdf(array $filtros, array $resultado) {
        $fmtQ = fn($m) => 'Q' . number_format((float) $m, 2);
        $tipo = $filtros['tipo'];

        ob_start();
        ?>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
                .titulo { background: #0c3a46; color: #fff; padding: 14px 18px; font-size: 16px; font-weight: bold; }
                .subtitulo { background: #efefef; color: #555; padding: 6px 18px; font-size: 10px; font-style: italic; }
                table { width: 100%; border-collapse: collapse; margin-top: 14px; }
                th { background: #16324a; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
                td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
                tr:nth-child(even) td { background: #f4f6f8; }
                .text-end { text-align: right; }
                .total-row td { background: #dee2e6; color: #16324a; font-weight: bold; border-top: 2px solid #adb5bd; }
                .badge-ingreso { color: #227355; font-weight: bold; }
                .badge-egreso { color: #a83232; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="titulo">Reporte de <?= $this->etiquetaTipo($tipo) ?> — Iglesia Centroamericana Nueva Vida</div>
            <div class="subtitulo">Período: <?= htmlspecialchars($filtros['desde']) ?> al <?= htmlspecialchars($filtros['hasta']) ?> — Generado: <?= $resultado['generado'] ?></div>

            <table>
                <thead>
                    <?php if ($tipo === 'egresos'): ?>
                        <tr><th>Fecha</th><th>Categoría</th><th>Beneficiario</th><th>Descripción</th><th class="text-end">Monto</th></tr>
                    <?php elseif ($tipo === 'consolidado'): ?>
                        <tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Detalle</th><th class="text-end">Monto</th></tr>
                    <?php else: ?>
                        <tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Aportante</th><th class="text-end">Monto</th></tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php foreach ($resultado['filas'] as $fila): ?>
                        <?php if ($tipo === 'egresos'): ?>
                            <tr>
                                <td><?= htmlspecialchars($fila['fecha_egreso']) ?></td>
                                <td><?= htmlspecialchars($fila['categoria_nombre']) ?></td>
                                <td><?= htmlspecialchars($fila['beneficiario']) ?></td>
                                <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                                <td class="text-end"><?= $fmtQ($fila['monto']) ?></td>
                            </tr>
                        <?php elseif ($tipo === 'consolidado'): ?>
                            <tr>
                                <td><?= htmlspecialchars($fila['fecha']) ?></td>
                                <td class="<?= $fila['tipo_movimiento'] === 'Ingreso' ? 'badge-ingreso' : 'badge-egreso' ?>"><?= htmlspecialchars($fila['tipo_movimiento']) ?></td>
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
                    <?php if ($tipo === 'consolidado'): ?>
                        <tr class="total-row">
                            <td colspan="4" class="text-end">TOTAL INGRESOS:</td>
                            <td class="text-end"><?= $fmtQ($resultado['total_ingresos']) ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4" class="text-end">TOTAL EGRESOS:</td>
                            <td class="text-end"><?= $fmtQ($resultado['total_egresos']) ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4" class="text-end">BALANCE:</td>
                            <td class="text-end"><?= $fmtQ($resultado['total']) ?></td>
                        </tr>
                    <?php else: ?>
                        <tr class="total-row">
                            <td colspan="4" class="text-end">TOTAL DEL PERÍODO:</td>
                            <td class="text-end"><?= $fmtQ($resultado['total']) ?></td>
                        </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function obtenerFiltros() {
        $tipo = $_GET['tipo'] ?? 'ingresos';
        if (!in_array($tipo, ['ingresos', 'egresos', 'consolidado'], true)) {
            $tipo = 'ingresos';
        }

        return [
            'tipo' => $tipo,
            'categoria' => $_GET['categoria'] ?? '',
            'desde' => $_GET['desde'] ?? '',
            'hasta' => $_GET['hasta'] ?? '',
            'generar' => !empty($_GET['desde']) && !empty($_GET['hasta']),
        ];
    }

    private function generar(array $filtros) {
        if ($filtros['tipo'] === 'consolidado') {
            return $this->generarConsolidado($filtros);
        }

        if ($filtros['tipo'] === 'egresos') {
            $modelo = new Egreso();
            $filas = $modelo->listar([
                'desde' => $filtros['desde'],
                'hasta' => $filtros['hasta'],
                'id_categoria' => $filtros['categoria'],
                'beneficiario' => '',
            ], 1, 100000);
        } else {
            $modelo = new Ingreso();
            $filas = $modelo->listar([
                'desde' => $filtros['desde'],
                'hasta' => $filtros['hasta'],
                'id_categoria' => $filtros['categoria'],
                'id_tipo_ingreso' => '',
            ], 1, 100000);
        }

        $total = array_sum(array_column($filas, 'monto'));
        $cantidad = count($filas);

        return [
            'filas' => $filas,
            'total' => $total,
            'cantidad' => $cantidad,
            'promedio' => $cantidad > 0 ? $total / $cantidad : 0,
            'generado' => date('d/m/Y H:i'),
        ];
    }

    private function generarConsolidado(array $filtros) {
        $ingresoModel = new Ingreso();
        $egresoModel = new Egreso();

        $ingresos = $ingresoModel->listar(['desde' => $filtros['desde'], 'hasta' => $filtros['hasta'], 'id_categoria' => '', 'id_tipo_ingreso' => ''], 1, 100000);
        $egresos = $egresoModel->listar(['desde' => $filtros['desde'], 'hasta' => $filtros['hasta'], 'id_categoria' => '', 'beneficiario' => ''], 1, 100000);

        $filas = [];

        foreach ($ingresos as $i) {
            $filas[] = [
                'fecha' => $i['fecha_ingreso'],
                'tipo_movimiento' => 'Ingreso',
                'categoria_nombre' => $i['categoria_nombre'],
                'detalle' => $i['aportante_nombre'] ?? '—',
                'monto' => (float) $i['monto'],
            ];
        }

        foreach ($egresos as $e) {
            $filas[] = [
                'fecha' => $e['fecha_egreso'],
                'tipo_movimiento' => 'Egreso',
                'categoria_nombre' => $e['categoria_nombre'],
                'detalle' => $e['beneficiario'],
                'monto' => (float) $e['monto'],
            ];
        }

        usort($filas, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

        $totalIngresos = array_sum(array_column($ingresos, 'monto'));
        $totalEgresos = array_sum(array_column($egresos, 'monto'));
        $cantidad = count($filas);

        return [
            'filas' => $filas,
            'total' => $totalIngresos - $totalEgresos,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos,
            'cantidad' => $cantidad,
            'cantidad_ingresos' => count($ingresos),
            'cantidad_egresos' => count($egresos),
            'promedio' => $cantidad > 0 ? ($totalIngresos + $totalEgresos) / $cantidad : 0,
            'generado' => date('d/m/Y H:i'),
        ];
    }
}
