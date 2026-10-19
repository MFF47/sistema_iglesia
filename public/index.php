<?php
$appConfigFile = file_exists(__DIR__ . '/../config/app.php')
    ? __DIR__ . '/../config/app.php'
    : __DIR__ . '/../config/app.example.php';
$appConfig = require $appConfigFile;

date_default_timezone_set($appConfig['timezone']);

error_reporting(E_ALL);
ini_set('display_errors', $appConfig['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/app.log');

if ($appConfig['force_https'] && empty($_SERVER['HTTPS'])) {
    $destino = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $destino, true, 301);
    exit;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $appConfig['force_https'],
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../core/Router.php';

$controller = $_GET['c'] ?? 'home';
$action = $_GET['a'] ?? 'index';

try {
    Router::route($controller, $action);
} catch (Exception $e) {
    http_response_code(404);
    require __DIR__ . '/../app/views/errores/404.php';
}
