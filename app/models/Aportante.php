<?php
require_once __DIR__ . '/../../core/Model.php';

class Aportante extends Model {
    public function listarActivos() {
        $stmt = $this->db->query(
            'SELECT id_aportante, nombre_completo FROM aportantes WHERE activo = 1 ORDER BY nombre_completo'
        );

        return $stmt->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare('SELECT * FROM aportantes WHERE id_aportante = ? LIMIT 1');
        $stmt->execute([$id]);

        $aportante = $stmt->fetch();

        return $aportante ?: null;
    }

    public function listar(array $filtros, $pagina, $porPagina) {
        [$where, $params] = $this->construirFiltros($filtros);
        $porPagina = (int) $porPagina;
        $offset = (int) (max(1, $pagina) - 1) * $porPagina;

        $stmt = $this->db->prepare(
            "SELECT a.*,
                (SELECT COUNT(*) FROM ingresos i WHERE i.id_aportante = a.id_aportante) AS movimientos
             FROM aportantes a
             $where
             ORDER BY a.nombre_completo
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contar(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM aportantes a $where");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function construirFiltros(array $filtros) {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $condiciones[] = '(a.nombre_completo LIKE ? OR a.documento_identidad LIKE ? OR a.correo LIKE ?)';
            $like = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like);
        }

        if ($filtros['activo'] !== null && $filtros['activo'] !== '') {
            $condiciones[] = 'a.activo = ?';
            $params[] = $filtros['activo'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        return [$where, $params];
    }

    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO aportantes (nombre_completo, documento_identidad, telefono, correo, direccion, observaciones, activo)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $datos['nombre_completo'],
            !empty($datos['documento_identidad']) ? $datos['documento_identidad'] : null,
            !empty($datos['telefono']) ? $datos['telefono'] : null,
            !empty($datos['correo']) ? $datos['correo'] : null,
            !empty($datos['direccion']) ? $datos['direccion'] : null,
            !empty($datos['observaciones']) ? $datos['observaciones'] : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar($id, array $datos) {
        $stmt = $this->db->prepare(
            'UPDATE aportantes
             SET nombre_completo = ?, documento_identidad = ?, telefono = ?, correo = ?, direccion = ?, observaciones = ?
             WHERE id_aportante = ?'
        );
        $stmt->execute([
            $datos['nombre_completo'],
            $datos['documento_identidad'] ?: null,
            $datos['telefono'] ?: null,
            $datos['correo'] ?: null,
            $datos['direccion'] ?: null,
            $datos['observaciones'] ?: null,
            $id,
        ]);
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->db->prepare('UPDATE aportantes SET activo = ? WHERE id_aportante = ?');
        $stmt->execute([$activo, $id]);
    }
}
