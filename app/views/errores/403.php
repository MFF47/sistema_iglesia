<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5 text-center">
        <h1 class="display-6 text-danger mb-3">403 - Acceso denegado</h1>
        <p class="lead">
            Su rol actual (<?= htmlspecialchars($usuario['rol_nombre'] ?? '') ?>)
            no tiene permisos para acceder a este módulo.
        </p>
        <a href="index.php?c=home&a=index" class="btn btn-outline-secondary mt-2">Volver al inicio</a>
    </div>
</body>
</html>
