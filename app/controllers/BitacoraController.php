<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../models/Bitacora.php';
require_once __DIR__ . '/../../core/PdfExporter.php';

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

    public function exportarPdf() {
        Middleware::requirePermiso('bitacora');

        $bitacoraModel = new Bitacora();
        $filtros = $this->obtenerFiltros();
        $eventos = $bitacoraModel->listar($filtros, 1, 1000000);

        Bitacora::registrar('Exportar', 'Bitácora', 'Exportación del registro de auditoría');

        $html = $this->construirHtmlPdf($eventos);

        PdfExporter::generar($html, 'bitacora_' . date('Ymd_His') . '.pdf', 'landscape');
    }

    private function construirHtmlPdf(array $eventos) {
        ob_start();
        ?>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #222; }
                .titulo { background: #0c3a46; color: #fff; padding: 12px 16px; font-size: 15px; font-weight: bold; }
                .subtitulo { background: #efefef; color: #555; padding: 5px 16px; font-size: 9px; font-style: italic; }
                table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                th { background: #16324a; color: #fff; padding: 5px 7px; text-align: left; font-size: 9px; }
                td { padding: 4px 7px; border-bottom: 1px solid #ddd; font-size: 9px; vertical-align: top; }
                tr:nth-child(even) td { background: #f4f6f8; }
            </style>
        </head>
        <body>
            <div class="titulo">Bitácora de Auditoría — Iglesia Centroamericana Nueva Vida</div>
            <div class="subtitulo">Generado: <?= date('d/m/Y H:i') ?> — Registros: <?= count($eventos) ?></div>

            <table>
                <thead>
                    <tr><th>Fecha/Hora</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Descripción</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $evento): ?>
                        <tr>
                            <td><?= htmlspecialchars($evento['fecha_evento']) ?></td>
                            <td><?= htmlspecialchars($evento['nombre_usuario']) ?></td>
                            <td><?= htmlspecialchars($evento['modulo']) ?></td>
                            <td><?= htmlspecialchars($evento['accion']) ?></td>
                            <td><?= htmlspecialchars($evento['descripcion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
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
