<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../models/Bitacora.php';

class FacturasController extends Controller {
    private const POR_PAGINA = 10;
    private const ROLES_GESTION = ['Administrador', 'Tesorero'];
    private const TIPOS_VALIDOS = ['factura', 'recibo', 'nota_credito'];
    private const EXTENSIONES_PERMITIDAS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB
    private const DIR_ARCHIVOS = __DIR__ . '/../../storage/facturas';

    public function index() {
        Middleware::requirePermiso('facturas');

        $facturaModel = new Factura();

        $filtros = [
            'buscar' => trim($_GET['buscar'] ?? ''),
            'tipo_documento' => $_GET['tipo'] ?? '',
            'desde' => $_GET['desde'] ?? '',
            'hasta' => $_GET['hasta'] ?? '',
        ];

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $total = $facturaModel->contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $totalPaginas);

        $this->view('facturas.index', [
            'usuario' => Auth::user(),
            'facturas' => $facturaModel->listar($filtros, $pagina, self::POR_PAGINA),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'puedeGestionar' => in_array(Auth::user()['rol_nombre'], self::ROLES_GESTION, true),
            'flash' => Flash::get(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create() {
        Middleware::requireRole(self::ROLES_GESTION);

        $this->view('facturas.form', $this->datosFormulario(null, [], []));
    }

    public function store() {
        Middleware::requireRole(self::ROLES_GESTION);

        $this->guardar(null);
    }

    public function edit() {
        Middleware::requireRole(self::ROLES_GESTION);

        $id = (int) ($_GET['id'] ?? 0);
        $facturaModel = new Factura();
        $registro = $facturaModel->buscarPorId($id);

        if (!$registro) {
            $this->redirect('index.php?c=facturas&a=index');
        }

        $this->view('facturas.form', $this->datosFormulario($registro, $registro, []));
    }

    public function update() {
        Middleware::requireRole(self::ROLES_GESTION);

        $id = (int) ($_GET['id'] ?? 0);
        $this->guardar($id);
    }

    private function guardar($id) {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=facturas&a=index');
        }

        $facturaModel = new Factura();

        $valores = [
            'numero_documento' => trim($_POST['numero_documento'] ?? ''),
            'tipo_documento' => $_POST['tipo_documento'] ?? '',
            'fecha_emision' => trim($_POST['fecha_emision'] ?? ''),
            'beneficiario' => trim($_POST['beneficiario'] ?? ''),
            'monto' => trim($_POST['monto'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'id_egreso_vincular' => $_POST['id_egreso_vincular'] ?? '',
        ];

        $errores = $this->validar($valores, $id);

        [$rutaArchivo, $errorArchivo] = $this->procesarArchivo();
        if ($errorArchivo) {
            $errores['archivo'] = $errorArchivo;
        }

        if (!empty($errores)) {
            $this->view('facturas.form', $this->datosFormulario($id ? $facturaModel->buscarPorId($id) : null, $valores, $errores));
            return;
        }

        $datos = $valores;
        $datos['ruta_archivo'] = $rutaArchivo;

        if ($id) {
            $facturaModel->actualizar($id, $datos);
            $idFactura = $id;
            Bitacora::registrar(
                'Modificar',
                'Facturas',
                "Modificación de factura {$valores['numero_documento']} — {$valores['beneficiario']} — Q " . number_format((float) $valores['monto'], 2),
                'facturas',
                $idFactura
            );
            Flash::set('success', 'Documento actualizado correctamente.');
        } else {
            $datos['id_usuario_registro'] = Auth::user()['id_usuario'];
            $idFactura = $facturaModel->crear($datos);
            Bitacora::registrar(
                'Crear',
                'Facturas',
                "Registro de factura {$valores['numero_documento']} — {$valores['beneficiario']} — Q " . number_format((float) $valores['monto'], 2),
                'facturas',
                $idFactura
            );
            Flash::set('success', 'Documento registrado correctamente.');
        }

        if (!empty($valores['id_egreso_vincular'])) {
            $egresoModel = new Egreso();
            $egresoModel->vincularFactura((int) $valores['id_egreso_vincular'], $idFactura);
        }

        $this->redirect('index.php?c=facturas&a=index');
    }

    private function validar(array $valores, $idExcluir) {
        $errores = [];

        if ($valores['numero_documento'] === '') {
            $errores['numero_documento'] = 'Ingrese el número de documento.';
        }

        if (!in_array($valores['tipo_documento'], self::TIPOS_VALIDOS, true)) {
            $errores['tipo_documento'] = 'Seleccione un tipo de documento válido.';
        }

        if ($valores['fecha_emision'] === '' || !$this->esFechaValida($valores['fecha_emision'])) {
            $errores['fecha_emision'] = 'Ingrese una fecha de emisión válida.';
        }

        if ($valores['beneficiario'] === '') {
            $errores['beneficiario'] = 'Indique el beneficiario o emisor del documento.';
        }

        if ($valores['monto'] === '' || !is_numeric($valores['monto']) || (float) $valores['monto'] <= 0) {
            $errores['monto'] = 'Ingrese un monto mayor a cero.';
        }

        if (!$idExcluir && empty($_FILES['archivo']['name'])) {
            $errores['archivo'] = 'Adjunte el archivo del documento.';
        }

        return $errores;
    }

    private function esFechaValida($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    private function procesarArchivo() {
        if (empty($_FILES['archivo']['name'])) {
            return [null, null];
        }

        $archivo = $_FILES['archivo'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return [null, 'No se pudo subir el archivo. Intente nuevamente.'];
        }

        if ($archivo['size'] > self::TAMANO_MAXIMO) {
            return [null, 'El archivo supera el tamaño máximo permitido (5 MB).'];
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
            return [null, 'Formato no permitido. Use PDF, JPG o PNG.'];
        }

        if (!is_dir(self::DIR_ARCHIVOS)) {
            mkdir(self::DIR_ARCHIVOS, 0755, true);
        }

        $nombreAlmacenado = 'factura_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $rutaDestino = self::DIR_ARCHIVOS . '/' . $nombreAlmacenado;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return [null, 'No se pudo guardar el archivo en el servidor.'];
        }

        return [$nombreAlmacenado, null];
    }

    public function verArchivo() {
        Middleware::requirePermiso('facturas');

        $id = (int) ($_GET['id'] ?? 0);
        $facturaModel = new Factura();
        $registro = $facturaModel->buscarPorId($id);

        if (!$registro || !$registro['ruta_archivo']) {
            $this->redirect('index.php?c=facturas&a=index');
        }

        $ruta = self::DIR_ARCHIVOS . '/' . $registro['ruta_archivo'];

        if (!is_file($ruta)) {
            $this->redirect('index.php?c=facturas&a=index');
        }

        header('Content-Type: ' . mime_content_type($ruta));
        header('Content-Disposition: inline; filename="' . basename($registro['ruta_archivo']) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    public function anular() {
        Middleware::requireRole(self::ROLES_GESTION);

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=facturas&a=index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $facturaModel = new Factura();
        $facturaModel->cambiarEstado($id, 0);
        Bitacora::registrar('Anular', 'Facturas', "Anulación de documento #{$id}", 'facturas', $id);

        Flash::set('success', 'Documento anulado correctamente.');
        $this->redirect('index.php?c=facturas&a=index');
    }

    private function datosFormulario($registro, array $valoresPost, array $errores) {
        $egresoModel = new Egreso();

        $valores = $registro ?: [];
        if (!empty($valoresPost)) {
            $valores = array_merge($valores, $valoresPost);
        }

        return [
            'usuario' => Auth::user(),
            'registro' => $registro,
            'valores' => $valores,
            'errores' => $errores,
            'egresos' => $egresoModel->listarParaVincular(),
            'csrfToken' => Csrf::token(),
        ];
    }
}
