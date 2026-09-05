<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Rol.php';
require_once __DIR__ . '/../models/Bitacora.php';
require_once __DIR__ . '/../../core/PasswordPolicy.php';

class UsuariosController extends Controller {
    private const POR_PAGINA = 5;

    public function index() {
        Middleware::requirePermiso('usuarios');

        $usuarioModel = new Usuario();
        $rolModel = new Rol();

        $filtros = [
            'buscar' => trim($_GET['buscar'] ?? ''),
            'id_rol' => $_GET['rol'] ?? '',
            'activo' => $_GET['estado'] ?? '',
        ];

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $total = $usuarioModel->contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $totalPaginas);

        $this->view('usuarios.index', [
            'usuario' => Auth::user(),
            'usuarios' => $usuarioModel->listar($filtros, $pagina, self::POR_PAGINA),
            'roles' => $rolModel->listarActivos(),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'flash' => Flash::get(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create() {
        Middleware::requirePermiso('usuarios');

        $rolModel = new Rol();

        $this->view('usuarios.form', [
            'usuario' => Auth::user(),
            'roles' => $rolModel->listarActivos(),
            'registro' => null,
            'errores' => [],
            'valores' => [],
            'csrfToken' => Csrf::token(),
            'esUsuarioActual' => false,
        ]);
    }

    public function store() {
        Middleware::requirePermiso('usuarios');

        $this->guardar(null);
    }

    public function edit() {
        Middleware::requirePermiso('usuarios');

        $id = (int) ($_GET['id'] ?? 0);
        $usuarioModel = new Usuario();
        $registro = $usuarioModel->buscarPorId($id);

        if (!$registro) {
            $this->redirect('index.php?c=usuarios&a=index');
        }

        $rolModel = new Rol();

        $this->view('usuarios.form', [
            'usuario' => Auth::user(),
            'roles' => $rolModel->listarActivos(),
            'registro' => $registro,
            'errores' => [],
            'valores' => $registro,
            'csrfToken' => Csrf::token(),
            'esUsuarioActual' => (int) Auth::user()['id_usuario'] === (int) $registro['id_usuario'],
        ]);
    }

    public function update() {
        Middleware::requirePermiso('usuarios');

        $id = (int) ($_GET['id'] ?? 0);
        $this->guardar($id);
    }

    private function guardar($id) {
        Middleware::requirePermiso('usuarios');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=usuarios&a=index');
        }

        $usuarioModel = new Usuario();

        $valores = [
            'nombre_usuario' => trim($_POST['nombre_usuario'] ?? ''),
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'id_rol' => $_POST['id_rol'] ?? '',
            'activo' => ($_POST['activo'] ?? '1') === '1' ? 1 : 0,
        ];
        $esUsuarioActual = $id && (int) Auth::user()['id_usuario'] === (int) $id;

        // El cambio de contraseña del propio usuario logueado se hace desde
        // "Cambiar contraseña" (confirma la contraseña actual); este formulario
        // de Usuarios nunca la modifica para el usuario logueado, aunque se envíe.
        $contrasena = $esUsuarioActual ? '' : ($_POST['contrasena'] ?? '');
        $confirmar = $esUsuarioActual ? '' : ($_POST['confirmar_contrasena'] ?? '');

        $rolModel = new Rol();
        $roles = $rolModel->listarActivos();

        $errores = $this->validar($usuarioModel, $roles, $valores, $contrasena, $confirmar, $id);

        if (!empty($errores)) {
            $registro = $id ? $usuarioModel->buscarPorId($id) : null;

            $this->view('usuarios.form', [
                'usuario' => Auth::user(),
                'roles' => $roles,
                'registro' => $registro,
                'errores' => $errores,
                'valores' => $valores,
                'csrfToken' => Csrf::token(),
                'esUsuarioActual' => $esUsuarioActual,
            ]);
            return;
        }

        $datos = $valores;

        if ($contrasena !== '') {
            $datos['contrasena_hash'] = password_hash($contrasena, PASSWORD_BCRYPT);
        }

        if ($id) {
            $usuarioModel->actualizar($id, $datos);
            Bitacora::registrar('Modificar', 'Usuarios', "Modificación del usuario '{$valores['nombre_usuario']}'", 'usuarios', $id);
            Flash::set('success', 'Usuario actualizado correctamente.');
        } else {
            $idCreado = $usuarioModel->crear($datos);
            Bitacora::registrar('Crear', 'Usuarios', "Creación del usuario '{$valores['nombre_usuario']}'", 'usuarios', $idCreado);
            Flash::set('success', 'Usuario creado correctamente.');
        }

        $this->redirect('index.php?c=usuarios&a=index');
    }

    private function validar(Usuario $usuarioModel, array $roles, array $valores, $contrasena, $confirmar, $idExcluir) {
        $errores = [];

        if ($valores['nombre_usuario'] === '' || strlen($valores['nombre_usuario']) < 3) {
            $errores['nombre_usuario'] = 'El nombre de usuario debe tener al menos 3 caracteres.';
        } elseif ($usuarioModel->existeNombreUsuario($valores['nombre_usuario'], $idExcluir)) {
            $errores['nombre_usuario'] = 'Ese nombre de usuario ya está en uso.';
        }

        if ($valores['nombre_completo'] === '') {
            $errores['nombre_completo'] = 'El nombre completo es obligatorio.';
        }

        if (!filter_var($valores['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Ingrese un correo electrónico válido.';
        } elseif ($usuarioModel->existeCorreo($valores['correo'], $idExcluir)) {
            $errores['correo'] = 'Ese correo ya está registrado.';
        }

        if (!$idExcluir && $contrasena === '') {
            $errores['contrasena'] = 'La contraseña es obligatoria.';
        } elseif ($contrasena !== '' && !PasswordPolicy::cumple($contrasena)) {
            $errores['contrasena'] = 'La contraseña no cumple con los requisitos: ' . PasswordPolicy::DESCRIPCION;
        } elseif ($contrasena !== '' && $contrasena !== $confirmar) {
            $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
        }

        $idsValidos = array_column($roles, 'id_rol');
        if (empty($valores['id_rol']) || !in_array((int) $valores['id_rol'], $idsValidos, true)) {
            $errores['id_rol'] = 'Seleccione un rol válido.';
        }

        return $errores;
    }

    public function toggleEstado() {
        Middleware::requirePermiso('usuarios');

        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            $this->redirect('index.php?c=usuarios&a=index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $usuarioActual = Auth::user();

        if ((int) $usuarioActual['id_usuario'] === $id) {
            Flash::set('danger', 'No puede desactivar su propio usuario.');
            $this->redirect('index.php?c=usuarios&a=index');
        }

        $usuarioModel = new Usuario();
        $registro = $usuarioModel->buscarPorId($id);

        if ($registro) {
            $nuevoEstado = $registro['activo'] ? 'Inactivo' : 'Activo';
            $usuarioModel->cambiarEstado($id, $registro['activo'] ? 0 : 1);
            Bitacora::registrar(
                'Modificar',
                'Usuarios',
                "Modificación del usuario '{$registro['nombre_usuario']}' — Cambio de estado a {$nuevoEstado}",
                'usuarios',
                $id
            );
            Flash::set('success', $registro['activo'] ? 'Usuario desactivado.' : 'Usuario activado.');
        }

        $this->redirect('index.php?c=usuarios&a=index');
    }
}
