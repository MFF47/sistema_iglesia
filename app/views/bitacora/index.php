<?php
$tituloPagina = 'Bitácora';
$activeMenu = 'bitacora';
require __DIR__ . '/../layout/header.php';

$claseBadgeAccion = function ($accion) {
    return match ($accion) {
        'Crear' => 'text-bg-success',
        'Modificar' => 'text-bg-warning',
        'Anular' => 'text-bg-danger',
        'Login' => 'text-bg-primary',
        'Login fallido' => 'text-bg-danger',
        'Exportar' => 'text-bg-info',
        'Consultar' => 'text-bg-secondary',
        default => 'text-bg-secondary',
    };
};

// Las descripciones se guardan como segmentos unidos por " — " (ej. "Diezmo — Q 1,200.00 — Hno. Pedro Castillo").
// En vez de mostrarlas como una sola oración con guiones, se separan visualmente en líneas independientes.
$renderDescripcion = function ($descripcion) {
    $segmentos = array_filter(array_map('trim', explode('—', (string) $descripcion)));

    if (count($segmentos) <= 1) {
        return htmlspecialchars($descripcion);
    }

    $html = '<div class="descripcion-bitacora">';
    foreach ($segmentos as $segmento) {
        $html .= '<div>' . htmlspecialchars($segmento) . '</div>';
    }
    $html .= '</div>';

    return $html;
};

$construirQuery = function (array $filtros, $pagina) {
    return http_build_query([
        'c' => 'bitacora',
        'a' => 'index',
        'desde' => $filtros['desde'],
        'hasta' => $filtros['hasta'],
        'usuario' => $filtros['nombre_usuario'],
        'modulo' => $filtros['modulo'],
        'accion' => $filtros['accion'],
        'pagina' => $pagina,
    ]);
};

$queryExport = http_build_query([
    'c' => 'bitacora',
    'desde' => $filtros['desde'],
    'hasta' => $filtros['hasta'],
    'usuario' => $filtros['nombre_usuario'],
    'modulo' => $filtros['modulo'],
    'accion' => $filtros['accion'],
]);
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h4 mb-1">Bitácora de Auditoría</h1>
    <a href="index.php?a=exportarPdf&<?= $queryExport ?>" class="btn btn-warning text-dark fw-semibold">
        <i class="bi bi-file-earmark-pdf"></i> Exportar Bitácora
    </a>
</div>
<p class="text-muted mb-3">Registro histórico de todas las acciones realizadas en el sistema</p>

<div class="alert alert-warning small">
    <i class="bi bi-shield-lock"></i>
    <strong>Solo lectura:</strong> los registros de bitácora no pueden modificarse ni eliminarse, para garantizar la trazabilidad y la integridad de la auditoría.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="bitacora">
            <input type="hidden" name="a" value="index">

            <div class="col-md-2">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Usuario</label>
                <select name="usuario" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>" <?= $filtros['nombre_usuario'] === $u ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Módulo</label>
                <select name="modulo" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($modulos as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= $filtros['modulo'] === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Acción</label>
                <select name="accion" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($acciones as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>" <?= $filtros['accion'] === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-teal w-100"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Usuario</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventos)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No hay eventos registrados.</td></tr>
                <?php endif; ?>

                <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td class="text-nowrap"><?= htmlspecialchars($evento['fecha_evento']) ?></td>
                        <td><?= htmlspecialchars($evento['nombre_usuario']) ?></td>
                        <td><?= htmlspecialchars($evento['modulo']) ?></td>
                        <td><span class="badge <?= $claseBadgeAccion($evento['accion']) ?>"><?= htmlspecialchars($evento['accion']) ?></span></td>
                        <td class="text-muted small"><?= $renderDescripcion($evento['descripcion']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-body d-flex justify-content-between align-items-center border-top flex-wrap gap-2">
        <span class="text-muted small">Mostrando <?= count($eventos) ?> de <?= $total ?> eventos</span>

        <?php if ($totalPaginas > 1): ?>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $inicio = max(1, $pagina - 2);
                    $fin = min($totalPaginas, $pagina + 2);
                    ?>
                    <?php if ($inicio > 1): ?>
                        <li class="page-item"><a class="page-link" href="index.php?<?= $construirQuery($filtros, 1) ?>">1</a></li>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>

                    <?php for ($p = $inicio; $p <= $fin; $p++): ?>
                        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?<?= $construirQuery($filtros, $p) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($fin < $totalPaginas): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                        <li class="page-item"><a class="page-link" href="index.php?<?= $construirQuery($filtros, $totalPaginas) ?>"><?= $totalPaginas ?></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
