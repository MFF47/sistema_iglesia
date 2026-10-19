<?php
// Copie este archivo como app.php y ajuste según el ambiente.
// app.php está excluido de git (.gitignore).
return [
    // true solo en desarrollo local: muestra errores en pantalla.
    // En producción debe quedar en false (los errores se registran en storage/logs/).
    'debug' => true,

    // Fuerza HTTPS y marca las cookies de sesión como Secure.
    // En producción, una vez el dominio tenga SSL activo, debe quedar en true.
    'force_https' => false,

    'timezone' => 'America/Guatemala',
];
