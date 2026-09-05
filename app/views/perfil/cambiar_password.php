<?php
$tituloPagina = 'Cambiar Contraseña';
require __DIR__ . '/../layout/header.php';
?>

<h1 class="h4 mb-1">Cambiar Contraseña</h1>
<p class="text-muted mb-4">Actualice la contraseña de su propia cuenta. Debe confirmar su contraseña actual.</p>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?> py-2"><?= htmlspecialchars($flash['mensaje']) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <form method="post" action="index.php?c=perfil&a=cambiarPassword" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Contraseña Actual <span class="text-danger">*</span></label>
                        <input type="password" name="contrasena_actual" class="form-control" placeholder="Ingrese su contraseña actual" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="contrasena_nueva" class="form-control" placeholder="Mínimo 8 caracteres" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="confirmar_contrasena" class="form-control" placeholder="Repita la nueva contraseña" required>
                    </div>

                    <div class="requisitos-box">
                        <p class="mb-2 fw-semibold text-teal-dark small">Requisitos:</p>
                        <ul class="mb-0 small text-muted">
                            <li>Mínimo 8 caracteres</li>
                            <li>Al menos una letra mayúscula</li>
                            <li>Al menos un número</li>
                            <li>Al menos un símbolo (!@#$%&amp;*)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="index.php?c=home&a=index" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-teal"><i class="bi bi-shield-lock"></i> Guardar Contraseña</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
