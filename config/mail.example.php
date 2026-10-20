<?php
// Copia este archivo como mail.php y completa tus datos reales.
// mail.php está excluido de git (.gitignore) porque contiene credenciales.
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    // 'tls' para puerto 587 (STARTTLS), 'ssl' para puerto 465 (SSL/TLS implícito).
    'encripcion' => 'tls',
    'usuario' => 'tucorreo@gmail.com',
    'clave' => 'contraseña-de-aplicacion',
    'remitente_correo' => 'tucorreo@gmail.com',
    'remitente_nombre' => 'Iglesia Centroamericana Nueva Vida',
];
