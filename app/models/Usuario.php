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

    public function buscarPorCorreo($correo) {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.correo = ?
             LIMIT 1'
        );
        $stmt->execute([$correo]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function actualizarContrasena($idUsuario, $contrasenaHash) {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET contrasena_hash = ?, intentos_fallidos = 0, bloqueado = 0, fecha_bloqueo = NULL WHERE id_usuario = ?'
        );
        $stmt->execute([$contrasenaHash, $idUsuario]);
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

    public function buscarPorId($id) {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function listar(array $filtros, $pagina, $porPagina) {
        [$where, $params] = $this->construirFiltros($filtros);
        $porPagina = (int) $porPagina;
        $offset = (int) (max(1, $pagina) - 1) * $porPagina;

        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             $where
             ORDER BY u.nombre_completo
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contar(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             $where"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function construirFiltros(array $filtros) {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $condiciones[] = '(u.nombre_usuario LIKE ? OR u.nombre_completo LIKE ? OR u.correo LIKE ?)';
            $like = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like);
        }

        if (!empty($filtros['id_rol'])) {
            $condiciones[] = 'u.id_rol = ?';
            $params[] = $filtros['id_rol'];
        }

        if ($filtros['activo'] !== null && $filtros['activo'] !== '') {
            $condiciones[] = 'u.activo = ?';
            $params[] = $filtros['activo'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        return [$where, $params];
    }

    public function existeNombreUsuario($nombreUsuario, $excluirId = null) {
        $sql = 'SELECT 1 FROM usuarios WHERE nombre_usuario = ?';
        $params = [$nombreUsuario];

        if ($excluirId) {
            $sql .= ' AND id_usuario != ?';
            $params[] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function existeCorreo($correo, $excluirId = null) {
        $sql = 'SELECT 1 FROM usuarios WHERE correo = ?';
        $params = [$correo];

        if ($excluirId) {
            $sql .= ' AND id_usuario != ?';
            $params[] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function crear(array $datos) {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (id_rol, nombre_usuario, nombre_completo, correo, contrasena_hash, telefono, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $datos['id_rol'],
            $datos['nombre_usuario'],
            $datos['nombre_completo'],
            $datos['correo'],
            $datos['contrasena_hash'],
            $datos['telefono'] ?: null,
            $datos['activo'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar($id, array $datos) {
        if (!empty($datos['contrasena_hash'])) {
            $stmt = $this->db->prepare(
                'UPDATE usuarios
                 SET id_rol = ?, nombre_usuario = ?, nombre_completo = ?, correo = ?, telefono = ?, activo = ?, contrasena_hash = ?
                 WHERE id_usuario = ?'
            );
            $stmt->execute([
                $datos['id_rol'],
                $datos['nombre_usuario'],
                $datos['nombre_completo'],
                $datos['correo'],
                $datos['telefono'] ?: null,
                $datos['activo'],
                $datos['contrasena_hash'],
                $id,
            ]);

            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET id_rol = ?, nombre_usuario = ?, nombre_completo = ?, correo = ?, telefono = ?, activo = ?
             WHERE id_usuario = ?'
        );
        $stmt->execute([
            $datos['id_rol'],
            $datos['nombre_usuario'],
            $datos['nombre_completo'],
            $datos['correo'],
            $datos['telefono'] ?: null,
            $datos['activo'],
            $id,
        ]);
    }

    public function cambiarEstado($id, $activo) {
        $stmt = $this->db->prepare('UPDATE usuarios SET activo = ? WHERE id_usuario = ?');
        $stmt->execute([$activo, $id]);
    }
}
