<?php
require_once __DIR__ . '/../../core/Model.php';

class Categoria extends Model {
    public function buscarPorId($id) {
        $stmt = $this->db->prepare('SELECT * FROM categorias WHERE id_categoria = ? LIMIT 1');
        $stmt->execute([$id]);

        $categoria = $stmt->fetch();

        return $categoria ?: null;
    }

    public function listar(array $filtros, $pagina, $porPagina) {
        [$where, $params] = $this->construirFiltros($filtros);
        $porPagina = (int) $porPagina;
        $offset = (int) (max(1, $pagina) - 1) * $porPagina;

        $stmt = $this->db->prepare(
            "SELECT c.*,
                (SELECT COUNT(*) FROM ingresos i WHERE i.id_categoria = c.id_categoria)
                + (SELECT COUNT(*) FROM egresos e WHERE e.id_categoria = c.id_categoria) AS movimientos
             FROM categorias c
             $where
             ORDER BY c.nombre
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contar(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM categorias c $where");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function construirFiltros(array $filtros) {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $condiciones[] = '(c.nombre LIKE ? OR c.descripcion LIKE ?)';
            $like = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like);
        }

        if (!empty($filtros['tipo'])) {
            $condiciones[] = 'c.tipo = ?';
            $params[] = $filtros['tipo'];
        }

        if ($filtros['activo'] !== null && $filtros['activo'] !== '') {
            $condiciones[] = 'c.activo = ?';
            $params[] = $filtros['activo'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        return [$where, $params];
    }

    public function existeNombre($nombre, $tipo, $excluirId = null) {
        $sql = 'SELECT 1 FROM categorias WHERE nombre = ? AND tipo = ?';
        $params = [$nombre, $tipo];

        if ($excluirId) {
            $sql .= ' AND id_categoria != ?';
            $params[] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO categorias (nombre, descripcion, tipo, activo) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'] ?: null,
            $datos['tipo'],
            $datos['activo'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar($id, array $datos) {
        $stmt = $this->db->prepare(
            'UPDATE categorias SET nombre = ?, descripcion = ?, tipo = ?, activo = ? WHERE id_categoria = ?'
        );
        $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'] ?: null,
            $datos['tipo'],
            $datos['activo'],
            $id,
        ]);
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->db->prepare('UPDATE categorias SET activo = ? WHERE id_categoria = ?');
        $stmt->execute([$activo, $id]);
    }
}
