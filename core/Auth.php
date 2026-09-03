<?php
class Auth {
    public static function login(array $usuario) {
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario'      => $usuario['id_usuario'],
            'nombre_usuario'  => $usuario['nombre_usuario'],
            'nombre_completo' => $usuario['nombre_completo'],
            'correo'          => $usuario['correo'],
            'id_rol'          => $usuario['id_rol'],
            'rol_nombre'      => $usuario['rol_nombre'],
            'rol_descripcion' => $usuario['rol_descripcion'],
        ];
    }

    public static function logout() {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function check() {
        return isset($_SESSION['usuario']);
    }

    public static function user() {
        return $_SESSION['usuario'] ?? null;
    }
}
