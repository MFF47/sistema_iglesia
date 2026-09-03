<?php
class Permisos {
    private static $mapa = null;

    private static function mapa() {
        if (self::$mapa === null) {
            self::$mapa = require __DIR__ . '/../config/permisos.php';
        }

        return self::$mapa;
    }

    public static function rolesPermitidos($modulo) {
        return self::mapa()[$modulo] ?? [];
    }

    public static function puedeAcceder($modulo, $rolNombre) {
        return in_array($rolNombre, self::rolesPermitidos($modulo), true);
    }
}
