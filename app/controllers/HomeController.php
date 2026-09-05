<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../models/Reporte.php';

class HomeController extends Controller {
    public function index() {
        Middleware::requirePermiso('dashboard');

        $mes = max(1, min(12, (int) ($_GET['mes'] ?? date('n'))));
        $anio = max(2000, min(2100, (int) ($_GET['anio'] ?? date('Y'))));

        [$desde, $hasta, $desdeAnterior, $hastaAnterior, $etiquetaPeriodo] = $this->rangoPeriodo($mes, $anio);

        $reporteModel = new Reporte();

        $actual = $reporteModel->totalesPeriodo($desde, $hasta);
        $anterior = $reporteModel->totalesPeriodo($desdeAnterior, $hastaAnterior);

        $this->view('home.index', [
            'usuario' => Auth::user(),
            'mes' => $mes,
            'anio' => $anio,
            'etiquetaPeriodo' => $etiquetaPeriodo,
            'kpis' => $actual,
            'disponibleCajaGeneral' => $reporteModel->disponibleCajaGeneral(),
            'variacionIngresos' => $this->variacion($actual['total_ingresos'], $anterior['total_ingresos']),
            'variacionEgresos' => $this->variacion($actual['total_egresos'], $anterior['total_egresos']),
            'serieMensual' => $reporteModel->serieMensual(6),
            'distribucionIngresos' => $reporteModel->distribucionIngresosPorTipo($desde, $hasta),
            'distribucionEgresos' => $reporteModel->distribucionEgresosPorCategoria($desde, $hasta),
            'comparativoAnual' => $reporteModel->comparativoAnual((int) date('Y')),
        ]);
    }

    private function rangoPeriodo($mes, $anio) {
        $inicio = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
        $fin = (clone $inicio)->modify('last day of this month');
        $inicioAnterior = (clone $inicio)->modify('-1 month');
        $finAnterior = (clone $inicioAnterior)->modify('last day of this month');
        $etiqueta = $this->nombreMesAnio($inicio);

        return [
            $inicio->format('Y-m-d'),
            $fin->format('Y-m-d'),
            $inicioAnterior->format('Y-m-d'),
            $finAnterior->format('Y-m-d'),
            $etiqueta,
        ];
    }

    private function nombreMesAnio(DateTime $fecha) {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
            7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $meses[(int) $fecha->format('n')] . ' ' . $fecha->format('Y');
    }

    private function variacion($actual, $anterior) {
        if ($anterior <= 0) {
            return $actual > 0 ? ['pct' => null, 'nuevo' => true] : ['pct' => 0.0, 'nuevo' => false];
        }

        return ['pct' => (($actual - $anterior) / $anterior) * 100, 'nuevo' => false];
    }
}
