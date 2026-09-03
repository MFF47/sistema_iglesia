<?php
$esEdicion = $registro !== null;
$tituloPagina = $esEdicion ? 'Editar Egreso' : 'Nuevo Egreso';
$activeMenu = 'egresos';
require __DIR__ . '/../layout/header.php';

$valor = fn($campo) => htmlspecialchars((string) ($valores[$campo] ?? ''));
$accion = $esEdicion ? 'index.php?c=egresos&a=update&id=' . $registro['id_egreso'] : 'index.php?c=egresos&a=store';
$idCategoriaSeleccionada = (string) ($valores['id_categoria'] ?? '');
$idFacturaSeleccionada = (string) ($valores['id_factura'] ?? '');
?>

<h1 class="h4 mb-1"><?= htmlspecialchars($tituloPagina) ?></h1>
<p class="text-muted mb-4">
    <?= $esEdicion ? 'Actualice los datos del egreso.' : 'Registre un nuevo egreso o gasto del sistema' ?>
</p>

<form method="post" action="<?= htmlspecialchars($accion) ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Datos del Egreso</h2>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Fecha del Egreso <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="fecha_egreso"
                        class="form-control <?= isset($errores['fecha_egreso']) ? 'is-invalid' : '' ?>"
                        value="<?= $valor('fecha_egreso') ?>"
                        required
                    >
                    <?php if (isset($errores['fecha_egreso'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['fecha_egreso']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select name="id_categoria" class="form-select <?= isset($errores['id_categoria']) ? 'is-invalid' : '' ?>">
                        <option value="">Seleccione...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>" <?= $idCategoriaSeleccionada === (string) $cat['id_categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['id_categoria'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['id_categoria']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
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
                    <label class="form-label">Beneficiario <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="beneficiario"
                        class="form-control <?= isset($errores['beneficiario']) ? 'is-invalid' : '' ?>"
                        placeholder="Persona o entidad que recibe"
                        value="<?= $valor('beneficiario') ?>"
                        required
                    >
                    <?php if (isset($errores['beneficiario'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['beneficiario']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Descripción <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="descripcion"
                        class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"
                        placeholder="Detalle del concepto del egreso..."
                        value="<?= $valor('descripcion') ?>"
                        required
                    >
                    <?php if (isset($errores['descripcion'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['descripcion']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales (opcional)..."><?= $valor('observaciones') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Vincular Factura (opcional)</h2>
            <p class="text-muted small mb-3">Asocie este egreso a una factura registrada previamente.</p>

            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Factura Asociada</label>
                    <select id="id_factura" name="id_factura" class="form-select">
                        <option value="">(Sin factura asociada)</option>
                        <?php foreach ($facturas as $f): ?>
                            <option value="<?= $f['id_factura'] ?>" <?= $idFacturaSeleccionada === (string) $f['id_factura'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($f['numero_documento'] ?: ucfirst($f['tipo_documento'])) . ' — Q' . number_format($f['monto'], 2)) ?>
                                <?= $f['beneficiario'] ? ' (' . htmlspecialchars($f['beneficiario']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#modalNuevaFactura">
                        <i class="bi bi-receipt"></i> Registrar Nueva Factura
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="index.php?c=egresos&a=index" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-teal"><i class="bi bi-save"></i> Guardar Egreso</button>
    </div>
</form>

<div class="modal fade" id="modalNuevaFactura" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nueva Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="factura-error" class="alert alert-danger py-2 d-none"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                        <select id="nueva-factura-tipo" class="form-select">
                            <option value="factura">Factura</option>
                            <option value="recibo">Recibo</option>
                            <option value="nota_credito">Nota de Crédito</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número de Documento</label>
                        <input type="text" id="nueva-factura-numero" class="form-control" placeholder="Ej. F-0042">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha de Emisión <span class="text-danger">*</span></label>
                        <input type="date" id="nueva-factura-fecha" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monto (Q) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" id="nueva-factura-monto" class="form-control" placeholder="0.00">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Beneficiario</label>
                        <input type="text" id="nueva-factura-beneficiario" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-guardar-factura" class="btn btn-teal">Guardar Factura</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const btnGuardarFactura = document.getElementById('btn-guardar-factura');
    const selectFactura = document.getElementById('id_factura');
    const errorFactura = document.getElementById('factura-error');

    btnGuardarFactura.addEventListener('click', function () {
        errorFactura.classList.add('d-none');

        const datos = new URLSearchParams();
        datos.set('csrf_token', '<?= htmlspecialchars($csrfToken) ?>');
        datos.set('tipo_documento', document.getElementById('nueva-factura-tipo').value);
        datos.set('numero_documento', document.getElementById('nueva-factura-numero').value.trim());
        datos.set('fecha_emision', document.getElementById('nueva-factura-fecha').value);
        datos.set('monto', document.getElementById('nueva-factura-monto').value);
        datos.set('beneficiario', document.getElementById('nueva-factura-beneficiario').value.trim());

        fetch('index.php?c=egresos&a=facturaRapida', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: datos.toString(),
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (resultado) {
                if (!resultado.ok) {
                    errorFactura.textContent = resultado.data.error || 'No se pudo registrar la factura.';
                    errorFactura.classList.remove('d-none');
                    return;
                }

                const opcion = document.createElement('option');
                opcion.value = resultado.data.id;
                opcion.textContent = resultado.data.etiqueta;
                opcion.selected = true;
                selectFactura.appendChild(opcion);

                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevaFactura'));
                modal.hide();
            })
            .catch(function () {
                errorFactura.textContent = 'Error de conexión. Intente nuevamente.';
                errorFactura.classList.remove('d-none');
            });
    });
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
