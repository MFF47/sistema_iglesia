<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Bitacora.php';

class CategoriasController extends Controller {
    private const TIPOS_VALIDOS = ['ingreso', 'egreso'];

    public function index() {
        Middleware::requirePermiso('categorias');

        $categoriaModel = new Categoria();

        $filtros = [
            'buscar' => trim($_GET['buscar'] ?? ''),
            'activo' => $_GET['estado'] ?? '',
        ];

        $categoriasIngreso = $categoriaModel->listar(array_merge($filtros, ['tipo' => 'ingreso']), 1, 1000);
        $categoriasEgreso = $categoriaModel->listar(array_merge($filtros, ['tipo' => 'egreso']), 1, 1000);

        $this->view('categorias.index', [
            'usuario' => Auth::user(),
            'categoriasIngreso' => $categoriasIngreso,
            'categoriasEgreso' => $categoriasEgreso,
            'filtros' => $filtros,
            'flash' => Flash::get(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create() {
        Middleware::requirePermiso('categorias');

        $this->view('categorias.form', [
            'usuario' => Auth::user(),
            'registro' => null,
            'errores' => [],
            'valores' => [],
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function store() {
        Middleware::requirePermiso('categorias');

        $this->guardar(null);
    }

    public function edit() {
        Middleware::requirePermiso('categorias');

        $id = (int) ($_GET['id'] ?? 0);
        $categoriaModel = new Categoria();
        $registro = $categoriaModel->buscarPorId($id);

        if (!$registro) {
            $this->redirect('index.php?c=categorias&a=index');
        }

        $this->view('categorias.form', [
            'usuario' => Auth::user(),
            'registro' => $registro,
            'errores' => [],
            'valores' => $registro,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function update() {
        Middleware::requirePermiso('categorias');

        $id = (int) ($_GET['id'] ?? 0);
        $this->guardar($id);
    }

    private function guardar($id) {
        Middleware::requirePermiso('categorias');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=categorias&a=index');
        }

        $categoriaModel = new Categoria();

        $valores = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'tipo' => $_POST['tipo'] ?? '',
            'activo' => ($_POST['activo'] ?? '1') === '1' ? 1 : 0,
        ];

        $errores = $this->validar($categoriaModel, $valores, $id);

        if (!empty($errores)) {
            $registro = $id ? $categoriaModel->buscarPorId($id) : null;

            $this->view('categorias.form', [
                'usuario' => Auth::user(),
                'registro' => $registro,
                'errores' => $errores,
                'valores' => $valores,
                'csrfToken' => Csrf::token(),
            ]);
            return;
        }

        if ($id) {
            $categoriaModel->actualizar($id, $valores);
            Bitacora::registrar('Modificar', 'Categorías', "Modificación de categoría '{$valores['nombre']}' — Tipo " . ucfirst($valores['tipo']), 'categorias', $id);
            Flash::set('success', 'Categoría actualizada correctamente.');
        } else {
            $idCreado = $categoriaModel->crear($valores);
            Bitacora::registrar('Crear', 'Categorías', "Creación de categoría '{$valores['nombre']}' — Tipo " . ucfirst($valores['tipo']), 'categorias', $idCreado);
            Flash::set('success', 'Categoría creada correctamente.');
        }

        $this->redirect('index.php?c=categorias&a=index');
    }

    private function validar(Categoria $categoriaModel, array $valores, $idExcluir) {
        $errores = [];

        if ($valores['nombre'] === '' || strlen($valores['nombre']) < 3) {
            $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
        }

        if (!in_array($valores['tipo'], self::TIPOS_VALIDOS, true)) {
            $errores['tipo'] = 'Seleccione un tipo válido.';
        }

        if (!isset($errores['nombre']) && !isset($errores['tipo'])
            && $categoriaModel->existeNombre($valores['nombre'], $valores['tipo'], $idExcluir)) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre y tipo.';
        }

        return $errores;
    }

    public function toggleEstado() {
        Middleware::requirePermiso('categorias');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=categorias&a=index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $categoriaModel = new Categoria();
        $registro = $categoriaModel->buscarPorId($id);

        if ($registro) {
            $nuevoEstado = $registro['activo'] ? 'Inactiva' : 'Activa';
            $categoriaModel->cambiarEstado($id, $registro['activo'] ? 0 : 1);
            Bitacora::registrar(
                'Modificar',
                'Categorías',
                "Modificación de categoría '{$registro['nombre']}' — Cambio de estado a {$nuevoEstado}",
                'categorias',
                $id
            );
            Flash::set('success', $registro['activo'] ? 'Categoría desactivada.' : 'Categoría activada.');
        }

        $this->redirect('index.php?c=categorias&a=index');
    }
}
