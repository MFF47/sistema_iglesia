<?php
require_once __DIR__ . '/../../core/Controller.php';

class HomeController extends Controller {
    public function index() {
        $this->view('home.index');
    }
}

//Esto lo dejaremos hasta acá, ya que no es necesario tener un modelo para esta vista, pero si en el futuro se requiere, podemos crear un modelo y utilizarlo en este controlador.