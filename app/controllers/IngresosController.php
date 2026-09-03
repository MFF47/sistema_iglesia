<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/TipoIngreso.php';
require_once __DIR__ . '/../models/Actividad.php';
require_once __DIR__ . '/../models/Aportante.php';
require_once __DIR__ . '/../models/Adjunto.php';
require_once __DIR__ . '/../models/Bitacora.php';

class IngresosController extends Controller {
    private const POR_PAGINA = 10;
    private const ROLES_GESTION = ['Administrador', 'Tesorero'];
    private const EXTENSIONES_PERMITIDAS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const TAMANO_MAXIMO = 3 * 1024 * 1024; // 3 MB
    private const DIR_ADJUNTOS = __DIR__ . '/../../storage/adjuntos';

    public function index() {
        Middleware::requirePermiso('ingresos');

        $ingresoModel = new Ingreso();
        $categoriaModel = new Categoria();
        $tipoModel = new TipoIngreso();

        $filtros = [
            'desde' => $_GET['desde'] ?? '',
            'hasta' => $_GET['hasta'] ?? '',
            'id_categoria' => $_GET['categoria'] ?? '',
            'id_tipo_ingreso' => $_GET['tipo'] ?? '',
        ];

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $total = $ingresoModel->contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $totalPaginas);

        $this->view('ingresos.index', [
            'usuario' => Auth::user(),
            'ingresos' => $ingresoModel->listar($filtros, $pagina, self::POR_PAGINA),
            'categorias' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'ingreso', 'activo' => '1'], 1, 100),
            'tipos' => $tipoModel->listarActivos(),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'totalFiltrado' => $ingresoModel->totalFiltrado($filtros),
            'kpis' => $ingresoModel->kpisMes(),
            'puedeGestionar' => in_array(Auth::user()['rol_nombre'], self::ROLES_GESTION, true),
            'flash' => Flash::get(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create() {
        Middleware::requireRole(self::ROLES_GESTION);

        $this->view('ingresos.form', $this->datosFormulario(null, [], []));
    }

    public function store() {
        Middleware::requireRole(self::ROLES_GESTION);

        $this->guardar(null);
    }

    public function edit() {
        Middleware::requireRole(self::ROLES_GESTION);

        $id = (int) ($_GET['id'] ?? 0);
        $ingresoModel = new Ingreso();
        $registro = $ingresoModel->buscarPorId($id);

        if (!$registro) {
            $this->redirect('index.php?c=ingresos&a=index');
        }

        $this->view('ingresos.form', $this->datosFormulario($registro, $registro, []));
    }

    public function update() {
        Middleware::requireRole(self::ROLES_GESTION);

        $id = (int) ($_GET['id'] ?? 0);
        $this->guardar($id);
    }

    private function guardar($id) {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=ingresos&a=index');
        }

        $ingresoModel = new Ingreso();
        $tipoModel = new TipoIngreso();

        $valores = [
            'fecha_ingreso' => trim($_POST['fecha_ingreso'] ?? ''),
            'id_tipo_ingreso' => $_POST['id_tipo_ingreso'] ?? '',
            'id_categoria' => $_POST['id_categoria'] ?? '',
            'monto' => trim($_POST['monto'] ?? ''),
            'id_actividad' => $_POST['id_actividad'] ?? '',
            'id_aportante' => $_POST['id_aportante'] ?? '',
            'descripcion' => trim($_POST['descripcion'] ?? ''),
        ];

        $errores = $this->validar($valores);

        if (!empty($errores)) {
            $this->view('ingresos.form', $this->datosFormulario($id ? $ingresoModel->buscarPorId($id) : null, $valores, $errores));
            return;
        }

        $usuario = Auth::user();
        $datos = $valores;
        $datos['id_usuario_registro'] = $usuario['id_usuario'];

        $tipoModel = new TipoIngreso();
        $tipo = $tipoModel->buscarPorId((int) $valores['id_tipo_ingreso']);
        $aportanteNombre = null;
        if (!empty($valores['id_aportante'])) {
            $aportanteModel = new Aportante();
            foreach ($aportanteModel->listarActivos() as $aportante) {
                if ((int) $aportante['id_aportante'] === (int) $valores['id_aportante']) {
                    $aportanteNombre = $aportante['nombre_completo'];
                    break;
                }
            }
        }

        if ($id) {
            $ingresoModel->actualizar($id, $datos);
            $idIngreso = $id;
            Bitacora::registrar(
                'Modificar',
                'Ingresos',
                "Modificación de ingreso #{$idIngreso} — {$tipo['nombre']} — Q " . number_format((float) $valores['monto'], 2)
                    . ($aportanteNombre ? " — {$aportanteNombre}" : ''),
                'ingresos',
                $idIngreso
            );
            Flash::set('success', 'Ingreso actualizado correctamente.');
        } else {
            $idIngreso = $ingresoModel->crear($datos);
            Bitacora::registrar(
                'Crear',
                'Ingresos',
                "Registro de ingreso #{$idIngreso} — {$tipo['nombre']} — Q " . number_format((float) $valores['monto'], 2)
                    . ($aportanteNombre ? " — {$aportanteNombre}" : ''),
                'ingresos',
                $idIngreso
            );
            Flash::set('success', 'Ingreso registrado correctamente.');
        }

        $errorArchivo = $this->procesarComprobante($idIngreso, $usuario['id_usuario']);
        if ($errorArchivo) {
            Flash::set('danger', $errorArchivo);
        }

        $this->redirect('index.php?c=ingresos&a=index');
    }

    private function validar(array $valores) {
        $errores = [];

        if ($valores['fecha_ingreso'] === '' || !$this->esFechaValida($valores['fecha_ingreso'])) {
            $errores['fecha_ingreso'] = 'Ingrese una fecha válida.';
        }

        $tipoModel = new TipoIngreso();
        $tipo = $valores['id_tipo_ingreso'] !== '' ? $tipoModel->buscarPorId((int) $valores['id_tipo_ingreso']) : null;

        if (!$tipo) {
            $errores['id_tipo_ingreso'] = 'Seleccione un tipo de ingreso válido.';
        }

        if (empty($valores['id_categoria'])) {
            $errores['id_categoria'] = 'Seleccione una categoría.';
        }

        if ($valores['monto'] === '' || !is_numeric($valores['monto']) || (float) $valores['monto'] <= 0) {
            $errores['monto'] = 'Ingrese un monto mayor a cero.';
        }

        if ($tipo && (int) $tipo['requiere_aportante'] === 1 && empty($valores['id_aportante'])) {
            $errores['id_aportante'] = 'Este tipo de ingreso requiere identificar al aportante.';
        }

        return $errores;
    }

    private function esFechaValida($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    private function procesarComprobante($idIngreso, $idUsuario) {
        if (empty($_FILES['comprobante']['name'])) {
            return null;
        }

        $archivo = $_FILES['comprobante'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return 'No se pudo subir el comprobante. Intente nuevamente.';
        }

        if ($archivo['size'] > self::TAMANO_MAXIMO) {
            return 'El comprobante supera el tamaño máximo permitido (3 MB).';
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
            return 'Formato de comprobante no permitido. Use PDF, JPG o PNG.';
        }

        if (!is_dir(self::DIR_ADJUNTOS)) {
            mkdir(self::DIR_ADJUNTOS, 0755, true);
        }

        $nombreAlmacenado = 'ingreso_' . $idIngreso . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $rutaDestino = self::DIR_ADJUNTOS . '/' . $nombreAlmacenado;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return 'No se pudo guardar el comprobante en el servidor.';
        }

        $adjuntoModel = new Adjunto();
        $adjuntoModel->crear([
            'tabla_origen' => 'ingresos',
            'id_origen' => $idIngreso,
            'nombre_original' => basename($archivo['name']),
            'nombre_almacenado' => $nombreAlmacenado,
            'ruta' => $nombreAlmacenado,
            'tipo_mime' => mime_content_type($rutaDestino),
            'tamano_bytes' => $archivo['size'],
            'id_usuario_registro' => $idUsuario,
        ]);

        return null;
    }

    public function verComprobante() {
        Middleware::requirePermiso('ingresos');

        $idIngreso = (int) ($_GET['id'] ?? 0);
        $adjuntoModel = new Adjunto();
        $adjunto = $adjuntoModel->buscarPorOrigen('ingresos', $idIngreso);

        if (!$adjunto) {
            $this->redirect('index.php?c=ingresos&a=index');
        }

        $ruta = self::DIR_ADJUNTOS . '/' . $adjunto['nombre_almacenado'];

        if (!is_file($ruta)) {
            $this->redirect('index.php?c=ingresos&a=index');
        }

        header('Content-Type: ' . $adjunto['tipo_mime']);
        header('Content-Disposition: inline; filename="' . basename($adjunto['nombre_original']) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    public function anular() {
        Middleware::requireRole(self::ROLES_GESTION);

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=ingresos&a=index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $ingresoModel = new Ingreso();
        $ingresoModel->cambiarEstado($id, 0);
        Bitacora::registrar('Anular', 'Ingresos', "Anulación de ingreso #{$id}", 'ingresos', $id);

        Flash::set('success', 'Ingreso anulado correctamente.');
        $this->redirect('index.php?c=ingresos&a=index');
    }

    public function aportanteRapido() {
        Middleware::requireRole(self::ROLES_GESTION);

        header('Content-Type: application/json');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo json_encode(['error' => 'Solicitud inválida.']);
            exit;
        }

        $nombre = trim($_POST['nombre_completo'] ?? '');

        if ($nombre === '' || strlen($nombre) < 3) {
            http_response_code(422);
            echo json_encode(['error' => 'Ingrese un nombre válido.']);
            exit;
        }

        $aportanteModel = new Aportante();
        $id = $aportanteModel->crear([
            'nombre_completo' => $nombre,
            'documento_identidad' => trim($_POST['documento_identidad'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
        ]);

        echo json_encode(['id' => $id, 'nombre_completo' => $nombre]);
        exit;
    }

    private function datosFormulario($registro, array $valoresPost, array $errores) {
        $categoriaModel = new Categoria();
        $tipoModel = new TipoIngreso();
        $actividadModel = new Actividad();
        $aportanteModel = new Aportante();

        $valores = $registro ?: [];
        if (!empty($valoresPost)) {
            $valores = array_merge($valores, $valoresPost);
        }

        return [
            'usuario' => Auth::user(),
            'registro' => $registro,
            'valores' => $valores,
            'errores' => $errores,
            'categorias' => $categoriaModel->listar(['buscar' => '', 'tipo' => 'ingreso', 'activo' => '1'], 1, 100),
            'tipos' => $tipoModel->listarActivos(),
            'actividades' => $actividadModel->listarActivas(),
            'aportantes' => $aportanteModel->listarActivos(),
            'csrfToken' => Csrf::token(),
        ];
    }
}
