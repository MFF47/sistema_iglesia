<?php
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Auth.php';

class Bitacora extends Model {
    // $usuarioForzado permite identificar al usuario en eventos sin sesión activa
    // (login fallido, recuperación de contraseña), donde Auth::user() es null
    // aunque la identidad del usuario involucrado sí se conozca.
    public static function registrar($accion, $modulo, $descripcion, $tablaAfectada = null, $idRegistroAfectado = null, $usuarioForzado = null) {
        $usuario = $usuarioForzado ?? Auth::user();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            'INSERT INTO bitacora
                (id_usuario, nombre_usuario, modulo, accion, descripcion, tabla_afectada, id_registro_afectado, ip_origen, fecha_evento)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $usuario['id_usuario'] ?? null,
            $usuario['nombre_usuario'] ?? '(anónimo)',
            $modulo,
            $accion,
            $descripcion,
            $tablaAfectada,
            $idRegistroAfectado,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public function listar(array $filtros, $pagina, $porPagina) {
        [$where, $params] = $this->construirFiltros($filtros);
        $porPagina = (int) $porPagina;
        $offset = (int) (max(1, $pagina) - 1) * $porPagina;

        $stmt = $this->db->prepare(
            "SELECT * FROM bitacora
             $where
             ORDER BY fecha_evento DESC, id_bitacora DESC
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contar(array $filtros) {
        [$where, $params] = $this->construirFiltros($filtros);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bitacora $where");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function construirFiltros(array $filtros) {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['desde'])) {
            $condiciones[] = 'fecha_evento >= ?';
            $params[] = $filtros['desde'];
        }

        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'fecha_evento < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $filtros['hasta'];
        }

        if (!empty($filtros['nombre_usuario'])) {
            $condiciones[] = 'nombre_usuario = ?';
            $params[] = $filtros['nombre_usuario'];
        }

        if (!empty($filtros['modulo'])) {
            $condiciones[] = 'modulo = ?';
            $params[] = $filtros['modulo'];
        }

        if (!empty($filtros['accion'])) {
            $condiciones[] = 'accion = ?';
            $params[] = $filtros['accion'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        return [$where, $params];
    }

    public function listarUsuarios() {
        $stmt = $this->db->query('SELECT DISTINCT nombre_usuario FROM bitacora ORDER BY nombre_usuario');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarModulos() {
        $stmt = $this->db->query('SELECT DISTINCT modulo FROM bitacora ORDER BY modulo');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarAcciones() {
        $stmt = $this->db->query('SELECT DISTINCT accion FROM bitacora ORDER BY accion');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
