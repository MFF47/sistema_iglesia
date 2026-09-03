<?php
require_once __DIR__ . '/../../core/Model.php';

class Aportante extends Model {
    public function listarActivos() {
        $stmt = $this->db->query(
            'SELECT id_aportante, nombre_completo FROM aportantes WHERE activo = 1 ORDER BY nombre_completo'
        );

        return $stmt->fetchAll();
    }

    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO aportantes (nombre_completo, documento_identidad, telefono, activo)
             VALUES (?, ?, ?, 1)'
        );
        $stmt->execute([
            $datos['nombre_completo'],
            $datos['documento_identidad'] ?: null,
            $datos['telefono'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
