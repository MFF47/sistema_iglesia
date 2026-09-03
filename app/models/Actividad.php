<?php
require_once __DIR__ . '/../../core/Model.php';

class Actividad extends Model {
    public function listarActivas() {
        $stmt = $this->db->query(
            "SELECT id_actividad, nombre FROM actividades WHERE activo = 1 AND estado != 'cancelada' ORDER BY nombre"
        );

        return $stmt->fetchAll();
    }
}
