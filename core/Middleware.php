<?php
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/View.php';

class Middleware {
    public static function requireAuth() {
        if (!Auth::check()) {
            header('Location: index.php?c=auth&a=login');
            exit;
        }
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
}
