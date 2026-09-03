<?php
require_once __DIR__ . '/../../core/Model.php';

class Usuario extends Model {
    public function buscarPorNombreUsuario($nombreUsuario) {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre, r.descripcion AS rol_descripcion
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.nombre_usuario = ?
             LIMIT 1'
        );
        $stmt->execute([$nombreUsuario]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function registrarAccesoExitoso($idUsuario) {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id_usuario = ?'
        );
        $stmt->execute([$idUsuario]);
    }

    public function registrarIntentoFallido($idUsuario, $maxIntentos) {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id_usuario = ?'
        );
        $stmt->execute([$idUsuario]);

        $stmt = $this->db->prepare('SELECT intentos_fallidos FROM usuarios WHERE id_usuario = ?');
        $stmt->execute([$idUsuario]);
        $intentos = (int) $stmt->fetchColumn();

        if ($intentos >= $maxIntentos) {
            $stmt = $this->db->prepare(
                'UPDATE usuarios SET bloqueado = 1, fecha_bloqueo = NOW() WHERE id_usuario = ?'
            );
            $stmt->execute([$idUsuario]);
        }
    }
}
