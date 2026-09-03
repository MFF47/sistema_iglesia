<?php
require_once __DIR__ . '/Database.php';

class Model {
    protected $db;

    public function __construct($connection = null) {
        $this->db = $connection ?? Database::getConnection();
    }
}
