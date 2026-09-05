<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Bitacora.php';
require_once __DIR__ . '/../models/Reporte.php';

class EgresosController extends Controller {
    private const POR_PAGINA = 10;
    private const ROLES_GESTION = ['Administrador', 'Tesorero'];
    private const EXTENSIONES_PERMITIDAS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB
    private const DIR_FACTURAS = __DIR__ . '/../../storage/facturas';

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

        $reporteModel = new Reporte();

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
            'mesActualNombre' => Reporte::nombreMesLargo((int) date('n')) . ' ' . date('Y'),
            'disponibleCajaGeneral' => $reporteModel->disponibleCajaGeneral(),
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
        $registroAnterior = $id ? $egresoModel->buscarPorId($id) : null;

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
            $this->view('egresos.form', $this->datosFormulario($registroAnterior, $valores, $errores));
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
            $id = $egresoModel->crear($datos);
            Bitacora::registrar(
                'Crear',
                'Egresos',
                "Registro de egreso #{$id} — {$valores['beneficiario']} — Q " . number_format((float) $valores['monto'], 2) . " — {$categoriaNombre}",
                'egresos',
                $id
            );
            Flash::set('success', 'Egreso registrado correctamente.');
        }

        [$idFacturaCreada, $errorArchivo] = $this->procesarFacturaAutomatica($id, $valores, $usuario['id_usuario']);

        $idFacturaAnterior = $registroAnterior['id_factura'] ?? null;

        if ($idFacturaCreada) {
            // Se subió un archivo nuevo: siempre gana sobre cualquier selección manual.
            $egresoModel->vincularFactura($id, $idFacturaCreada);
        } elseif (!empty($valores['id_factura']) && (int) $valores['id_factura'] !== (int) $idFacturaAnterior) {
            // El usuario eligió manualmente una factura distinta en el select.
            $egresoModel->vincularFactura($id, (int) $valores['id_factura']);
        } elseif ($idFacturaAnterior && empty($valores['id_factura'])) {
            // No se subió archivo nuevo ni se cambió la factura vinculada manualmente:
            // se propaga el cambio de monto/fecha/beneficiario hacia el documento ya vinculado.
            $facturaModel = new Factura();
            $facturaModel->actualizarCamposClave(
                $registroAnterior['id_factura'],
                $valores['monto'],
                $valores['fecha_egreso'],
                $valores['beneficiario']
            );
            Bitacora::registrar(
                'Modificar',
                'Facturas',
                "Actualización en cascada del documento {$registroAnterior['factura_numero']} por edición del egreso #{$id}",
                'facturas',
                $registroAnterior['id_factura']
            );
        }

        if ($errorArchivo) {
            Flash::set('danger', $errorArchivo);
        }

        $this->redirect('index.php?c=egresos&a=index');
    }

    /**
     * Si el usuario adjuntó un archivo (foto o PDF) al registrar/editar el egreso,
     * lo guarda y crea automáticamente el documento correspondiente en Facturas y Recibos.
     */
    private function procesarFacturaAutomatica($idEgreso, array $valores, $idUsuario) {
        if (empty($_FILES['comprobante']['name'])) {
            return [null, null];
        }

        $archivo = $_FILES['comprobante'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return [null, 'No se pudo subir el comprobante. Intente nuevamente.'];
        }

        if ($archivo['size'] > self::TAMANO_MAXIMO) {
            return [null, 'El comprobante supera el tamaño máximo permitido (5 MB).'];
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
            return [null, 'Formato de comprobante no permitido. Use PDF, JPG o PNG.'];
        }

        if (!is_dir(self::DIR_FACTURAS)) {
            mkdir(self::DIR_FACTURAS, 0755, true);
        }

        $nombreAlmacenado = 'factura_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $rutaDestino = self::DIR_FACTURAS . '/' . $nombreAlmacenado;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return [null, 'No se pudo guardar el comprobante en el servidor.'];
        }

        $facturaModel = new Factura();
        $idFactura = $facturaModel->crear([
            'numero_documento' => 'EGR-' . $idEgreso,
            'tipo_documento' => 'recibo',
            'beneficiario' => $valores['beneficiario'],
            'fecha_emision' => $valores['fecha_egreso'],
            'monto' => $valores['monto'],
            'descripcion' => "Comprobante generado automáticamente desde el egreso #{$idEgreso}",
            'ruta_archivo' => $nombreAlmacenado,
            'id_usuario_registro' => $idUsuario,
        ]);

        Bitacora::registrar(
            'Crear',
            'Facturas',
            "Registro automático de documento (EGR-{$idEgreso}) generado desde Egresos",
            'facturas',
            $idFactura
        );

        return [$idFactura, null];
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
        $registro = $egresoModel->buscarPorId($id);

        $egresoModel->cambiarEstado($id, 0);
        Bitacora::registrar('Anular', 'Egresos', "Anulación de egreso #{$id}", 'egresos', $id);

        if ($registro && $registro['id_factura']) {
            $facturaModel = new Factura();
            $facturaModel->cambiarEstado($registro['id_factura'], 0);
            Bitacora::registrar(
                'Anular',
                'Facturas',
                "Anulación en cascada del documento {$registro['factura_numero']} por anulación del egreso #{$id}",
                'facturas',
                $registro['id_factura']
            );
        }

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
        $reporteModel = new Reporte();

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
            'disponibleCajaGeneral' => $reporteModel->disponibleCajaGeneral(),
            'csrfToken' => Csrf::token(),
        ];
    }
}
