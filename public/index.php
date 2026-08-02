<?php
require_once __DIR__ . '/../core/Router.php';

try {
    Router::route('home', 'index');
} catch (Exception $e) {
    echo '<h1>Error</h1><p>' . $e->getMessage() . '</p>';
}
