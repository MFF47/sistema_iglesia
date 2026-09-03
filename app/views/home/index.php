<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Sistema Financiero y Administrativo</h1>
            <a href="index.php?c=auth&a=logout" class="btn btn-outline-secondary btn-sm">Cerrar sesión</a>
        </div>
        <p class="lead mb-1">
            Bienvenido, <?= htmlspecialchars($usuario['nombre_completo']) ?>
            (<?= htmlspecialchars($usuario['rol_nombre']) ?>).
        </p>
        <p class="text-muted small">
            <?= htmlspecialchars($usuario['rol_descripcion']) ?>
        </p>
    </div>
</body>
</html>
