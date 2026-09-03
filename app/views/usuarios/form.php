<?php
$esEdicion = $registro !== null;
$tituloPagina = $esEdicion ? 'Editar Usuario' : 'Nuevo Usuario';
$activeMenu = 'usuarios';
require __DIR__ . '/../layout/header.php';

$valor = fn($campo) => htmlspecialchars($valores[$campo] ?? '');
$accion = $esEdicion ? 'index.php?c=usuarios&a=update&id=' . $registro['id_usuario'] : 'index.php?c=usuarios&a=store';
$idRolSeleccionado = (string) ($valores['id_rol'] ?? '');
$activoSeleccionado = array_key_exists('activo', $valores) ? (string) $valores['activo'] : '1';
?>

<h1 class="h4 mb-1"><?= htmlspecialchars($tituloPagina) ?></h1>
<p class="text-muted mb-4">
    <?= $esEdicion ? 'Actualice los datos del usuario.' : 'Complete los datos para registrar un nuevo usuario en el sistema' ?>
</p>

<form method="post" action="<?= htmlspecialchars($accion) ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Datos del Usuario</h2>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nombre_usuario"
                        class="form-control <?= isset($errores['nombre_usuario']) ? 'is-invalid' : '' ?>"
                        placeholder="Ej. mlopez"
                        value="<?= $valor('nombre_usuario') ?>"
                        required
                    >
                    <?php if (isset($errores['nombre_usuario'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['nombre_usuario']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nombre_completo"
                        class="form-control <?= isset($errores['nombre_completo']) ? 'is-invalid' : '' ?>"
                        placeholder="Ej. María López Hernández"
                        value="<?= $valor('nombre_completo') ?>"
                        required
                    >
                    <?php if (isset($errores['nombre_completo'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['nombre_completo']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        name="correo"
                        class="form-control <?= isset($errores['correo']) ? 'is-invalid' : '' ?>"
                        placeholder="usuario@nuevavida.org"
                        value="<?= $valor('correo') ?>"
                        required
                    >
                    <?php if (isset($errores['correo'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['correo']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input
                        type="text"
                        name="telefono"
                        class="form-control"
                        placeholder="+502 0000-0000"
                        value="<?= $valor('telefono') ?>"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Credenciales y Rol</h2>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        Contraseña <?= $esEdicion ? '' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input
                        type="password"
                        name="contrasena"
                        class="form-control <?= isset($errores['contrasena']) ? 'is-invalid' : '' ?>"
                        placeholder="<?= $esEdicion ? 'Dejar en blanco para no cambiarla' : 'Mínimo 8 caracteres' ?>"
                    >
                    <?php if (isset($errores['contrasena'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['contrasena']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Confirmar Contraseña <?= $esEdicion ? '' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input
                        type="password"
                        name="confirmar_contrasena"
                        class="form-control <?= isset($errores['confirmar_contrasena']) ? 'is-invalid' : '' ?>"
                        placeholder="Repita la contraseña"
                    >
                    <?php if (isset($errores['confirmar_contrasena'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['confirmar_contrasena']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Rol del Usuario <span class="text-danger">*</span></label>
                    <select name="id_rol" class="form-select <?= isset($errores['id_rol']) ? 'is-invalid' : '' ?>">
                        <option value="">Seleccione un rol...</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id_rol'] ?>" <?= $idRolSeleccionado === (string) $rol['id_rol'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['id_rol'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['id_rol']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <select name="activo" class="form-select">
                        <option value="1" <?= $activoSeleccionado === '1' ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= $activoSeleccionado === '0' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="index.php?c=usuarios&a=index" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-teal"><i class="bi bi-save"></i> Guardar Usuario</button>
    </div>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
