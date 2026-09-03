<?php
require_once __DIR__ . '/../../core/Model.php';

class Ingreso extends Model {
    public function buscarPorId($id) {
        $stmt = $this->db->prepare(
            'SELECT i.*, c.nombre AS categoria_nombre, t.nombre AS tipo_nombre, a.nombre_completo AS aportante_nombre
             FROM ingresos i
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN tipos_ingreso t ON t.id_tipo_ingreso = i.id_tipo_ingreso
             LEFT JOIN aportantes a ON a.id_aportante = i.id_aportante
             WHERE i.id_ingreso = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);

        $ingreso = $stmt->fetch();

        return $ingreso ?: null;
    }

    public function listar(array $filtros, $pagina, $porPagina) {
        [$where, $params] = $this->construirFiltros($filtros);
        $porPagina = (int) $porPagina;
        $offset = (int) (max(1, $pagina) - 1) * $porPagina;

        $stmt = $this->db->prepare(
            "SELECT i.*, c.nombre AS categoria_nombre, t.nombre AS tipo_nombre, a.nombre_completo AS aportante_nombre,
                (SELECT COUNT(*) FROM adjuntos ad WHERE ad.tabla_origen = 'ingresos' AND ad.id_origen = i.id_ingreso) AS tiene_comprobante
             FROM ingresos i
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN tipos_ingreso t ON t.id_tipo_ingreso = i.id_tipo_ingreso
             LEFT JOIN aportantes a ON a.id_aportante = i.id_aportante
             $where
             ORDER BY i.fecha_ingreso DESC, i.id_ingreso DESC
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contar(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ingresos i
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN tipos_ingreso t ON t.id_tipo_ingreso = i.id_tipo_ingreso
             $where"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function totalFiltrado(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(i.monto), 0) FROM ingresos i
             INNER JOIN categorias c ON c.id_categoria = i.id_categoria
             INNER JOIN tipos_ingreso t ON t.id_tipo_ingreso = i.id_tipo_ingreso
             $where"
        );
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    private function construirFiltros(array $filtros) {
        $condiciones = ['i.activo = 1'];
        $params = [];

        if (!empty($filtros['desde'])) {
            $condiciones[] = 'i.fecha_ingreso >= ?';
            $params[] = $filtros['desde'];
        }

        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'i.fecha_ingreso <= ?';
            $params[] = $filtros['hasta'];
        }

        if (!empty($filtros['id_categoria'])) {
            $condiciones[] = 'i.id_categoria = ?';
            $params[] = $filtros['id_categoria'];
        }

        if (!empty($filtros['id_tipo_ingreso'])) {
            $condiciones[] = 'i.id_tipo_ingreso = ?';
            $params[] = $filtros['id_tipo_ingreso'];
        }

        $where = 'WHERE ' . implode(' AND ', $condiciones);

        return [$where, $params];
    }

    public function kpisMes() {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) AS total, COUNT(*) AS cantidad
             FROM ingresos
             WHERE activo = 1
               AND YEAR(fecha_ingreso) = YEAR(CURDATE())
               AND MONTH(fecha_ingreso) = MONTH(CURDATE())"
        );
        $fila = $stmt->fetch();

        $diasTranscurridos = max(1, (int) date('j'));

        return [
            'total' => (float) $fila['total'],
            'cantidad' => (int) $fila['cantidad'],
            'promedio_diario' => (float) $fila['total'] / $diasTranscurridos,
        ];
    }

    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO ingresos
                (id_categoria, id_tipo_ingreso, id_aportante, id_actividad, id_usuario_registro,
                 fecha_ingreso, monto, descripcion, observaciones, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $datos['id_categoria'],
            $datos['id_tipo_ingreso'],
            $datos['id_aportante'] ?: null,
            $datos['id_actividad'] ?: null,
            $datos['id_usuario_registro'],
            $datos['fecha_ingreso'],
            $datos['monto'],
            $datos['descripcion'] ?: null,
            $datos['observaciones'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar($id, array $datos) {
        $stmt = $this->db->prepare(
            'UPDATE ingresos
             SET id_categoria = ?, id_tipo_ingreso = ?, id_aportante = ?, id_actividad = ?,
                 fecha_ingreso = ?, monto = ?, descripcion = ?, observaciones = ?
             WHERE id_ingreso = ?'
        );
        $stmt->execute([
            $datos['id_categoria'],
            $datos['id_tipo_ingreso'],
            $datos['id_aportante'] ?: null,
            $datos['id_actividad'] ?: null,
            $datos['fecha_ingreso'],
            $datos['monto'],
            $datos['descripcion'] ?: null,
            $datos['observaciones'] ?: null,
            $id,
        ]);
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->db->prepare('UPDATE ingresos SET activo = ? WHERE id_ingreso = ?');
        $stmt->execute([$activo, $id]);
    }
}
