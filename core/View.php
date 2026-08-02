<?php
class View {
    public static function render($view, $data = []) {
        $controller = new Controller();
        $controller->view($view, $data);
    }
}
