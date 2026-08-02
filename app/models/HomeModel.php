<?php
require_once __DIR__ . '/../../core/Model.php';

class HomeModel extends Model {
    public function getWelcomeMessage() {
        return 'Bienvenido al sistema de la Iglesia Centroamericana Nueva Vida';
    }
}
