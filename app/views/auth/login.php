<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Iglesia Centroamericana Nueva Vida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .login-brand {
            text-align: center;
            font-size: 0.8rem;
            letter-spacing: .03em;
            color: #6c757d;
            margin-bottom: .25rem;
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

        .form-label .text-danger {
            font-size: 0.8rem;
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

        .login-forgot {
            display: block;
            text-align: center;
            font-size: 0.875rem;
            margin: 1rem 0 1.25rem;
        }

        .login-footer {
            text-align: center;
            font-size: 0.75rem;
            color: #adb5bd;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 2h2v7h7v2h-7v11h-2V11H4V9h7V2z" fill="#d98c22"/>
            </svg>
        </div>

        <p class="login-brand">Iglesia Centroamericana Nueva Vida</p>
        <h1 class="h5 login-title">Sistema de Control Financiero</h1>
        <p class="login-subtitle">Ingrese sus credenciales para acceder al sistema</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?c=auth&a=login" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="mb-3">
                <label for="nombre_usuario" class="form-label">Usuario <span class="text-danger">*</span></label>
                <input
                    type="text"
                    class="form-control"
                    id="nombre_usuario"
                    name="nombre_usuario"
                    placeholder="Ingrese su nombre de usuario"
                    value="<?= htmlspecialchars($nombreUsuario) ?>"
                    autofocus
                    required
                >
            </div>

            <div class="mb-2">
                <label for="contrasena" class="form-label">Contraseña <span class="text-danger">*</span></label>
                <input
                    type="password"
                    class="form-control"
                    id="contrasena"
                    name="contrasena"
                    placeholder="Ingrese su contraseña"
                    required
                >
            </div>

            <a href="#" class="login-forgot">¿Olvidó su contraseña?</a>

            <button type="submit" class="btn btn-login w-100 py-2">Iniciar Sesión</button>
        </form>

        <p class="login-footer">&copy; <?= date('Y') ?> — Iglesia Centroamericana Nueva Vida</p>
    </div>
</body>
</html>
