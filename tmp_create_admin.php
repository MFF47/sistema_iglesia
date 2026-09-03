<?php
// Uso: php tmp_create_admin.php <nombre_usuario> <contrasena> <nombre_completo> <correo>
// Ejemplo: php tmp_create_admin.php admin Admin1234! "Administrador General" admin@iglesianuevavida.org
// Este script es temporal, se puede borrar una vez creado el usuario administrador.

$cfg = require __DIR__ . '/config/database.php';

$nombreUsuario = $argv[1] ?? 'admin';
$contrasena = $argv[2] ?? 'Admin1234!';
$nombreCompleto = $argv[3] ?? 'Administrador General';
$correo = $argv[4] ?? 'admin@iglesianuevavida.org';

$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$stmt = $pdo->prepare('SELECT id_rol FROM roles WHERE nombre = ?');
$stmt->execute(['Administrador']);
$idRol = $stmt->fetchColumn();

if (!$idRol) {
    echo "No se encontró el rol 'Administrador'.\n";
    exit(1);
}

$hash = password_hash($contrasena, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (id_rol, nombre_usuario, nombre_completo, correo, contrasena_hash)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$idRol, $nombreUsuario, $nombreCompleto, $correo, $hash]);

echo "Usuario administrador creado: {$nombreUsuario}\n";
