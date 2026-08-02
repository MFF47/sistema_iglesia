<?php
class Router {
    public static function route($controller, $action = 'index') {
        $controllerName = ucfirst($controller) . 'Controller';
        $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            throw new Exception("Controlador no encontrado: {$controllerName}");
        }

        require_once $controllerFile;
        $instance = new $controllerName();

        if (!method_exists($instance, $action)) {
            throw new Exception("Acción no encontrada: {$action}");
        }

        $instance->$action();
    }
}
