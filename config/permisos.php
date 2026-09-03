<?php
// Mapa de módulo => roles que tienen acceso.
// Fuente única de verdad: la usan tanto el sidebar (para mostrar/ocultar enlaces)
// como Middleware::requirePermiso() en cada controlador.
return [
    'dashboard'  => ['Administrador', 'Tesorero', 'Supervisor', 'Consulta'],
    'ingresos'   => ['Administrador', 'Tesorero', 'Supervisor'],
    'egresos'    => ['Administrador', 'Tesorero', 'Supervisor'],
    'facturas'   => ['Administrador', 'Tesorero', 'Supervisor'],
    'categorias' => ['Administrador'],
    'usuarios'   => ['Administrador'],
    'reporteria' => ['Administrador', 'Tesorero', 'Supervisor', 'Consulta'],
    'bitacora'   => ['Administrador', 'Supervisor'],
];
