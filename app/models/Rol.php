<?php
require_once __DIR__ . '/../../core/Model.php';

class Rol extends Model {
    public function listarActivos() {
        $stmt = $this->db->query('SELECT id_rol, nombre FROM roles WHERE activo = 1 ORDER BY nombre');

        return $stmt->fetchAll();
    }
}
