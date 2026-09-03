<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../models/Bitacora.php';

class BitacoraController extends Controller {
    private const POR_PAGINA = 15;

    public function index() {
        Middleware::requirePermiso('bitacora');

        $bitacoraModel = new Bitacora();
        $filtros = $this->obtenerFiltros();

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $total = $bitacoraModel->contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $totalPaginas);

        $this->view('bitacora.index', [
            'usuario' => Auth::user(),
            'eventos' => $bitacoraModel->listar($filtros, $pagina, self::POR_PAGINA),
            'usuarios' => $bitacoraModel->listarUsuarios(),
            'modulos' => $bitacoraModel->listarModulos(),
            'acciones' => $bitacoraModel->listarAcciones(),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
        ]);
    }

    public function exportarCsv() {
        Middleware::requirePermiso('bitacora');

        $bitacoraModel = new Bitacora();
        $filtros = $this->obtenerFiltros();
        $eventos = $bitacoraModel->listar($filtros, 1, 1000000);

        Bitacora::registrar('Exportar', 'Bitácora', 'Exportación del registro de auditoría');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bitacora.csv"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Fecha/Hora', 'Usuario', 'Módulo', 'Acción', 'Descripción', 'IP Origen']);

        foreach ($eventos as $evento) {
            fputcsv($salida, [
                $evento['fecha_evento'],
                $evento['nombre_usuario'],
                $evento['modulo'],
                $evento['accion'],
                $evento['descripcion'],
                $evento['ip_origen'],
            ]);
        }

        fclose($salida);
        exit;
    }

    private function obtenerFiltros() {
        return [
            'desde' => $_GET['desde'] ?? '',
            'hasta' => $_GET['hasta'] ?? '',
            'nombre_usuario' => $_GET['usuario'] ?? '',
            'modulo' => $_GET['modulo'] ?? '',
            'accion' => $_GET['accion'] ?? '',
        ];
    }
}
