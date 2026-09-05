<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - Iglesia Centroamericana Nueva Vida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --navy: #16324a;
            --teal: #0f4c5c;
            --teal-dark: #0c3a46;
            --amber: #d98c22;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--teal) 0%, var(--navy) 100%);
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, 0.25);
            padding: 2.5rem 2.25rem;
        }

        .login-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .login-title {
            text-align: center;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: .35rem;
        }

        .login-subtitle {
            text-align: center;
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 1.75rem;
        }

        .btn-login {
            background: var(--teal-dark);
            border-color: var(--teal-dark);
            color: #fff;
            font-weight: 600;
        }

        .btn-login:hover {
            background: var(--navy);
            border-color: var(--navy);
            color: #fff;
        }

        .btn-amber {
            background: var(--amber);
            border-color: var(--amber);
            color: #fff;
            font-weight: 600;
        }

        .btn-amber:hover {
            background: #c17d1a;
            border-color: #c17d1a;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-icon">
            <i class="bi bi-lock-fill" style="color: var(--amber); font-size: 1.5rem;"></i>
        </div>

        <h1 class="h5 login-title">Recuperar Contraseña</h1>
        <p class="login-subtitle">Ingrese su correo registrado y le enviaremos un enlace temporal para restablecer su contraseña.</p>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success py-2" role="alert">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?c=auth&a=recuperar" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                <input
                    type="email"
                    class="form-control"
                    id="correo"
                    name="correo"
                    placeholder="usuario@nuevavida.org"
                    value="<?= htmlspecialchars($correo) ?>"
                    autofocus
                    required
                >
            </div>

            <button type="submit" class="btn btn-login w-100 py-2 mb-2">Enviar Enlace de Recuperación</button>
            <a href="index.php?c=auth&a=login" class="btn btn-amber w-100 py-2"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
        </form>
    </div>
</body>
</html>
