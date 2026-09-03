<?php
require_once __DIR__ . '/../../core/Model.php';

class Adjunto extends Model {
    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO adjuntos (tabla_origen, id_origen, nombre_original, nombre_almacenado, ruta, tipo_mime, tamano_bytes, id_usuario_registro)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $datos['tabla_origen'],
            $datos['id_origen'],
            $datos['nombre_original'],
            $datos['nombre_almacenado'],
            $datos['ruta'],
            $datos['tipo_mime'],
            $datos['tamano_bytes'],
            $datos['id_usuario_registro'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function buscarPorOrigen($tablaOrigen, $idOrigen) {
        $stmt = $this->db->prepare(
            'SELECT * FROM adjuntos WHERE tabla_origen = ? AND id_origen = ? ORDER BY fecha_subida DESC LIMIT 1'
        );
        $stmt->execute([$tablaOrigen, $idOrigen]);

        $adjunto = $stmt->fetch();

        return $adjunto ?: null;
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare('SELECT * FROM adjuntos WHERE id_adjunto = ? LIMIT 1');
        $stmt->execute([$id]);

        $adjunto = $stmt->fetch();

        return $adjunto ?: null;
    }
}
