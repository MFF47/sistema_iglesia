<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña - Iglesia Centroamericana Nueva Vida</title>
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

        .requisitos-box {
            background: #f4f6f8;
            border: 1px solid #e5e9ec;
            border-radius: .5rem;
            padding: .85rem 1rem;
            margin-bottom: 1.25rem;
        }

        .requisitos-box p {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--navy);
            margin-bottom: .35rem;
        }

        .requisitos-box ul {
            margin-bottom: 0;
            padding-left: 1.1rem;
            font-size: 0.82rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <?php if (!$tokenValido): ?>
            <div class="login-icon">
                <i class="bi bi-exclamation-triangle-fill" style="color: var(--amber); font-size: 1.5rem;"></i>
            </div>

            <h1 class="h5 login-title">Enlace inválido o expirado</h1>
            <p class="login-subtitle">El enlace de recuperación ya no es válido. Solicite uno nuevo para continuar.</p>

            <a href="index.php?c=auth&a=recuperar" class="btn btn-login w-100 py-2 mb-2">Solicitar nuevo enlace</a>
            <a href="index.php?c=auth&a=login" class="btn btn-amber w-100 py-2"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
        <?php else: ?>
            <div class="login-icon">
                <i class="bi bi-key-fill" style="color: var(--amber); font-size: 1.5rem;"></i>
            </div>

            <h1 class="h5 login-title">Nueva Contraseña</h1>
            <p class="login-subtitle">Ingrese su nueva contraseña. Debe contener al menos 8 caracteres, una mayúscula, un número y un símbolo.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?c=auth&a=restablecer" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="mb-3">
                    <label for="contrasena" class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="contrasena"
                        name="contrasena"
                        placeholder="Mínimo 8 caracteres"
                        autofocus
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="confirmar_contrasena"
                        name="confirmar_contrasena"
                        placeholder="Repita la nueva contraseña"
                        required
                    >
                </div>

                <div class="requisitos-box">
                    <p class="mb-2">Requisitos:</p>
                    <ul>
                        <li>Mínimo 8 caracteres</li>
                        <li>Al menos una letra mayúscula</li>
                        <li>Al menos un número</li>
                        <li>Al menos un símbolo (!@#$%&*)</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-login w-100 py-2">Guardar Nueva Contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
