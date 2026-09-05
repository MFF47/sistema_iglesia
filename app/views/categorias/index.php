<?php
$tituloPagina = 'Categorías';
$activeMenu = 'categorias';
require __DIR__ . '/../layout/header.php';

$renderTabla = function ($titulo, $iconoTitulo, $claseBadge, $categorias) use ($csrfToken) {
    ?>
    <div class="card mb-3">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi <?= $iconoTitulo ?>"></i>
            <span class="fw-semibold"><?= htmlspecialchars($titulo) ?></span>
            <span class="badge <?= $claseBadge ?> ms-1"><?= count($categorias) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Movimientos</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No se encontraron categorías.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($categorias as $fila): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($fila['nombre']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($fila['descripcion'] ?? '') ?></td>
                            <td><?= (int) $fila['movimientos'] ?></td>
                            <td>
                                <?php if ($fila['activo']): ?>
                                    <span class="badge text-bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a
                                    href="index.php?c=categorias&a=edit&id=<?= $fila['id_categoria'] ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Editar"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="post"
                                    action="index.php?c=categorias&a=toggleEstado"
                                    class="d-inline"
                                    onsubmit="return confirm('<?= $fila['activo'] ? 'Desactivar' : 'Activar' ?> la categoría <?= htmlspecialchars(addslashes($fila['nombre'])) ?>?');"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= $fila['id_categoria'] ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-sm <?= $fila['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                        title="<?= $fila['activo'] ? 'Desactivar' : 'Activar' ?>"
                                    >
                                        <i class="bi <?= $fila['activo'] ? 'bi-slash-circle' : 'bi-check-circle' ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
};
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h4 mb-1">Categorías</h1>
    <a href="index.php?c=categorias&a=create" class="btn btn-warning text-dark fw-semibold">
        <i class="bi bi-plus-lg"></i> Nueva Categoría
    </a>
</div>
<p class="text-muted mb-4">Clasificación de ingresos y egresos del sistema</p>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipo'] === 'success' ? 'success' : 'danger') ?>">
        <?= htmlspecialchars($flash['mensaje']) ?>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="categorias">
            <input type="hidden" name="a" value="index">

            <div class="col-md-8">
                <label class="form-label small text-muted">Buscar</label>
                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    placeholder="Nombre de categoría..."
                    value="<?= htmlspecialchars($filtros['buscar']) ?>"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?= $filtros['activo'] === '1' ? 'selected' : '' ?>>Activas</option>
                    <option value="0" <?= $filtros['activo'] === '0' ? 'selected' : '' ?>>Inactivas</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-teal w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<?php $renderTabla('Categorías de Ingreso', 'bi-arrow-down-circle text-success', 'text-bg-success', $categoriasIngreso); ?>
<?php $renderTabla('Categorías de Egreso', 'bi-arrow-up-circle text-danger', 'text-bg-danger', $categoriasEgreso); ?>

<div class="alert alert-warning small mb-0">
    <i class="bi bi-info-circle"></i>
    Una categoría con movimientos asociados no se puede eliminar, únicamente desactivar, para preservar la integridad del historial financiero.
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
