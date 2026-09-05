<?php
$esEdicion = $registro !== null;
$tituloPagina = $esEdicion ? 'Editar Factura / Recibo' : 'Nueva Factura / Recibo';
$activeMenu = 'facturas';
require __DIR__ . '/../layout/header.php';

$valor = fn($campo) => htmlspecialchars((string) ($valores[$campo] ?? ''));
$accion = $esEdicion ? 'index.php?c=facturas&a=update&id=' . $registro['id_factura'] : 'index.php?c=facturas&a=store';
$tipoSeleccionado = (string) ($valores['tipo_documento'] ?? '');
$idEgresoSeleccionado = (string) ($valores['id_egreso_vincular'] ?? ($registro['egreso_vinculado_id'] ?? ''));
$idIngresoSeleccionado = (string) ($valores['id_ingreso_vincular'] ?? ($registro['ingreso_vinculado_id'] ?? ''));
$tieneVinculo = $esEdicion && (!empty($registro['egreso_vinculado_id']) || !empty($registro['ingreso_vinculado_id']));
$etiquetaVinculo = $esEdicion
    ? (!empty($registro['egreso_vinculado_id']) ? 'Egreso #' . $registro['egreso_vinculado_id'] : (!empty($registro['ingreso_vinculado_id']) ? 'Ingreso #' . $registro['ingreso_vinculado_id'] : ''))
    : '';
?>

<h1 class="h4 mb-1"><?= htmlspecialchars($tituloPagina) ?></h1>
<p class="text-muted mb-4">
    <?= $esEdicion ? 'Actualice los datos del documento.' : 'Registre un documento de respaldo de movimiento económico' ?>
</p>

<form
    method="post"
    action="<?= htmlspecialchars($accion) ?>"
    enctype="multipart/form-data"
    novalidate
    id="form-factura"
    data-vinculo="<?= htmlspecialchars($etiquetaVinculo) ?>"
>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <?php if ($tieneVinculo): ?>
        <div class="alert alert-info py-2 small">
            <i class="bi bi-link-45deg"></i>
            Este documento está vinculado a <strong><?= htmlspecialchars($etiquetaVinculo) ?></strong>.
            Si cambia el monto, la fecha o el beneficiario, ese registro se actualizará automáticamente al guardar.
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Datos del Documento</h2>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Número de Documento <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="numero_documento"
                        class="form-control <?= isset($errores['numero_documento']) ? 'is-invalid' : '' ?>"
                        placeholder="Ej. F-0043"
                        value="<?= $valor('numero_documento') ?>"
                        required
                    >
                    <?php if (isset($errores['numero_documento'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['numero_documento']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                    <select name="tipo_documento" class="form-select <?= isset($errores['tipo_documento']) ? 'is-invalid' : '' ?>">
                        <option value="factura" <?= $tipoSeleccionado === 'factura' ? 'selected' : '' ?>>Factura</option>
                        <option value="recibo" <?= $tipoSeleccionado === 'recibo' ? 'selected' : '' ?>>Recibo</option>
                        <option value="nota_credito" <?= $tipoSeleccionado === 'nota_credito' ? 'selected' : '' ?>>Nota de Crédito</option>
                    </select>
                    <?php if (isset($errores['tipo_documento'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['tipo_documento']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha de Emisión <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="fecha_emision"
                        class="form-control <?= isset($errores['fecha_emision']) ? 'is-invalid' : '' ?>"
                        value="<?= $valor('fecha_emision') ?>"
                        required
                    >
                    <?php if (isset($errores['fecha_emision'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['fecha_emision']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Monto (Q) <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="monto"
                        class="form-control <?= isset($errores['monto']) ? 'is-invalid' : '' ?>"
                        placeholder="0.00"
                        value="<?= $valor('monto') ?>"
                        required
                    >
                    <?php if (isset($errores['monto'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['monto']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Beneficiario / Aportante <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="beneficiario"
                        class="form-control <?= isset($errores['beneficiario']) ? 'is-invalid' : '' ?>"
                        placeholder="Nombre de la empresa o persona"
                        value="<?= $valor('beneficiario') ?>"
                        required
                    >
                    <?php if (isset($errores['beneficiario'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['beneficiario']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Descripción</label>
                    <input
                        type="text"
                        name="descripcion"
                        class="form-control"
                        placeholder="Concepto o detalle del documento..."
                        value="<?= $valor('descripcion') ?>"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Archivo del Documento <?= $esEdicion ? '' : '<span class="text-danger">*</span>' ?></h2>
            <p class="text-muted small mb-3">Adjunte la imagen escaneada o PDF del documento original (máx. 5 MB).</p>

            <?php if ($esEdicion && !empty($registro['ruta_archivo'])): ?>
                <p class="small mb-2">
                    Archivo actual:
                    <a href="index.php?c=facturas&a=verArchivo&id=<?= $registro['id_factura'] ?>" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Ver documento
                    </a>
                </p>
            <?php endif; ?>

            <label for="archivo" class="dropzone d-block mb-0 <?= isset($errores['archivo']) ? 'border-danger text-danger' : '' ?>">
                <i class="bi bi-cloud-arrow-up fs-3 d-block mb-2"></i>
                <span id="archivo-texto">
                    <?= $esEdicion ? 'Arrastre un archivo para reemplazar el actual (opcional)' : 'Arrastre el archivo aquí o haga clic para seleccionar' ?>
                </span>
                <input type="file" id="archivo" name="archivo" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
            </label>
            <?php if (isset($errores['archivo'])): ?>
                <div class="text-danger small mt-2"><?= htmlspecialchars($errores['archivo']) ?></div>
            <?php endif; ?>
            <p class="text-muted small mt-2 mb-0">Formatos permitidos: PDF, JPG, PNG.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Vincular a un Movimiento (opcional)</h2>
            <p class="text-muted small mb-3">Asocie esta factura a un ingreso o egreso ya registrado.</p>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted">Egreso Asociado</label>
                    <select name="id_egreso_vincular" class="form-select">
                        <option value="">(Sin vincular)</option>
                        <?php foreach ($egresos as $egreso): ?>
                            <option value="<?= $egreso['id_egreso'] ?>" <?= $idEgresoSeleccionado === (string) $egreso['id_egreso'] ? 'selected' : '' ?>>
                                Egreso #<?= $egreso['id_egreso'] ?> — <?= htmlspecialchars($egreso['beneficiario']) ?> — Q<?= number_format($egreso['monto'], 2) ?>
                                <?= $egreso['id_factura'] ? ' (ya tiene documento vinculado)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">Ingreso Asociado</label>
                    <select name="id_ingreso_vincular" class="form-select">
                        <option value="">(Sin vincular)</option>
                        <?php foreach ($ingresos as $ingreso): ?>
                            <option value="<?= $ingreso['id_ingreso'] ?>" <?= $idIngresoSeleccionado === (string) $ingreso['id_ingreso'] ? 'selected' : '' ?>>
                                Ingreso #<?= $ingreso['id_ingreso'] ?> — <?= htmlspecialchars($ingreso['aportante_nombre'] ?? 'No identificado') ?> — Q<?= number_format($ingreso['monto'], 2) ?>
                                <?= $ingreso['id_factura'] ? ' (ya tiene documento vinculado)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="index.php?c=facturas&a=index" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-teal"><i class="bi bi-save"></i> Guardar Factura</button>
    </div>
</form>

<script>
(function () {
    const inputArchivo = document.getElementById('archivo');
    const dropzone = inputArchivo.closest('.dropzone');
    const texto = document.getElementById('archivo-texto');

    inputArchivo.addEventListener('change', function () {
        if (inputArchivo.files.length > 0) {
            texto.textContent = inputArchivo.files[0].name;
        }
    });

    ['dragover', 'dragleave', 'drop'].forEach(function (evento) {
        dropzone.addEventListener(evento, function (e) {
            e.preventDefault();
            dropzone.classList.toggle('dragover', evento === 'dragover');
        });
    });

    dropzone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length > 0) {
            inputArchivo.files = e.dataTransfer.files;
            texto.textContent = e.dataTransfer.files[0].name;
        }
    });

    const formFactura = document.getElementById('form-factura');
    const vinculo = formFactura.dataset.vinculo;

    formFactura.addEventListener('submit', function (e) {
        if (vinculo) {
            const continuar = confirm(
                'Este documento está vinculado a "' + vinculo + '". ' +
                'Al guardar, ese registro también se actualizará con los nuevos datos. ¿Desea continuar?'
            );
            if (!continuar) {
                e.preventDefault();
            }
        }
    });
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
