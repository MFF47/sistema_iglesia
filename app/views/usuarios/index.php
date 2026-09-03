<?php
$tituloPagina = 'Usuarios y Roles';
$activeMenu = 'usuarios';
require __DIR__ . '/../layout/header.php';

$claseBadgeRol = function ($rolNombre) {
    return match ($rolNombre) {
        'Administrador' => 'text-bg-primary',
        'Tesorero' => 'text-bg-warning',
        'Supervisor' => 'text-bg-info',
        default => 'text-bg-secondary',
    };
};

$construirQuery = function (array $filtros, $pagina) {
    return http_build_query([
        'c' => 'usuarios',
        'a' => 'index',
        'buscar' => $filtros['buscar'],
        'rol' => $filtros['id_rol'],
        'estado' => $filtros['activo'],
        'pagina' => $pagina,
    ]);
};
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h4 mb-1">Usuarios y Roles</h1>
    <a href="index.php?c=usuarios&a=create" class="btn btn-warning text-dark fw-semibold">
        <i class="bi bi-plus-lg"></i> Nuevo Usuario
    </a>
</div>
<p class="text-muted mb-4">Gestione los usuarios que tienen acceso al sistema</p>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipo'] === 'success' ? 'success' : 'danger') ?>">
        <?= htmlspecialchars($flash['mensaje']) ?>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="usuarios">
            <input type="hidden" name="a" value="index">

            <div class="col-md-5">
                <label class="form-label small text-muted">Buscar</label>
                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    placeholder="Nombre, usuario o correo..."
                    value="<?= htmlspecialchars($filtros['buscar']) ?>"
                >
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted">Rol</label>
                <select name="rol" class="form-select">
                    <option value="">Todos los roles</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['id_rol'] ?>" <?= (string) $filtros['id_rol'] === (string) $rol['id_rol'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rol['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?= $filtros['activo'] === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $filtros['activo'] === '0' ? 'selected' : '' ?>>Inactivo</option>
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
                    <th>Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Último Acceso</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se encontraron usuarios.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($usuarios as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['nombre_usuario']) ?></td>
                        <td><?= htmlspecialchars($fila['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($fila['correo']) ?></td>
                        <td>
                            <span class="badge <?= $claseBadgeRol($fila['rol_nombre']) ?>">
                                <?= htmlspecialchars($fila['rol_nombre']) ?>
                            </span>
                        </td>
                        <td><?= $fila['ultimo_acceso'] ? htmlspecialchars($fila['ultimo_acceso']) : '—' ?></td>
                        <td>
                            <?php if ($fila['activo']): ?>
                                <span class="badge text-bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a
                                href="index.php?c=usuarios&a=edit&id=<?= $fila['id_usuario'] ?>"
                                class="btn btn-sm btn-outline-secondary"
                                title="Editar"
                            >
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form
                                method="post"
                                action="index.php?c=usuarios&a=toggleEstado"
                                class="d-inline"
                                onsubmit="return confirm('<?= $fila['activo'] ? 'Desactivar' : 'Activar' ?> a <?= htmlspecialchars(addslashes($fila['nombre_usuario'])) ?>?');"
                            >
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $fila['id_usuario'] ?>">
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

    <div class="card-body d-flex justify-content-between align-items-center border-top">
        <span class="text-muted small">Mostrando <?= count($usuarios) ?> de <?= $total ?> registros</span>

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
