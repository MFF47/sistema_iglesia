<?php
require_once __DIR__ . '/../../core/Model.php';

class TipoIngreso extends Model {
    public function listarActivos() {
        $stmt = $this->db->query(
            'SELECT id_tipo_ingreso, nombre, requiere_aportante FROM tipos_ingreso WHERE activo = 1 ORDER BY nombre'
        );

        return $stmt->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare('SELECT * FROM tipos_ingreso WHERE id_tipo_ingreso = ? LIMIT 1');
        $stmt->execute([$id]);

        $tipo = $stmt->fetch();

        return $tipo ?: null;
    }
}
