<?php
$esEdicion = $registro !== null;
$tituloPagina = $esEdicion ? 'Editar Aportante' : 'Nuevo Aportante';
$activeMenu = 'aportantes';
require __DIR__ . '/../layout/header.php';

$valor = fn($campo) => htmlspecialchars((string) ($valores[$campo] ?? ''));
$accion = $esEdicion ? 'index.php?c=aportantes&a=update&id=' . $registro['id_aportante'] : 'index.php?c=aportantes&a=store';
?>

<h1 class="h4 mb-1"><?= htmlspecialchars($tituloPagina) ?></h1>
<p class="text-muted mb-4">
    <?= $esEdicion ? 'Actualice los datos del aportante.' : 'Registre una persona identificada en los ingresos del sistema' ?>
</p>

<form method="post" action="<?= htmlspecialchars($accion) ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Datos del Aportante</h2>

            <div class="row g-3">
                <div class="col-md-8">
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

                <div class="col-md-4">
                    <label class="form-label">Documento de Identidad</label>
                    <input
                        type="text"
                        name="documento_identidad"
                        class="form-control"
                        placeholder="DPI / NIT"
                        value="<?= $valor('documento_identidad') ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="+502 0000-0000" value="<?= $valor('telefono') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Correo Electrónico</label>
                    <input
                        type="email"
                        name="correo"
                        class="form-control <?= isset($errores['correo']) ? 'is-invalid' : '' ?>"
                        placeholder="correo@ejemplo.org"
                        value="<?= $valor('correo') ?>"
                    >
                    <?php if (isset($errores['correo'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['correo']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?= $valor('direccion') ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= $valor('observaciones') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="index.php?c=aportantes&a=index" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-teal"><i class="bi bi-save"></i> Guardar Aportante</button>
    </div>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
