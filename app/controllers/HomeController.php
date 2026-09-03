<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../models/Reporte.php';

class HomeController extends Controller {
    public function index() {
        Middleware::requirePermiso('dashboard');

        $periodo = $_GET['periodo'] ?? 'mes_actual';
        [$desde, $hasta, $desdeAnterior, $hastaAnterior, $etiquetaPeriodo] = $this->rangoPeriodo($periodo);

        $reporteModel = new Reporte();

        $actual = $reporteModel->totalesPeriodo($desde, $hasta);
        $anterior = $reporteModel->totalesPeriodo($desdeAnterior, $hastaAnterior);

        $this->view('home.index', [
            'usuario' => Auth::user(),
            'periodo' => $periodo,
            'etiquetaPeriodo' => $etiquetaPeriodo,
            'kpis' => $actual,
            'variacionIngresos' => $this->variacion($actual['total_ingresos'], $anterior['total_ingresos']),
            'variacionEgresos' => $this->variacion($actual['total_egresos'], $anterior['total_egresos']),
            'serieMensual' => $reporteModel->serieMensual(6),
            'distribucionIngresos' => $reporteModel->distribucionIngresosPorTipo($desde, $hasta),
            'comparativoAnual' => $reporteModel->comparativoAnual((int) date('Y')),
        ]);
    }

    private function rangoPeriodo($periodo) {
        $hoy = new DateTime();

        if ($periodo === 'mes_anterior') {
            $inicio = new DateTime('first day of last month');
            $fin = new DateTime('last day of last month');
            $inicioAnterior = (clone $inicio)->modify('-1 month');
            $finAnterior = (clone $fin)->modify('-1 month');
            $etiqueta = 'Mes anterior — ' . $this->nombreMesAnio($inicio);
        } elseif ($periodo === 'anio_actual') {
            $inicio = new DateTime('first day of january this year');
            $fin = $hoy;
            $inicioAnterior = (clone $inicio)->modify('-1 year');
            $finAnterior = (clone $fin)->modify('-1 year');
            $etiqueta = 'Año ' . $hoy->format('Y');
        } else {
            $periodo = 'mes_actual';
            $inicio = new DateTime('first day of this month');
            $fin = new DateTime('last day of this month');
            $inicioAnterior = (clone $inicio)->modify('-1 month');
            $finAnterior = (clone $fin)->modify('-1 month');
            $etiqueta = 'Mes actual — ' . $this->nombreMesAnio($inicio);
        }

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
