<?php
class Model {
    protected $db;

    public function __construct($connection = null) {
        $this->db = $connection;
    }
}
