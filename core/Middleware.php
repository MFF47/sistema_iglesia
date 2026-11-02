<?php
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/Permisos.php';
require_once __DIR__ . '/Flash.php';

class Middleware {
    const MINUTOS_INACTIVIDAD = 15;

    public static function requireAuth() {
        if (!Auth::check()) {
            header('Location: index.php?c=auth&a=login');
            exit;
        }

        $limite = self::MINUTOS_INACTIVIDAD * 60;
        if (time() - ($_SESSION['ultima_actividad'] ?? time()) > $limite) {
            Auth::logout();
            session_start();
            Flash::set('error', 'Su sesión se cerró automáticamente por inactividad.');
            header('Location: index.php?c=auth&a=login');
            exit;
        }

        $_SESSION['ultima_actividad'] = time();
    }

    public static function requireRole(array $rolesPermitidos) {
        self::requireAuth();

        $usuario = Auth::user();

        if (!in_array($usuario['rol_nombre'], $rolesPermitidos, true)) {
            http_response_code(403);
            View::render('errores.403', ['usuario' => $usuario]);
            exit;
        }
    }

    public static function requirePermiso($modulo) {
        self::requireRole(Permisos::rolesPermitidos($modulo));
    }
}
