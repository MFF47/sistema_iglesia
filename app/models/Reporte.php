<?php
require_once __DIR__ . '/../../core/Model.php';

class Reporte extends Model {
    public function disponibleCajaGeneral() {
        $totalIngresos = (float) $this->db->query('SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE activo = 1')->fetchColumn();
        $totalEgresos = (float) $this->db->query('SELECT COALESCE(SUM(monto), 0) FROM egresos WHERE activo = 1')->fetchColumn();

        return $totalIngresos - $totalEgresos;
    }

    public function totalesPeriodo($desde, $hasta) {
        $stmtIngresos = $this->db->prepare(
            'SELECT COALESCE(SUM(monto), 0) AS total, COUNT(*) AS cantidad
             FROM ingresos WHERE activo = 1 AND fecha_ingreso BETWEEN ? AND ?'
        );
        $stmtIngresos->execute([$desde, $hasta]);
        $ingresos = $stmtIngresos->fetch();

        $stmtEgresos = $this->db->prepare(
            'SELECT COALESCE(SUM(monto), 0) AS total, COUNT(*) AS cantidad
             FROM egresos WHERE activo = 1 AND fecha_egreso BETWEEN ? AND ?'
        );
        $stmtEgresos->execute([$desde, $hasta]);
        $egresos = $stmtEgresos->fetch();

        return [
            'total_ingresos' => (float) $ingresos['total'],
            'cantidad_ingresos' => (int) $ingresos['cantidad'],
            'total_egresos' => (float) $egresos['total'],
            'cantidad_egresos' => (int) $egresos['cantidad'],
            'balance' => (float) $ingresos['total'] - (float) $egresos['total'],
            'movimientos' => (int) $ingresos['cantidad'] + (int) $egresos['cantidad'],
        ];
    }

    public function serieMensual($meses = 6) {
        $etiquetas = [];
        $ingresosPorMes = [];
        $egresosPorMes = [];

        $stmtIngresos = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM ingresos
             WHERE activo = 1 AND YEAR(fecha_ingreso) = ? AND MONTH(fecha_ingreso) = ?"
        );
        $stmtEgresos = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM egresos
             WHERE activo = 1 AND YEAR(fecha_egreso) = ? AND MONTH(fecha_egreso) = ?"
        );

        for ($i = $meses - 1; $i >= 0; $i--) {
            $timestamp = strtotime("-$i months", strtotime(date('Y-m-01')));
            $anio = (int) date('Y', $timestamp);
            $mes = (int) date('n', $timestamp);

            $etiquetas[] = ucfirst(self::nombreMesCorto($mes));

            $stmtIngresos->execute([$anio, $mes]);
            $ingresosPorMes[] = (float) $stmtIngresos->fetchColumn();

            $stmtEgresos->execute([$anio, $mes]);
            $egresosPorMes[] = (float) $stmtEgresos->fetchColumn();
        }

        return [
            'etiquetas' => $etiquetas,
            'ingresos' => $ingresosPorMes,
            'egresos' => $egresosPorMes,
        ];
    }

    public function distribucionIngresosPorTipo($desde, $hasta) {
        $stmt = $this->db->prepare(
            "SELECT t.nombre, COALESCE(SUM(i.monto), 0) AS total
             FROM tipos_ingreso t
             LEFT JOIN ingresos i ON i.id_tipo_ingreso = t.id_tipo_ingreso
                 AND i.activo = 1 AND i.fecha_ingreso BETWEEN ? AND ?
             WHERE t.activo = 1
             GROUP BY t.id_tipo_ingreso, t.nombre
             HAVING total > 0
             ORDER BY total DESC"
        );
        $stmt->execute([$desde, $hasta]);

        return $stmt->fetchAll();
    }

    public function distribucionEgresosPorCategoria($desde, $hasta) {
        $stmt = $this->db->prepare(
            "SELECT c.nombre, COALESCE(SUM(e.monto), 0) AS total
             FROM categorias c
             LEFT JOIN egresos e ON e.id_categoria = c.id_categoria
                 AND e.activo = 1 AND e.fecha_egreso BETWEEN ? AND ?
             WHERE c.activo = 1 AND c.tipo = 'egreso'
             GROUP BY c.id_categoria, c.nombre
             HAVING total > 0
             ORDER BY total DESC"
        );
        $stmt->execute([$desde, $hasta]);

        return $stmt->fetchAll();
    }

    public function comparativoAnual($anio) {
        $etiquetas = [];
        $totales = [];

        $stmt = $this->db->prepare(
            "SELECT
                (SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE activo = 1 AND YEAR(fecha_ingreso) = ? AND MONTH(fecha_ingreso) = ?)
                -
                (SELECT COALESCE(SUM(monto), 0) FROM egresos WHERE activo = 1 AND YEAR(fecha_egreso) = ? AND MONTH(fecha_egreso) = ?)
             AS balance"
        );

        for ($mes = 1; $mes <= 12; $mes++) {
            $etiquetas[] = ucfirst(self::nombreMesCorto($mes));
            $stmt->execute([$anio, $mes, $anio, $mes]);
            $totales[] = (float) $stmt->fetchColumn();
        }

        return ['etiquetas' => $etiquetas, 'balance' => $totales];
    }

    public static function nombreMesCorto($mes) {
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return $meses[$mes - 1];
    }

    public static function nombreMesLargo($mes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return $meses[(int) $mes];
    }
}
