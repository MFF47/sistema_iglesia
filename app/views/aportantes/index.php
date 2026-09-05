<?php
$tituloPagina = 'Aportantes';
$activeMenu = 'aportantes';
require __DIR__ . '/../layout/header.php';

$construirQuery = function (array $filtros, $pagina) {
    return http_build_query([
        'c' => 'aportantes',
        'a' => 'index',
        'buscar' => $filtros['buscar'],
        'estado' => $filtros['activo'],
        'pagina' => $pagina,
    ]);
};
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h4 mb-1">Aportantes</h1>
    <a href="index.php?c=aportantes&a=create" class="btn btn-warning text-dark fw-semibold">
        <i class="bi bi-plus-lg"></i> Nuevo Aportante
    </a>
</div>
<p class="text-muted mb-4">Personas identificadas en los ingresos registrados (diezmos, donaciones, etc.)</p>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipo'] === 'success' ? 'success' : 'danger') ?>">
        <?= htmlspecialchars($flash['mensaje']) ?>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="aportantes">
            <input type="hidden" name="a" value="index">

            <div class="col-md-8">
                <label class="form-label small text-muted">Buscar</label>
                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    placeholder="Nombre, documento o correo..."
                    value="<?= htmlspecialchars($filtros['buscar']) ?>"
                >
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?= $filtros['activo'] === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $filtros['activo'] === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-teal w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre Completo</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Ingresos</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($aportantes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se encontraron aportantes.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($aportantes as $fila): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($fila['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($fila['documento_identidad'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($fila['telefono'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($fila['correo'] ?? '—') ?></td>
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
                                href="index.php?c=aportantes&a=edit&id=<?= $fila['id_aportante'] ?>"
                                class="btn btn-sm btn-outline-secondary"
                                title="Editar"
                            >
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form
                                method="post"
                                action="index.php?c=aportantes&a=toggleEstado"
                                class="d-inline"
                                onsubmit="return confirm('<?= $fila['activo'] ? 'Desactivar' : 'Activar' ?> a <?= htmlspecialchars(addslashes($fila['nombre_completo'])) ?>?');"
                            >
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $fila['id_aportante'] ?>">
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

    <div class="card-body d-flex justify-content-between align-items-center border-top flex-wrap gap-2">
        <span class="text-muted small">Mostrando <?= count($aportantes) ?> de <?= $total ?> aportantes</span>

        <?php if ($totalPaginas > 1): ?>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?<?= $construirQuery($filtros, $p) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
