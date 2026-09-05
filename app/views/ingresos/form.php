<?php
$esEdicion = $registro !== null;
$tituloPagina = $esEdicion ? 'Editar Ingreso' : 'Nuevo Ingreso';
$activeMenu = 'ingresos';
require __DIR__ . '/../layout/header.php';

$valor = fn($campo) => htmlspecialchars((string) ($valores[$campo] ?? ''));
$accion = $esEdicion ? 'index.php?c=ingresos&a=update&id=' . $registro['id_ingreso'] : 'index.php?c=ingresos&a=store';
$idTipoSeleccionado = (string) ($valores['id_tipo_ingreso'] ?? '');
$idCategoriaSeleccionada = (string) ($valores['id_categoria'] ?? '');
$idAportanteSeleccionado = (string) ($valores['id_aportante'] ?? '');
$facturaVinculada = $esEdicion ? ($registro['factura_numero'] ?? '') : '';
?>

<h1 class="h4 mb-1"><?= htmlspecialchars($tituloPagina) ?></h1>
<p class="text-muted mb-3">
    <?= $esEdicion ? 'Actualice los datos del ingreso.' : 'Registre un nuevo ingreso económico al sistema' ?>
</p>

<div class="card card-disponible mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-safe2"></i> Disponible Caja General</span>
        <span class="h5 mb-0">Q<?= number_format((float) $disponibleCajaGeneral, 2) ?></span>
    </div>
</div>

<form method="post" action="<?= htmlspecialchars($accion) ?>" enctype="multipart/form-data" novalidate data-factura-vinculada="<?= htmlspecialchars($facturaVinculada) ?>" id="form-ingreso">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Datos del Ingreso</h2>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha del Ingreso <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="fecha_ingreso"
                        class="form-control <?= isset($errores['fecha_ingreso']) ? 'is-invalid' : '' ?>"
                        value="<?= $valor('fecha_ingreso') ?>"
                        required
                    >
                    <?php if (isset($errores['fecha_ingreso'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['fecha_ingreso']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo de Ingreso <span class="text-danger">*</span></label>
                    <select
                        id="id_tipo_ingreso"
                        name="id_tipo_ingreso"
                        class="form-select <?= isset($errores['id_tipo_ingreso']) ? 'is-invalid' : '' ?>"
                    >
                        <option value="">Seleccione...</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option
                                value="<?= $tipo['id_tipo_ingreso'] ?>"
                                data-requiere-aportante="<?= $tipo['requiere_aportante'] ?>"
                                <?= $idTipoSeleccionado === (string) $tipo['id_tipo_ingreso'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($tipo['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['id_tipo_ingreso'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errores['id_tipo_ingreso']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
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

                <div class="col-md-9">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalle del ingreso..."><?= $valor('descripcion') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Aportante (opcional)</h2>
            <p class="text-muted small mb-3">Si desea identificar al aportante, búsquelo o registre uno nuevo.</p>

            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">
                        Aportante <span id="aportante-requerido" class="text-danger" style="display:none">*</span>
                    </label>
                    <select
                        id="id_aportante"
                        name="id_aportante"
                        class="form-select <?= isset($errores['id_aportante']) ? 'is-invalid' : '' ?>"
                    >
                        <option value="">(No identificado)</option>
                        <?php foreach ($aportantes as $ap): ?>
                            <option value="<?= $ap['id_aportante'] ?>" <?= $idAportanteSeleccionado === (string) $ap['id_aportante'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ap['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['id_aportante'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['id_aportante']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#modalNuevoAportante">
                        <i class="bi bi-person-plus"></i> Registrar Nuevo Aportante
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Comprobante (opcional)</h2>
            <p class="text-muted small mb-3">
                Adjunte una foto (puede tomarla directamente con la cámara del dispositivo) o un PDF del comprobante.
                Se registrará automáticamente en <strong>Facturas y Recibos</strong> (máx. 5 MB).
            </p>

            <?php if ($facturaVinculada): ?>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-link-45deg"></i>
                    Este ingreso ya tiene el documento <strong><?= htmlspecialchars($facturaVinculada) ?></strong> vinculado.
                    Si sube un archivo nuevo aquí, se creará un documento adicional; si no sube nada, el documento existente
                    se actualizará con los datos de este ingreso al guardar.
                </div>
            <?php endif; ?>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label for="comprobante_camara" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-camera"></i> Tomar Foto
                    </label>
                    <input type="file" id="comprobante_camara" class="d-none" accept="image/*" capture="environment">
                </div>
                <div class="col-6">
                    <label for="comprobante" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-folder2-open"></i> Elegir Archivo
                    </label>
                </div>
            </div>

            <label for="comprobante" class="dropzone d-block mb-0">
                <i class="bi bi-cloud-arrow-up fs-3 d-block mb-2"></i>
                <span id="comprobante-texto">Arrastre el archivo aquí, tome una foto o haga clic para seleccionar</span>
                <input type="file" id="comprobante" name="comprobante" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
            </label>
            <p class="text-muted small mt-2 mb-0">Formatos permitidos: PDF, JPG, PNG.</p>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="index.php?c=ingresos&a=index" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-teal"><i class="bi bi-save"></i> Guardar Ingreso</button>
    </div>
</form>

<div class="modal fade" id="modalNuevoAportante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nuevo Aportante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="aportante-error" class="alert alert-danger py-2 d-none"></div>
                <div class="mb-3">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" id="nuevo-aportante-nombre" class="form-control" placeholder="Ej. María López Hernández">
                </div>
                <div class="mb-3">
                    <label class="form-label">Documento de Identidad</label>
                    <input type="text" id="nuevo-aportante-documento" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" id="nuevo-aportante-telefono" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-guardar-aportante" class="btn btn-teal">Guardar Aportante</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const selectTipo = document.getElementById('id_tipo_ingreso');
    const marcaRequerido = document.getElementById('aportante-requerido');

    function actualizarRequerido() {
        const opcion = selectTipo.options[selectTipo.selectedIndex];
        const requiere = opcion && opcion.dataset.requiereAportante === '1';
        marcaRequerido.style.display = requiere ? 'inline' : 'none';
    }

    selectTipo.addEventListener('change', actualizarRequerido);
    actualizarRequerido();

    const inputArchivo = document.getElementById('comprobante');
    const inputCamara = document.getElementById('comprobante_camara');
    const dropzone = inputArchivo.closest('.dropzone');
    const texto = document.getElementById('comprobante-texto');

    inputArchivo.addEventListener('change', function () {
        if (inputArchivo.files.length > 0) {
            texto.textContent = inputArchivo.files[0].name;
        }
    });

    inputCamara.addEventListener('change', function () {
        if (inputCamara.files.length > 0) {
            inputArchivo.files = inputCamara.files;
            texto.textContent = inputCamara.files[0].name;
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

    const formIngreso = document.getElementById('form-ingreso');
    const facturaVinculada = formIngreso.dataset.facturaVinculada;

    formIngreso.addEventListener('submit', function (e) {
        if (facturaVinculada && inputArchivo.files.length === 0) {
            const continuar = confirm(
                'Este ingreso tiene el documento "' + facturaVinculada + '" vinculado. ' +
                'Al guardar, ese documento también se actualizará con los nuevos datos. ¿Desea continuar?'
            );
            if (!continuar) {
                e.preventDefault();
            }
        }
    });

    const btnGuardarAportante = document.getElementById('btn-guardar-aportante');
    const selectAportante = document.getElementById('id_aportante');
    const errorAportante = document.getElementById('aportante-error');

    btnGuardarAportante.addEventListener('click', function () {
        const nombre = document.getElementById('nuevo-aportante-nombre').value.trim();
        errorAportante.classList.add('d-none');

        const datos = new URLSearchParams();
        datos.set('csrf_token', '<?= htmlspecialchars($csrfToken) ?>');
        datos.set('nombre_completo', nombre);
        datos.set('documento_identidad', document.getElementById('nuevo-aportante-documento').value.trim());
        datos.set('telefono', document.getElementById('nuevo-aportante-telefono').value.trim());

        fetch('index.php?c=ingresos&a=aportanteRapido', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: datos.toString(),
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (resultado) {
                if (!resultado.ok) {
                    errorAportante.textContent = resultado.data.error || 'No se pudo registrar el aportante.';
                    errorAportante.classList.remove('d-none');
                    return;
                }

                const opcion = document.createElement('option');
                opcion.value = resultado.data.id;
                opcion.textContent = resultado.data.nombre_completo;
                opcion.selected = true;
                selectAportante.appendChild(opcion);

                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoAportante'));
                modal.hide();
            })
            .catch(function () {
                errorAportante.textContent = 'Error de conexión. Intente nuevamente.';
                errorAportante.classList.remove('d-none');
            });
    });
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
