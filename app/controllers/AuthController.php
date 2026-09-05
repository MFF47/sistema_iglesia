<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Sesion.php';
require_once __DIR__ . '/../models/Bitacora.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../../core/PasswordPolicy.php';

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
            'flash' => Flash::get(),
        ]);
    }

    public function recuperar() {
        if (Auth::check()) {
            $this->redirect('index.php?c=home&a=index');
        }

        $error = null;
        $mensaje = null;
        $correo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = trim($_POST['correo'] ?? '');
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!Csrf::verify($csrfToken)) {
                $error = 'Solicitud inválida, intente nuevamente.';
            } elseif ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ingrese un correo electrónico válido.';
            } else {
                $this->enviarEnlaceRecuperacion($correo);
                // Mensaje genérico siempre, exista o no el correo, para no revelar qué cuentas existen.
                $mensaje = 'Si el correo está registrado, se ha enviado un enlace de recuperación. Revise su bandeja de entrada.';
                $correo = '';
            }
        }

        $this->view('auth.recuperar', [
            'error' => $error,
            'mensaje' => $mensaje,
            'correo' => $correo,
            'csrfToken' => Csrf::token(),
        ]);
    }

    private function enviarEnlaceRecuperacion($correo) {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorCorreo($correo);

        if (!$usuario || !$usuario['activo']) {
            return;
        }

        $sesionModel = new Sesion();
        $token = $sesionModel->crearTokenRecuperacion($usuario['id_usuario']);

        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $enlace = $protocolo . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/index.php?c=auth&a=restablecer&token=' . $token;
        $enlace = str_replace('\\', '/', $enlace);

        $cuerpo = Mailer::plantillaBoton(
            'Solicitud de restablecimiento de contraseña',
            $usuario['nombre_completo'],
            [
                'Recibimos una solicitud para restablecer la contraseña de tu cuenta en el Sistema de Control Financiero.',
                'Este enlace es válido por <strong>' . Sesion::MINUTOS_VALIDEZ_RECUPERACION . ' minutos</strong> y solo puede usarse una vez.',
            ],
            'Restablecer contraseña',
            $enlace,
            'Si no solicitaste este cambio, puedes ignorar este correo con tranquilidad — tu contraseña actual seguirá funcionando.'
        );

        try {
            Mailer::enviar($correo, $usuario['nombre_completo'], 'Recuperación de contraseña', $cuerpo);
            Bitacora::registrar('Recuperación de contraseña', 'Autenticación', "Se envió enlace de recuperación al usuario '{$usuario['nombre_usuario']}'");
        } catch (Exception $e) {
            error_log('Error al enviar enlace de recuperación: ' . $e->getMessage());
        }
    }

    public function restablecer() {
        if (Auth::check()) {
            $this->redirect('index.php?c=home&a=index');
        }

        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        $sesionModel = new Sesion();
        $sesion = $token !== '' ? $sesionModel->buscarTokenRecuperacionValido($token) : null;

        if (!$sesion) {
            $this->view('auth.restablecer', [
                'tokenValido' => false,
                'error' => null,
                'token' => '',
                'csrfToken' => Csrf::token(),
            ]);
            return;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contrasena = $_POST['contrasena'] ?? '';
            $confirmar = $_POST['confirmar_contrasena'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!Csrf::verify($csrfToken)) {
                $error = 'Solicitud inválida, intente nuevamente.';
            } elseif (!PasswordPolicy::cumple($contrasena)) {
                $error = 'La contraseña no cumple con los requisitos indicados.';
            } elseif ($contrasena !== $confirmar) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                $usuarioModel = new Usuario();
                $usuarioModel->actualizarContrasena($sesion['id_usuario'], password_hash($contrasena, PASSWORD_DEFAULT));
                $sesionModel->marcarComoUsado($sesion['id_sesion']);

                $usuario = $usuarioModel->buscarPorId($sesion['id_usuario']);
                Bitacora::registrar('Restablecimiento de contraseña', 'Autenticación', "El usuario '{$usuario['nombre_usuario']}' restableció su contraseña");

                Flash::set('success', 'Contraseña actualizada correctamente. Ya puede iniciar sesión.');
                $this->redirect('index.php?c=auth&a=login');
            }
        }

        $this->view('auth.restablecer', [
            'tokenValido' => true,
            'error' => $error,
            'token' => $token,
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
