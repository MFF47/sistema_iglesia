<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Bitacora.php';

class ReporteriaController extends Controller {
    public function index() {
        Middleware::requirePermiso('reporteria');

        $filtros = $this->obtenerFiltros();
        $resultado = $filtros['generar'] ? $this->generar($filtros) : null;

        $categoriaModel = new Categoria();

        $this->view('reporteria.index', [
            'usuario' => Auth::user(),
            'filtros' => $filtros,
            'resultado' => $resultado,
            'categoriasIngreso' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'ingreso', 'activo' => '1'], 1, 100),
            'categoriasEgreso' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'egreso', 'activo' => '1'], 1, 100),
        ]);
    }

    public function exportarCsv() {
        Middleware::requirePermiso('reporteria');

        $filtros = $this->obtenerFiltros();

        if (!$filtros['generar']) {
            $this->redirect('index.php?c=reporteria&a=index');
        }

        $resultado = $this->generar($filtros);

        Bitacora::registrar(
            'Exportar',
            'Reportería',
            'Exportación de reporte de ' . ($filtros['tipo'] === 'egresos' ? 'Egresos' : 'Ingresos')
                . " — Período {$filtros['desde']} al {$filtros['hasta']} — Formato Excel"
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . $filtros['tipo'] . '_' . $filtros['desde'] . '_a_' . $filtros['hasta'] . '.csv"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM para acentos correctos en Excel

        if ($filtros['tipo'] === 'egresos') {
            fputcsv($salida, ['Fecha', 'Categoría', 'Beneficiario', 'Descripción', 'Monto']);
            foreach ($resultado['filas'] as $fila) {
                fputcsv($salida, [$fila['fecha_egreso'], $fila['categoria_nombre'], $fila['beneficiario'], $fila['descripcion'], $fila['monto']]);
            }
        } else {
            fputcsv($salida, ['Fecha', 'Tipo', 'Categoría', 'Aportante', 'Monto']);
            foreach ($resultado['filas'] as $fila) {
                fputcsv($salida, [$fila['fecha_ingreso'], $fila['tipo_nombre'], $fila['categoria_nombre'], $fila['aportante_nombre'] ?? '', $fila['monto']]);
            }
        }

        fputcsv($salida, []);
        fputcsv($salida, ['Total del período', $resultado['total']]);
        fclose($salida);
        exit;
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
            'Exportación de reporte de ' . ($filtros['tipo'] === 'egresos' ? 'Egresos' : 'Ingresos')
                . " — Período {$filtros['desde']} al {$filtros['hasta']} — Formato PDF"
        );

        $this->view('reporteria.imprimir', [
            'filtros' => $filtros,
            'resultado' => $resultado,
        ]);
    }

    private function obtenerFiltros() {
        $tipo = ($_GET['tipo'] ?? 'ingresos') === 'egresos' ? 'egresos' : 'ingresos';

        return [
            'tipo' => $tipo,
            'categoria' => $_GET['categoria'] ?? '',
            'desde' => $_GET['desde'] ?? '',
            'hasta' => $_GET['hasta'] ?? '',
            'generar' => !empty($_GET['desde']) && !empty($_GET['hasta']),
        ];
    }

    private function generar(array $filtros) {
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
}
