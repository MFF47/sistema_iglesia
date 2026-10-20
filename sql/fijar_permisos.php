<?php
// Script de un solo uso: corrige permisos tras extraer el .zip en cPanel.
// Algunos .zip generados en Windows no preservan el bit de ejecución en
// carpetas anidadas al extraerse en Linux, lo que bloquea a PHP para
// entrar en ellas (típicamente rompe el autoload de vendor/). Deja
// carpetas en 0755 y archivos en 0644, recursivamente. Se autoelimina.

$raiz = __DIR__ . '/..'; // raíz del proyecto (un nivel arriba de sql/)
$raizReal = realpath($raiz);

if (!$raizReal) {
    die('No se pudo resolver la carpeta raíz del proyecto.');
}

$dirs = 0;
$archivos = 0;
$errores = [];

function corregir($ruta, &$dirs, &$archivos, &$errores) {
    $items = scandir($ruta);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $ruta . '/' . $item;
        if (is_dir($full)) {
            if (@chmod($full, 0755)) {
                $dirs++;
            } else {
                $errores[] = $full;
            }
            corregir($full, $dirs, $archivos, $errores);
        } else {
            if (@chmod($full, 0644)) {
                $archivos++;
            } else {
                $errores[] = $full;
            }
        }
    }
}

corregir($raizReal, $dirs, $archivos, $errores);

header('Content-Type: text/plain; charset=utf-8');
echo "Raiz: {$raizReal}\n";
echo "Carpetas corregidas: {$dirs}\n";
echo "Archivos corregidos: {$archivos}\n";
if ($errores) {
    echo "No se pudieron cambiar (" . count($errores) . "):\n";
    foreach (array_slice($errores, 0, 20) as $e) {
        echo "  - {$e}\n";
    }
}

$auto = @unlink(__FILE__);
echo $auto ? "\nEste script se elimino automaticamente.\n" : "\nBorralo manualmente ahora.\n";
