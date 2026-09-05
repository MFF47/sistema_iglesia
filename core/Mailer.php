<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer {
    /**
     * Genera el HTML de una notificación con la paleta de colores del sistema
     * (encabezado navy/teal, botón de acción en teal, acento en amber).
     */
    public static function plantillaBoton($titulo, $nombreDestinatario, array $parrafos, $textoBoton, $enlace, $notaPie) {
        $parrafosHtml = '';
        foreach ($parrafos as $parrafo) {
            $parrafosHtml .= '<p style="margin:0 0 16px;color:#3c4a56;font-size:14px;line-height:1.6;">' . $parrafo . '</p>';
        }

        return '
        <div style="background:#f4f6f8;padding:32px 16px;font-family:Segoe UI, Roboto, Arial, sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e2e8ee;">
                <tr>
                    <td style="background:#0c3a46;padding:28px 32px;text-align:center;">
                        <div style="width:52px;height:52px;border-radius:50%;background:#16324a;margin:0 auto 12px;line-height:52px;text-align:center;">
                            <span style="color:#d98c22;font-size:22px;">&#128274;</span>
                        </div>
                        <p style="margin:0;color:#cfe8ee;font-size:12px;letter-spacing:.04em;text-transform:uppercase;">Iglesia Centroamericana Nueva Vida</p>
                        <p style="margin:4px 0 0;color:#ffffff;font-size:17px;font-weight:600;">Sistema de Control Financiero</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 16px;color:#16324a;font-size:19px;font-weight:700;">' . htmlspecialchars($titulo) . '</h1>
                        <p style="margin:0 0 16px;color:#3c4a56;font-size:14px;line-height:1.6;">Hola ' . htmlspecialchars($nombreDestinatario) . ',</p>
                        ' . $parrafosHtml . '
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                            <tr>
                                <td style="border-radius:6px;background:#0c3a46;">
                                    <a href="' . $enlace . '" style="display:inline-block;padding:12px 28px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:6px;">' . htmlspecialchars($textoBoton) . '</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 4px;color:#8a97a3;font-size:12px;">Si el botón no funciona, copie y pegue este enlace en su navegador:</p>
                        <p style="margin:0 0 20px;color:#0c3a46;font-size:12px;word-break:break-all;">' . $enlace . '</p>
                        <hr style="border:none;border-top:1px solid #eef1f4;margin:0 0 16px;">
                        <p style="margin:0;color:#8a97a3;font-size:12px;line-height:1.6;">' . $notaPie . '</p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f4f6f8;padding:16px 32px;text-align:center;">
                        <p style="margin:0;color:#adb5bd;font-size:11px;">&copy; ' . date('Y') . ' Iglesia Centroamericana Nueva Vida. Notificación generada automáticamente, por favor no responda a este correo.</p>
                    </td>
                </tr>
            </table>
        </div>';
    }

    public static function enviar($destinatario, $nombreDestinatario, $asunto, $cuerpoHtml) {
        $config = require __DIR__ . '/../config/mail.php';

        if (empty($config['usuario']) || empty($config['clave'])) {
            throw new Exception('El envío de correo no está configurado. Complete config/mail.php.');
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['usuario'];
            $mail->Password = $config['clave'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($config['remitente_correo'], $config['remitente_nombre']);
            $mail->addAddress($destinatario, $nombreDestinatario);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $cuerpoHtml;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $cuerpoHtml));

            $mail->send();

            return true;
        } catch (PHPMailerException $e) {
            error_log('Error al enviar correo: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
