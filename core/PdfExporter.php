<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExporter {
    public static function generar($html, $nombreArchivo, $orientacion = 'portrait') {
        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opciones);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', $orientacion);
        $dompdf->render();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($nombreArchivo, ['Attachment' => true]);
        exit;
    }
}
