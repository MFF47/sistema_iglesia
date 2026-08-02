<?php
$cfg = require 'config/database.php';
$ports = [3306, 3307];
foreach ($ports as $port) {
    $mysqli = @new mysqli($cfg['host'], $cfg['username'], $cfg['password'], $cfg['dbname'], $port);
    if ($mysqli->connect_error) {
        echo "PORT_$port:FAIL:" . $mysqli->connect_error . PHP_EOL;
    } else {
        echo "PORT_$port:OK" . PHP_EOL;
        $mysqli->close();
    }
}
