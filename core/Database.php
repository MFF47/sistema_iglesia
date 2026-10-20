<?php
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $cfg = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";

            self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // NOW() y CURRENT_TIMESTAMP() los calcula MySQL con el reloj del
            // sistema operativo del servidor de base de datos, no con el de
            // PHP — en hosting compartido ese reloj no necesariamente está en
            // hora de Guatemala. Guatemala no usa horario de verano, así que
            // un offset fijo es siempre correcto (no depende de que el motor
            // tenga cargadas las tablas de zonas horarias con nombre).
            self::$instance->exec("SET time_zone = '-06:00'");
        }

        return self::$instance;
    }
}
