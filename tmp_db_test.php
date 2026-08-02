<?php
$cfg = require 'config/database.php';
$mysqli = new mysqli($cfg['host'], $cfg['username'], $cfg['password'], $cfg['dbname'], (int) $cfg['port']);

if ($mysqli->connect_error) {
    echo "CONNECTION_FAILED\n";
    echo $mysqli->connect_error . "\n";
    exit(1);
}

echo "CONNECTION_OK\n";
echo $cfg['host'] . ':' . $cfg['port'] . "\n";
echo $cfg['dbname'] . "\n";
$mysqli->close();
