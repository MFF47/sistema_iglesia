<?php
require_once __DIR__ . '/../../core/Model.php';

class Egreso extends Model {
    public function buscarPorId($id) {
        $stmt = $this->db->prepare(
            'SELECT e.*, c.nombre AS categoria_nombre, f.numero_documento AS factura_numero
             FROM egresos e
             INNER JOIN categorias c ON c.id_categoria = e.id_categoria
             LEFT JOIN facturas f ON f.id_factura = e.id_factura
             WHERE e.id_egreso = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);

        $egreso = $stmt->fetch();

        return $egreso ?: null;
    }

    public function listar(array $filtros, $pagina, $porPagina) {
        [$where, $params] = $this->construirFiltros($filtros);
        $porPagina = (int) $porPagina;
        $offset = (int) (max(1, $pagina) - 1) * $porPagina;

        $stmt = $this->db->prepare(
            "SELECT e.*, c.nombre AS categoria_nombre, f.numero_documento AS factura_numero
             FROM egresos e
             INNER JOIN categorias c ON c.id_categoria = e.id_categoria
             LEFT JOIN facturas f ON f.id_factura = e.id_factura
             $where
             ORDER BY e.fecha_egreso DESC, e.id_egreso DESC
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contar(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM egresos e
             INNER JOIN categorias c ON c.id_categoria = e.id_categoria
             $where"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function totalFiltrado(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(e.monto), 0) FROM egresos e
             INNER JOIN categorias c ON c.id_categoria = e.id_categoria
             $where"
        );
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    private function construirFiltros(array $filtros) {
        $condiciones = ['e.activo = 1'];
        $params = [];

        if (!empty($filtros['desde'])) {
            $condiciones[] = 'e.fecha_egreso >= ?';
            $params[] = $filtros['desde'];
        }

        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'e.fecha_egreso <= ?';
            $params[] = $filtros['hasta'];
        }

        if (!empty($filtros['id_categoria'])) {
            $condiciones[] = 'e.id_categoria = ?';
            $params[] = $filtros['id_categoria'];
        }

        if (!empty($filtros['beneficiario'])) {
            $condiciones[] = 'e.beneficiario LIKE ?';
            $params[] = '%' . $filtros['beneficiario'] . '%';
        }

        $where = 'WHERE ' . implode(' AND ', $condiciones);

        return [$where, $params];
    }

    public function kpisMes() {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) AS total, COUNT(*) AS cantidad
             FROM egresos
             WHERE activo = 1
               AND YEAR(fecha_egreso) = YEAR(CURDATE())
               AND MONTH(fecha_egreso) = MONTH(CURDATE())"
        );
        $fila = $stmt->fetch();

        $cantidad = (int) $fila['cantidad'];

        return [
            'total' => (float) $fila['total'],
            'cantidad' => $cantidad,
            'promedio' => $cantidad > 0 ? (float) $fila['total'] / $cantidad : 0.0,
        ];
    }

    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO egresos
                (id_categoria, id_factura, id_usuario_registro, fecha_egreso, monto, beneficiario, descripcion, observaciones, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $datos['id_categoria'],
            $datos['id_factura'] ?: null,
            $datos['id_usuario_registro'],
            $datos['fecha_egreso'],
            $datos['monto'],
            $datos['beneficiario'],
            $datos['descripcion'],
            $datos['observaciones'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar($id, array $datos) {
        // id_factura NO se toca aquí a propósito: se administra únicamente vía
        // vincularFactura(), para no romper el vínculo cuando el select de
        // "Vincular Factura" del formulario llega vacío por no haberse re-seleccionado.
        $stmt = $this->db->prepare(
            'UPDATE egresos
             SET id_categoria = ?, fecha_egreso = ?, monto = ?, beneficiario = ?, descripcion = ?, observaciones = ?
             WHERE id_egreso = ?'
        );
        $stmt->execute([
            $datos['id_categoria'],
            $datos['fecha_egreso'],
            $datos['monto'],
            $datos['beneficiario'],
            $datos['descripcion'],
            $datos['observaciones'] ?: null,
            $id,
        ]);
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->db->prepare('UPDATE egresos SET activo = ? WHERE id_egreso = ?');
        $stmt->execute([$activo, $id]);
    }

    public function listarParaVincular() {
        $stmt = $this->db->query(
            "SELECT id_egreso, fecha_egreso, beneficiario, monto, id_factura
             FROM egresos
             WHERE activo = 1
             ORDER BY fecha_egreso DESC"
        );

        return $stmt->fetchAll();
    }

    public function vincularFactura($idEgreso, $idFactura) {
        $stmt = $this->db->prepare('UPDATE egresos SET id_factura = ? WHERE id_egreso = ?');
        $stmt->execute([$idFactura, $idEgreso]);
    }

    public function actualizarCamposClave($id, $monto, $fechaEgreso, $beneficiario) {
        $stmt = $this->db->prepare('UPDATE egresos SET monto = ?, fecha_egreso = ?, beneficiario = ? WHERE id_egreso = ?');
        $stmt->execute([$monto, $fechaEgreso, $beneficiario, $id]);
    }
}
