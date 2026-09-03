<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Bitacora.php';

class EgresosController extends Controller {
    private const POR_PAGINA = 10;
    private const ROLES_GESTION = ['Administrador', 'Tesorero'];

    public function index() {
        Middleware::requirePermiso('egresos');

        $egresoModel = new Egreso();
        $categoriaModel = new Categoria();

        $filtros = [
            'desde' => $_GET['desde'] ?? '',
            'hasta' => $_GET['hasta'] ?? '',
            'id_categoria' => $_GET['categoria'] ?? '',
            'beneficiario' => trim($_GET['beneficiario'] ?? ''),
        ];

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $total = $egresoModel->contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $totalPaginas);

        $this->view('egresos.index', [
            'usuario' => Auth::user(),
            'egresos' => $egresoModel->listar($filtros, $pagina, self::POR_PAGINA),
            'categorias' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'egreso', 'activo' => '1'], 1, 100),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'totalFiltrado' => $egresoModel->totalFiltrado($filtros),
            'kpis' => $egresoModel->kpisMes(),
            'puedeGestionar' => in_array(Auth::user()['rol_nombre'], self::ROLES_GESTION, true),
            'flash' => Flash::get(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create() {
        Middleware::requireRole(self::ROLES_GESTION);

        $this->view('egresos.form', $this->datosFormulario(null, [], []));
    }

    public function store() {
        Middleware::requireRole(self::ROLES_GESTION);

        $this->guardar(null);
    }

    public function edit() {
        Middleware::requireRole(self::ROLES_GESTION);

        $id = (int) ($_GET['id'] ?? 0);
        $egresoModel = new Egreso();
        $registro = $egresoModel->buscarPorId($id);

        if (!$registro) {
            $this->redirect('index.php?c=egresos&a=index');
        }

        $this->view('egresos.form', $this->datosFormulario($registro, $registro, []));
    }

    public function update() {
        Middleware::requireRole(self::ROLES_GESTION);

        $id = (int) ($_GET['id'] ?? 0);
        $this->guardar($id);
    }

    private function guardar($id) {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=egresos&a=index');
        }

        $egresoModel = new Egreso();

        $valores = [
            'fecha_egreso' => trim($_POST['fecha_egreso'] ?? ''),
            'id_categoria' => $_POST['id_categoria'] ?? '',
            'monto' => trim($_POST['monto'] ?? ''),
            'beneficiario' => trim($_POST['beneficiario'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'id_factura' => $_POST['id_factura'] ?? '',
        ];

        $errores = $this->validar($valores);

        if (!empty($errores)) {
            $this->view('egresos.form', $this->datosFormulario($id ? $egresoModel->buscarPorId($id) : null, $valores, $errores));
            return;
        }

        $usuario = Auth::user();
        $datos = $valores;
        $datos['id_usuario_registro'] = $usuario['id_usuario'];

        $categoriaModel = new Categoria();
        $categoria = $categoriaModel->buscarPorId((int) $valores['id_categoria']);
        $categoriaNombre = $categoria['nombre'] ?? '';

        if ($id) {
            $egresoModel->actualizar($id, $datos);
            Bitacora::registrar(
                'Modificar',
                'Egresos',
                "Modificación de egreso #{$id} — {$valores['beneficiario']} — Q " . number_format((float) $valores['monto'], 2) . " — {$categoriaNombre}",
                'egresos',
                $id
            );
            Flash::set('success', 'Egreso actualizado correctamente.');
        } else {
            $idCreado = $egresoModel->crear($datos);
            Bitacora::registrar(
                'Crear',
                'Egresos',
                "Registro de egreso #{$idCreado} — {$valores['beneficiario']} — Q " . number_format((float) $valores['monto'], 2) . " — {$categoriaNombre}",
                'egresos',
                $idCreado
            );
            Flash::set('success', 'Egreso registrado correctamente.');
        }

        $this->redirect('index.php?c=egresos&a=index');
    }

    private function validar(array $valores) {
        $errores = [];

        if ($valores['fecha_egreso'] === '' || !$this->esFechaValida($valores['fecha_egreso'])) {
            $errores['fecha_egreso'] = 'Ingrese una fecha válida.';
        }

        if (empty($valores['id_categoria'])) {
            $errores['id_categoria'] = 'Seleccione una categoría.';
        }

        if ($valores['monto'] === '' || !is_numeric($valores['monto']) || (float) $valores['monto'] <= 0) {
            $errores['monto'] = 'Ingrese un monto mayor a cero.';
        }

        if ($valores['beneficiario'] === '') {
            $errores['beneficiario'] = 'Indique quién recibe el pago.';
        }

        if ($valores['descripcion'] === '') {
            $errores['descripcion'] = 'La descripción es obligatoria.';
        }

        return $errores;
    }

    private function esFechaValida($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    public function anular() {
        Middleware::requireRole(self::ROLES_GESTION);

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=egresos&a=index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $egresoModel = new Egreso();
        $egresoModel->cambiarEstado($id, 0);
        Bitacora::registrar('Anular', 'Egresos', "Anulación de egreso #{$id}", 'egresos', $id);

        Flash::set('success', 'Egreso anulado correctamente.');
        $this->redirect('index.php?c=egresos&a=index');
    }

    public function facturaRapida() {
        Middleware::requireRole(self::ROLES_GESTION);

        header('Content-Type: application/json');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo json_encode(['error' => 'Solicitud inválida.']);
            exit;
        }

        $numero = trim($_POST['numero_documento'] ?? '');
        $tipo = $_POST['tipo_documento'] ?? '';
        $beneficiario = trim($_POST['beneficiario'] ?? '');
        $fecha = trim($_POST['fecha_emision'] ?? '');
        $monto = trim($_POST['monto'] ?? '');

        if (!in_array($tipo, ['factura', 'recibo', 'nota_credito'], true)) {
            http_response_code(422);
            echo json_encode(['error' => 'Seleccione un tipo de documento válido.']);
            exit;
        }

        if (!$this->esFechaValida($fecha)) {
            http_response_code(422);
            echo json_encode(['error' => 'Ingrese una fecha de emisión válida.']);
            exit;
        }

        if ($monto === '' || !is_numeric($monto) || (float) $monto <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Ingrese un monto válido.']);
            exit;
        }

        $facturaModel = new Factura();
        $id = $facturaModel->crear([
            'numero_documento' => $numero,
            'tipo_documento' => $tipo,
            'beneficiario' => $beneficiario,
            'fecha_emision' => $fecha,
            'monto' => $monto,
            'descripcion' => null,
            'id_usuario_registro' => Auth::user()['id_usuario'],
        ]);

        $etiqueta = ($numero !== '' ? $numero : ucfirst($tipo)) . ' — Q' . number_format((float) $monto, 2);

        echo json_encode(['id' => $id, 'etiqueta' => $etiqueta]);
        exit;
    }

    private function datosFormulario($registro, array $valoresPost, array $errores) {
        $categoriaModel = new Categoria();
        $facturaModel = new Factura();

        $valores = $registro ?: [];
        if (!empty($valoresPost)) {
            $valores = array_merge($valores, $valoresPost);
        }

        return [
            'usuario' => Auth::user(),
            'registro' => $registro,
            'valores' => $valores,
            'errores' => $errores,
            'categorias' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'egreso', 'activo' => '1'], 1, 100),
            'facturas' => $facturaModel->listarActivas(),
            'csrfToken' => Csrf::token(),
        ];
    }
}
