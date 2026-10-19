<?php
// Instalación inicial: crea el primer usuario Administrador.
// Se niega a correr si ya existe un Administrador (no se puede usar para crear
// cuentas adicionales ni para resetear nada — eso se hace desde la aplicación).
//
// Uso por línea de comandos (si el hosting tiene SSH/Terminal):
//   php sql/crear_administrador.php <usuario> <nombre_completo> <correo> <contrasena>
//   Ejemplo: php sql/crear_administrador.php admin "Administrador General" admin@finanzasnv.com "Clave#Segura1"
//
// Uso por navegador (si no hay SSH): copie este archivo temporalmente dentro
// de public/, ábralo en el navegador, complete el formulario, y confirme.
// El script se borra solo en cuanto crea el usuario correctamente.
//
// La contraseña debe cumplir la misma política que el resto del sistema:
// mínimo 8 caracteres, una mayúscula, un número y un símbolo (!@#$%&*).

require_once __DIR__ . '/../core/PasswordPolicy.php';

$cfg = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$yaExisteAdmin = (bool) $pdo->query(
    "SELECT 1 FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol
     WHERE r.nombre = 'Administrador' LIMIT 1"
)->fetchColumn();

$esCli = (php_sapi_name() === 'cli');

function salir($mensaje, $esCli) {
    if ($esCli) {
        fwrite(STDERR, $mensaje . "\n");
        exit(1);
    }
    http_response_code(400);
    echo "<p style='font-family:sans-serif;color:#b00'>" . htmlspecialchars($mensaje) . "</p>";
    exit;
}

if ($yaExisteAdmin) {
    salir(
        'Ya existe un usuario Administrador en el sistema. Este script no permite crear otro ni se usa para reiniciar contraseñas — use "Gestión > Usuarios" o "Cambiar contraseña" dentro de la aplicación, o bórrelo si ya no lo necesita.',
        $esCli
    );
}

if ($esCli) {
    $nombreUsuario = trim($argv[1] ?? '');
    $nombreCompleto = trim($argv[2] ?? '');
    $correo = trim($argv[3] ?? '');
    $contrasena = $argv[4] ?? '';
} else {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"><title>Crear Administrador</title></head>
        <body style="font-family:sans-serif;max-width:420px;margin:60px auto;">
            <h1 style="font-size:1.3rem;">Instalación: crear Administrador</h1>
            <p style="color:#555;font-size:0.9rem;">Este formulario solo funciona una vez. Al crear el usuario, este archivo se borra automáticamente.</p>
            <form method="post">
                <p><label>Nombre de usuario<br><input name="nombre_usuario" required style="width:100%;padding:6px;"></label></p>
                <p><label>Nombre completo<br><input name="nombre_completo" required style="width:100%;padding:6px;"></label></p>
                <p><label>Correo<br><input type="email" name="correo" required style="width:100%;padding:6px;"></label></p>
                <p><label>Contraseña<br><input type="password" name="contrasena" required style="width:100%;padding:6px;"></label></p>
                <p style="font-size:0.8rem;color:#777;">Mínimo 8 caracteres, una mayúscula, un número y un símbolo (!@#$%&amp;*).</p>
                <button type="submit" style="padding:8px 16px;">Crear Administrador</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
    $nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
}

if (strlen($nombreUsuario) < 3) {
    salir('El nombre de usuario debe tener al menos 3 caracteres.', $esCli);
}
if ($nombreCompleto === '') {
    salir('El nombre completo es obligatorio.', $esCli);
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    salir('Ingrese un correo electrónico válido.', $esCli);
}
if (!PasswordPolicy::cumple($contrasena)) {
    salir('La contraseña no cumple la política de seguridad: ' . PasswordPolicy::DESCRIPCION, $esCli);
}

$idRol = $pdo->query("SELECT id_rol FROM roles WHERE nombre = 'Administrador'")->fetchColumn();
if (!$idRol) {
    salir("No existe el rol 'Administrador' — importe primero sql/instalacion.sql.", $esCli);
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (id_rol, nombre_usuario, nombre_completo, correo, contrasena_hash)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$idRol, $nombreUsuario, $nombreCompleto, $correo, $hash]);

$autoborrado = @unlink(__FILE__);

$mensaje = "Administrador '{$nombreUsuario}' creado correctamente.";
$mensaje .= $autoborrado
    ? ' Este script se eliminó automáticamente.'
    : ' No se pudo autoeliminar este archivo — bórrelo manualmente ahora mismo.';

if ($esCli) {
    echo $mensaje . "\n";
} else {
    echo "<p style='font-family:sans-serif;color:#276749'>" . htmlspecialchars($mensaje) . "</p>";
}
