<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Flash.php';
require_once __DIR__ . '/../../core/PasswordPolicy.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Bitacora.php';

class PerfilController extends Controller {
    public function cambiarPassword() {
        Middleware::requireAuth();

        $error = null;
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorId(Auth::user()['id_usuario']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actual = $_POST['contrasena_actual'] ?? '';
            $nueva = $_POST['contrasena_nueva'] ?? '';
            $confirmar = $_POST['confirmar_contrasena'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!Csrf::verify($csrfToken)) {
                $error = 'Solicitud inválida, intente nuevamente.';
            } elseif (!password_verify($actual, $usuario['contrasena_hash'])) {
                $error = 'La contraseña actual no es correcta.';
            } elseif (!PasswordPolicy::cumple($nueva)) {
                $error = 'La nueva contraseña no cumple con los requisitos indicados.';
            } elseif ($nueva !== $confirmar) {
                $error = 'Las contraseñas nuevas no coinciden.';
            } elseif (password_verify($nueva, $usuario['contrasena_hash'])) {
                $error = 'La nueva contraseña debe ser diferente a la actual.';
            } else {
                $usuarioModel->actualizarContrasena($usuario['id_usuario'], password_hash($nueva, PASSWORD_DEFAULT));
                Bitacora::registrar(
                    'Modificar',
                    'Perfil',
                    "El usuario '{$usuario['nombre_usuario']}' cambió su propia contraseña",
                    'usuarios',
                    $usuario['id_usuario']
                );
                Flash::set('success', 'Contraseña actualizada correctamente.');
                $this->redirect('index.php?c=perfil&a=cambiarPassword');
            }
        }

        $this->view('perfil.cambiar_password', [
            'usuario' => Auth::user(),
            'error' => $error,
            'flash' => Flash::get(),
            'csrfToken' => Csrf::token(),
        ]);
    }
}
