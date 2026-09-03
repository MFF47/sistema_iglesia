<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Middleware.php';

class HomeController extends Controller {
    public function index() {
        Middleware::requireAuth();

        $this->view('home.index', ['usuario' => Auth::user()]);
    }
}

//Esto lo dejaremos hasta acá, ya que no es necesario tener un modelo para esta vista, pero si en el futuro se requiere, podemos crear un modelo y utilizarlo en este controlador.
//Desde el Visual Code la sincronizacion con GitHub es muy lenta, por lo que se debe hacer manualmente o GitHub Desktop.
