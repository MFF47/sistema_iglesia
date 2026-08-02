# Sistema de Control Financiero y Administrativo
## Iglesia Centroamericana Nueva Vida

Proyecto de graduación — Ingeniería en Sistemas, UMG (10mo semestre).
Cliente: Iglesia Centroamericana Nueva Vida.

## Stack técnico
- Backend: PHP (arquitectura MVC)
- Base de datos: MySQL (script ya generado, 13 tablas, nombre de BD: `sistema_iglesia`)
- Frontend: Bootstrap 5
- Entorno local: XAMPP (solo desarrollo, la iglesia usa hosting externo en la nube — NO hay servidor local ni firewall en producción)
- MySQL Workbench para gestión de BD

## Alcance del sistema
- 10 módulos funcionales
- 13 tablas (roles, usuarios, sesiones, ingresos, egresos, facturas, categorías, adjuntos, bitácora, configuración, actividades, y otras — sin tabla `proveedores`, se descartó por no aplicar a la iglesia)
- 17 pantallas (mockups HTML ya diseñados, paleta teal/navy/amber)

## Plan de desarrollo (orden de dependencias técnicas)
1. Estructura de carpetas del proyecto (MVC)
2. Configuración y conexión a base de datos
3. Módulo de autenticación (login, sesiones, cierre de sesión)
4. Control de acceso por roles y middleware de seguridad
5. Layout base (topbar, sidebar, contenido)
6. Gestión de usuarios y roles
7. Módulo de categorías
8. Ingresos / Egresos / Facturas
9. Reportería y Dashboard
10. Bitácora y configuración (después de la demo si falta tiempo)

## Fecha objetivo
Demo funcional: 24 de agosto de 2026.
Ritmo: ~2 horas diarias de desarrollo.

## Decisiones ya tomadas
- El usuario administrador se crea vía la aplicación PHP (no por script SQL), para evitar problemas con el placeholder de bcrypt.
- Prioridad para la demo: estructura base + login por rol + ingresos/egresos + facturas + reportería básica + dashboard.
- Bitácora completa y exportación avanzada pueden quedar para después de la demo.

## Preferencias de trabajo
- Avanzar módulo por módulo, probando cada uno en XAMPP antes de pasar al siguiente.
- Ir directo a la mejor solución técnica, sin presentar múltiples alternativas salvo que se pida.
