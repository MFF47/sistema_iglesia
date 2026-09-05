<?php
require_once __DIR__ . '/../../core/Model.php';

class Sesion extends Model {
    const MINUTOS_VALIDEZ_RECUPERACION = 30;

    public function crearTokenRecuperacion($idUsuario) {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->db->prepare(
            'INSERT INTO sesiones (id_usuario, token, tipo, ip_origen, user_agent, fecha_expiracion)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
        );
        $stmt->execute([
            $idUsuario,
            $token,
            'recuperacion_password',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            self::MINUTOS_VALIDEZ_RECUPERACION,
        ]);

        return $token;
    }

    public function buscarTokenRecuperacionValido($token) {
        $stmt = $this->db->prepare(
            "SELECT * FROM sesiones
             WHERE token = ? AND tipo = 'recuperacion_password' AND usado = 0 AND fecha_expiracion > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);

        $sesion = $stmt->fetch();

        return $sesion ?: null;
    }

    public function marcarComoUsado($idSesion) {
        $stmt = $this->db->prepare('UPDATE sesiones SET usado = 1 WHERE id_sesion = ?');
        $stmt->execute([$idSesion]);
    }
}
