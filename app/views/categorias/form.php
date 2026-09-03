<?php
$esEdicion = $registro !== null;
$tituloPagina = $esEdicion ? 'Editar Categoría' : 'Nueva Categoría';
$activeMenu = 'categorias';
require __DIR__ . '/../layout/header.php';

$valor = fn($campo) => htmlspecialchars($valores[$campo] ?? '');
$accion = $esEdicion ? 'index.php?c=categorias&a=update&id=' . $registro['id_categoria'] : 'index.php?c=categorias&a=store';
$tipoSeleccionado = (string) ($valores['tipo'] ?? '');
$activoSeleccionado = array_key_exists('activo', $valores) ? (string) $valores['activo'] : '1';
?>

<h1 class="h4 mb-1"><?= htmlspecialchars($tituloPagina) ?></h1>
<p class="text-muted mb-4">
    <?= $esEdicion ? 'Actualice los datos de la categoría.' : 'Cree una nueva categoría para clasificar ingresos o egresos' ?>
</p>

<form method="post" action="<?= htmlspecialchars($accion) ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Datos de la Categoría</h2>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre de la Categoría <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                        placeholder="Ej. Diezmos y Ofrendas"
                        value="<?= $valor('nombre') ?>"
                        required
                    >
                    <?php if (isset($errores['nombre'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['nombre']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo" class="form-select <?= isset($errores['tipo']) ? 'is-invalid' : '' ?>">
                        <option value="">Seleccione un tipo...</option>
                        <option value="ingreso" <?= $tipoSeleccionado === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
                        <option value="egreso" <?= $tipoSeleccionado === 'egreso' ? 'selected' : '' ?>>Egreso</option>
                    </select>
                    <?php if (isset($errores['tipo'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['tipo']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Descripción</label>
                    <textarea
                        name="descripcion"
                        class="form-control"
                        rows="3"
                        placeholder="Breve descripción del propósito de esta categoría..."
                    ><?= $valor('descripcion') ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="activo" class="form-select">
                        <option value="1" <?= $activoSeleccionado === '1' ? 'selected' : '' ?>>Activa</option>
                        <option value="0" <?= $activoSeleccionado === '0' ? 'selected' : '' ?>>Inactiva</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="index.php?c=categorias&a=index" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-teal"><i class="bi bi-save"></i> Guardar Categoría</button>
    </div>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
