<?php
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
