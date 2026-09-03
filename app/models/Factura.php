<?php
require_once __DIR__ . '/../../core/Model.php';

class Factura extends Model {
    public function listarActivas() {
        $stmt = $this->db->query(
            "SELECT id_factura, numero_documento, tipo_documento, beneficiario, monto
             FROM facturas
             WHERE activo = 1
             ORDER BY fecha_emision DESC"
        );

        return $stmt->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare(
            'SELECT f.*, e.id_egreso AS egreso_vinculado_id
             FROM facturas f
             LEFT JOIN egresos e ON e.id_factura = f.id_factura
             WHERE f.id_factura = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);

        $factura = $stmt->fetch();

        return $factura ?: null;
    }

    public function listar(array $filtros, $pagina, $porPagina) {
        [$where, $params] = $this->construirFiltros($filtros);
        $porPagina = (int) $porPagina;
        $offset = (int) (max(1, $pagina) - 1) * $porPagina;

        $stmt = $this->db->prepare(
            "SELECT f.*, e.id_egreso AS egreso_vinculado_id
             FROM facturas f
             LEFT JOIN egresos e ON e.id_factura = f.id_factura
             $where
             ORDER BY f.fecha_emision DESC, f.id_factura DESC
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contar(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM facturas f $where");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function construirFiltros(array $filtros) {
        $condiciones = ['f.activo = 1'];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $condiciones[] = '(f.numero_documento LIKE ? OR f.beneficiario LIKE ?)';
            $like = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like);
        }

        if (!empty($filtros['tipo_documento'])) {
            $condiciones[] = 'f.tipo_documento = ?';
            $params[] = $filtros['tipo_documento'];
        }

        if (!empty($filtros['desde'])) {
            $condiciones[] = 'f.fecha_emision >= ?';
            $params[] = $filtros['desde'];
        }

        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'f.fecha_emision <= ?';
            $params[] = $filtros['hasta'];
        }

        $where = 'WHERE ' . implode(' AND ', $condiciones);

        return [$where, $params];
    }

    public function actualizar($id, array $datos) {
        if (!empty($datos['ruta_archivo'])) {
            $stmt = $this->db->prepare(
                'UPDATE facturas
                 SET numero_documento = ?, tipo_documento = ?, beneficiario = ?, fecha_emision = ?, monto = ?, descripcion = ?, ruta_archivo = ?
                 WHERE id_factura = ?'
            );
            $stmt->execute([
                $datos['numero_documento'],
                $datos['tipo_documento'],
                $datos['beneficiario'],
                $datos['fecha_emision'],
                $datos['monto'],
                $datos['descripcion'] ?: null,
                $datos['ruta_archivo'],
                $id,
            ]);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE facturas
             SET numero_documento = ?, tipo_documento = ?, beneficiario = ?, fecha_emision = ?, monto = ?, descripcion = ?
             WHERE id_factura = ?'
        );
        $stmt->execute([
            $datos['numero_documento'],
            $datos['tipo_documento'],
            $datos['beneficiario'],
            $datos['fecha_emision'],
            $datos['monto'],
            $datos['descripcion'] ?: null,
            $id,
        ]);
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->db->prepare('UPDATE facturas SET activo = ? WHERE id_factura = ?');
        $stmt->execute([$activo, $id]);
    }

    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO facturas (numero_documento, tipo_documento, beneficiario, fecha_emision, monto, descripcion, ruta_archivo, id_usuario_registro, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $datos['numero_documento'] ?: null,
            $datos['tipo_documento'],
            $datos['beneficiario'] ?: null,
            $datos['fecha_emision'],
            $datos['monto'],
            $datos['descripcion'] ?: null,
            $datos['ruta_archivo'] ?? null,
            $datos['id_usuario_registro'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}
