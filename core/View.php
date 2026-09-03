<?php
require_once __DIR__ . '/Controller.php';

class View {
    public static function render($view, $data = []) {
        $controller = new Controller();
        $controller->view($view, $data);
    }
}
