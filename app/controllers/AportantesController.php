<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../models/Aportante.php';
require_once __DIR__ . '/../models/Bitacora.php';

class AportantesController extends Controller {
    private const POR_PAGINA = 10;

    public function index() {
        Middleware::requirePermiso('aportantes');

        $aportanteModel = new Aportante();

        $filtros = [
            'buscar' => trim($_GET['buscar'] ?? ''),
            'activo' => $_GET['estado'] ?? '',
        ];

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $total = $aportanteModel->contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $totalPaginas);

        $this->view('aportantes.index', [
            'usuario' => Auth::user(),
            'aportantes' => $aportanteModel->listar($filtros, $pagina, self::POR_PAGINA),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'flash' => Flash::get(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create() {
        Middleware::requirePermiso('aportantes');

        $this->view('aportantes.form', [
            'usuario' => Auth::user(),
            'registro' => null,
            'errores' => [],
            'valores' => [],
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function store() {
        Middleware::requirePermiso('aportantes');

        $this->guardar(null);
    }

    public function edit() {
        Middleware::requirePermiso('aportantes');

        $id = (int) ($_GET['id'] ?? 0);
        $aportanteModel = new Aportante();
        $registro = $aportanteModel->buscarPorId($id);

        if (!$registro) {
            $this->redirect('index.php?c=aportantes&a=index');
        }

        $this->view('aportantes.form', [
            'usuario' => Auth::user(),
            'registro' => $registro,
            'errores' => [],
            'valores' => $registro,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function update() {
        Middleware::requirePermiso('aportantes');

        $id = (int) ($_GET['id'] ?? 0);
        $this->guardar($id);
    }

    private function guardar($id) {
        Middleware::requirePermiso('aportantes');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=aportantes&a=index');
        }

        $aportanteModel = new Aportante();

        $valores = [
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'documento_identidad' => trim($_POST['documento_identidad'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
        ];

        $errores = $this->validar($valores);

        if (!empty($errores)) {
            $registro = $id ? $aportanteModel->buscarPorId($id) : null;

            $this->view('aportantes.form', [
                'usuario' => Auth::user(),
                'registro' => $registro,
                'errores' => $errores,
                'valores' => $valores,
                'csrfToken' => Csrf::token(),
            ]);
            return;
        }

        if ($id) {
            $aportanteModel->actualizar($id, $valores);
            Bitacora::registrar('Modificar', 'Aportantes', "Modificación del aportante '{$valores['nombre_completo']}'", 'aportantes', $id);
            Flash::set('success', 'Aportante actualizado correctamente.');
        } else {
            $idCreado = $aportanteModel->crear($valores);
            Bitacora::registrar('Crear', 'Aportantes', "Creación del aportante '{$valores['nombre_completo']}'", 'aportantes', $idCreado);
            Flash::set('success', 'Aportante creado correctamente.');
        }

        $this->redirect('index.php?c=aportantes&a=index');
    }

    private function validar(array $valores) {
        $errores = [];

        if ($valores['nombre_completo'] === '' || strlen($valores['nombre_completo']) < 3) {
            $errores['nombre_completo'] = 'El nombre completo debe tener al menos 3 caracteres.';
        }

        if ($valores['correo'] !== '' && !filter_var($valores['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Ingrese un correo electrónico válido.';
        }

        return $errores;
    }

    public function toggleEstado() {
        Middleware::requirePermiso('aportantes');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=aportantes&a=index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $aportanteModel = new Aportante();
        $registro = $aportanteModel->buscarPorId($id);

        if ($registro) {
            $nuevoEstado = $registro['activo'] ? 'Inactivo' : 'Activo';
            $aportanteModel->cambiarEstado($id, $registro['activo'] ? 0 : 1);
            Bitacora::registrar(
                'Modificar',
                'Aportantes',
                "Modificación del aportante '{$registro['nombre_completo']}' — Cambio de estado a {$nuevoEstado}",
                'aportantes',
                $id
            );
            Flash::set('success', $registro['activo'] ? 'Aportante desactivado.' : 'Aportante activado.');
        }

        $this->redirect('index.php?c=aportantes&a=index');
    }
}
