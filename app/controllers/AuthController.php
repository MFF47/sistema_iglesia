<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Bitacora.php';

class AuthController extends Controller {
    private const MAX_INTENTOS = 5;

    public function login() {
        if (Auth::check()) {
            $this->redirect('index.php?c=home&a=index');
        }

        $error = null;
        $nombreUsuario = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
            $contrasena = $_POST['contrasena'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!Csrf::verify($csrfToken)) {
                $error = 'Solicitud inválida, intente nuevamente.';
            } elseif ($nombreUsuario === '' || $contrasena === '') {
                $error = 'Ingrese usuario y contraseña.';
            } else {
                $error = $this->autenticar($nombreUsuario, $contrasena);
            }
        }

        $this->view('auth.login', [
            'error' => $error,
            'nombreUsuario' => $nombreUsuario,
            'csrfToken' => Csrf::token(),
        ]);
    }

    private function autenticar($nombreUsuario, $contrasena) {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorNombreUsuario($nombreUsuario);

        if ($usuario && password_verify($contrasena, $usuario['contrasena_hash'])) {
            if (!$usuario['activo']) {
                return 'Su cuenta está inactiva. Contacte al administrador.';
            }

            if ($usuario['bloqueado']) {
                return 'Su cuenta está bloqueada por múltiples intentos fallidos. Contacte al administrador.';
            }

            $usuarioModel->registrarAccesoExitoso($usuario['id_usuario']);
            Auth::login($usuario);
            Bitacora::registrar('Login', 'Autenticación', 'Inicio de sesión exitoso');
            $this->redirect('index.php?c=home&a=index');
        }

        // Mantiene un tiempo de respuesta similar cuando el usuario no existe.
        password_verify($contrasena, '$2y$10$abcdefghijklmnopqrstuuVDXbUeC7ZgO2wG1zW1r9J0Y0e6b6f6O');

        if ($usuario && !$usuario['bloqueado']) {
            $usuarioModel->registrarIntentoFallido($usuario['id_usuario'], self::MAX_INTENTOS);
        }

        Bitacora::registrar(
            'Login fallido',
            'Autenticación',
            "Intento de inicio de sesión fallido usando usuario '{$nombreUsuario}' — Contraseña incorrecta"
        );

        return 'Usuario o contraseña incorrectos.';
    }

    public function logout() {
        Auth::logout();
        $this->redirect('index.php?c=auth&a=login');
    }
}
