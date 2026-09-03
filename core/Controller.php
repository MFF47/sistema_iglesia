<?php
class Controller {
    public function view($view, $data = []) {
        extract($data);
        $file = __DIR__ . '/../app/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($file)) {
            throw new Exception("Vista no encontrada: {$view}");
        }

        require $file;
    }

    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}
