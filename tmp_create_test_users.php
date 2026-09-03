<?php
// Crea un usuario de prueba por cada rol (excepto Administrador, que ya se crea con tmp_create_admin.php).
// Uso: php tmp_create_test_users.php
// Script temporal, se puede borrar cuando ya no se necesiten los usuarios de prueba.

$cfg = require __DIR__ . '/config/database.php';

$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$contrasena = 'Tester123!';
$hash = password_hash($contrasena, PASSWORD_BCRYPT);

$usuarios = [
    ['rol' => 'Tesorero',   'nombre_usuario' => 'tesorero1',   'nombre_completo' => 'Usuario de Prueba Tesorero',   'correo' => 'tesorero1@iglesianuevavida.org'],
    ['rol' => 'Supervisor', 'nombre_usuario' => 'supervisor1', 'nombre_completo' => 'Usuario de Prueba Supervisor', 'correo' => 'supervisor1@iglesianuevavida.org'],
    ['rol' => 'Consulta',   'nombre_usuario' => 'consulta1',   'nombre_completo' => 'Usuario de Prueba Consulta',   'correo' => 'consulta1@iglesianuevavida.org'],
];

$stmtRol = $pdo->prepare('SELECT id_rol FROM roles WHERE nombre = ?');
$stmtInsert = $pdo->prepare(
    'INSERT INTO usuarios (id_rol, nombre_usuario, nombre_completo, correo, contrasena_hash)
     VALUES (?, ?, ?, ?, ?)'
);
$stmtExiste = $pdo->prepare('SELECT 1 FROM usuarios WHERE nombre_usuario = ?');

foreach ($usuarios as $u) {
    $stmtExiste->execute([$u['nombre_usuario']]);
    if ($stmtExiste->fetchColumn()) {
        echo "Ya existe: {$u['nombre_usuario']}\n";
        continue;
    }

    $stmtRol->execute([$u['rol']]);
    $idRol = $stmtRol->fetchColumn();

    if (!$idRol) {
        echo "Rol no encontrado: {$u['rol']}\n";
        continue;
    }

    $stmtInsert->execute([$idRol, $u['nombre_usuario'], $u['nombre_completo'], $u['correo'], $hash]);
    echo "Usuario creado: {$u['nombre_usuario']} (rol {$u['rol']}), contraseña: {$contrasena}\n";
}
