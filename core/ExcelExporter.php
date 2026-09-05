<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExporter {
    const NAVY = '16324A';
    const TEAL_DARK = '0C3A46';
    const AMBER = 'D98C22';
    const LIGHT_GRAY = 'F4F6F8';
    const BORDER_GRAY = 'DDDDDD';

    /**
     * @param array $encabezados nombres de columna
     * @param array $filas filas de datos (cada una un array indexado igual que $encabezados)
     * @param array $anchos ancho de cada columna
     * @param array|null $totales una fila final destacada, o varias (array de arrays) para más de un total
     * @param array $columnasMoneda índices de columna (0-based) a formatear como Q #,##0.00
     */
    public static function generar($titulo, $subtitulo, array $encabezados, array $filas, array $anchos, $nombreArchivo, ?array $totales = null, array $columnasMoneda = []) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        $numColumnas = count($encabezados);
        $ultimaColumna = self::letraColumna($numColumnas);

        $sheet->mergeCells("A1:{$ultimaColumna}1");
        $sheet->setCellValue('A1', $titulo);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::TEAL_DARK);
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells("A2:{$ultimaColumna}2");
        $sheet->setCellValue('A2', $subtitulo);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('555555');
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFEFEF');
        $sheet->getRowDimension(2)->setRowHeight(20);

        $filaEncabezado = 3;
        foreach ($encabezados as $i => $encabezado) {
            $sheet->setCellValue(self::letraColumna($i + 1) . $filaEncabezado, $encabezado);
        }
        $rangoEncabezado = "A{$filaEncabezado}:{$ultimaColumna}{$filaEncabezado}";
        $sheet->getStyle($rangoEncabezado)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($rangoEncabezado)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::NAVY);
        $sheet->getStyle($rangoEncabezado)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($filaEncabezado)->setRowHeight(22);

        $filaActual = $filaEncabezado + 1;
        foreach ($filas as $indiceFila => $filaDatos) {
            foreach (array_values($filaDatos) as $i => $valor) {
                $sheet->setCellValue(self::letraColumna($i + 1) . $filaActual, $valor);
            }
            if ($indiceFila % 2 === 1) {
                $sheet->getStyle("A{$filaActual}:{$ultimaColumna}{$filaActual}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::LIGHT_GRAY);
            }
            $filaActual++;
        }

        if ($filaActual > $filaEncabezado + 1) {
            $rangoDatos = "A{$filaEncabezado}:{$ultimaColumna}" . ($filaActual - 1);
            $sheet->getStyle($rangoDatos)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER_GRAY);
        }

        if ($totales !== null) {
            $filasTotales = is_array($totales[0] ?? null) ? $totales : [$totales];

            foreach ($filasTotales as $filaTotal) {
                foreach (array_values($filaTotal) as $i => $valor) {
                    $sheet->setCellValue(self::letraColumna($i + 1) . $filaActual, $valor);
                }
                $rangoTotal = "A{$filaActual}:{$ultimaColumna}{$filaActual}";
                $sheet->getStyle($rangoTotal)->getFont()->setBold(true);
                $sheet->getStyle($rangoTotal)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::AMBER);
                $sheet->getStyle($rangoTotal)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $filaActual++;
            }
        }

        foreach ($anchos as $i => $ancho) {
            $sheet->getColumnDimension(self::letraColumna($i + 1))->setWidth($ancho);
        }

        $ultimaFilaConDatos = $filaActual - 1;
        foreach ($columnasMoneda as $indiceColumna) {
            $col = self::letraColumna($indiceColumna + 1);
            $sheet->getStyle("{$col}" . ($filaEncabezado + 1) . ":{$col}{$ultimaFilaConDatos}")
                ->getNumberFormat()->setFormatCode('"Q"#,##0.00');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private static function letraColumna($indice) {
        $letra = '';
        while ($indice > 0) {
            $modulo = ($indice - 1) % 26;
            $letra = chr(65 + $modulo) . $letra;
            $indice = intdiv($indice - 1, 26);
        }
        return $letra;
    }
}
