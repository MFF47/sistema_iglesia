<?php
class PasswordPolicy {
    const DESCRIPCION = 'Mínimo 8 caracteres, una mayúscula, un número y un símbolo (!@#$%&*).';

    public static function cumple($contrasena) {
        return strlen($contrasena) >= 8
            && preg_match('/[A-Z]/', $contrasena)
            && preg_match('/[0-9]/', $contrasena)
            && preg_match('/[!@#$%&*]/', $contrasena);
    }
}
