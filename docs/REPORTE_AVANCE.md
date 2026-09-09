# Reporte de Avance
## Sistema de Control Financiero y Administrativo — Iglesia Centroamericana Nueva Vida

**Fecha del reporte:** 5 de septiembre de 2026
**Demo funcional:** 27 de septiembre de 2026

---

## Estado del plan (10 módulos)

| # | Módulo | Estado |
|---|---|---|
| 1-5 | Estructura, BD, autenticación, roles/middleware, layout base | ✅ Completado |
| 6 | Gestión de usuarios y roles | ✅ Completado |
| 7 | Categorías | ✅ Completado |
| 8 | Ingresos / Egresos / Facturas | ✅ Completado |
| 9 | Reportería y Dashboard | ✅ Completado |
| 10 | Bitácora | ✅ Completado |
| — | Aportantes (módulo extra, no contemplado en los mockups originales — pasa a ser la pantalla 17) | ✅ Completado |
| — | Recuperación de contraseña (módulo extra, mockups 02 y 03) | ✅ Completado |

**Los 10 módulos del plan y las 17 pantallas (16 con mockup + Aportantes) están construidos.** Se evaluó agregar un módulo de Configuración del sistema (parámetros generales), pero se decidió que no es necesario para el alcance de este proyecto — se revirtió por completo (código, permisos, menú y la tabla `configuracion` en la base de datos).

---

## Módulos funcionales

### Autenticación
Login con `password_verify`, bloqueo tras 5 intentos fallidos, sesiones seguras (regeneración de ID, CSRF en todos los formularios).

### Roles y permisos
Administrador, Tesorero, Supervisor, Consulta. Matriz centralizada en `config/permisos.php` (una sola fuente de verdad que usan tanto el sidebar como cada controlador), con permisos de grano fino (ej. Supervisor ve Ingresos/Egresos pero no puede crear ni editar).

### Layout base
Topbar + sidebar con menú dinámico según rol. **Ahora responsivo**: en móvil el sidebar se oculta y se abre con botón de hamburguesa.

### Usuarios y Roles *(Administrador)*
CRUD completo, activar/desactivar, no se puede desactivar la propia sesión.

### Categorías *(Administrador)*
Separadas en dos tablas (Ingreso / Egreso), cada una alfabética; no se eliminan, solo se desactivan.

### Aportantes *(Administrador, módulo nuevo)*
CRUD completo (antes solo existía alta rápida desde Ingresos); accesible desde Gestión → Aportantes.

### Ingresos y Egresos *(Administrador/Tesorero gestionan, Supervisor solo consulta)*
- KPIs del mes con el **nombre del mes visible** (ej. "Total de Septiembre 2026").
- **"Disponible Caja General"** visible en cuadro verde separado (saldo acumulado histórico, no solo del período), tanto en los listados como en los formularios de creación/edición.
- Adjuntar comprobante por **archivo o cámara del dispositivo** → genera automáticamente el documento en Facturas y Recibos (requirió agregar la columna `id_factura` a la tabla `ingresos`, que no la tenía).
- Egresos: advertencia visual si el monto supera el disponible.
- **Cascada de ediciones/anulaciones**: si un ingreso/egreso tiene factura vinculada, editarlo o anularlo pide confirmación y propaga el cambio al documento (y viceversa desde Facturas), quedando registrado en Bitácora en ambos sentidos.
- **"Total filtrado"** (suma de lo que muestra el filtro actual, al pie del listado) ahora resaltado en una píldora teal con texto grande (antes era texto pequeño gris, poco visible junto al conteo de registros).

### Facturas y Recibos *(mismo esquema de permisos)*
- Puede vincularse tanto a un Ingreso como a un Egreso (antes solo a Egreso).
- Filtro **"Vinculado a"** (Ingresos / Egresos / Sin vincular).
- Etiqueta "Beneficiario / Aportante".

### Dashboard
- Selector de Mes/Año (antes solo "mes actual/anterior/año").
- Gráficas con **datos numéricos dentro** (plugin `chartjs-plugin-datalabels`): dona con porcentaje por segmento y total en el centro, barras con el monto Q encima.
- **Donas (Ingresos y Egresos)**: el nombre/categoría (Total, Subtotal, o el nombre puntual al aislar un segmento desde la leyenda) ahora se muestra **arriba de la dona**, no dentro del círculo — dentro solo queda el monto. Evita que nombres largos de categoría se corten o se encimen dentro del círculo.
- **"Comparativo Mensual del Año"** ahora a media anchura (antes ocupaba todo el ancho), un poco más alta que antes, en la misma fila que la nueva dona de egresos.
- **"Distribución de Egresos por Categoría"** (dona nueva, misma mecánica que la de Ingresos: porcentaje por segmento y total en el centro, clic en la leyenda para ver subtotales). Antes solo se veía en qué tipo de ingreso entraba el dinero (Diezmo/Ofrenda/Actividad); ahora también se ve en qué categoría de egreso se está gastando.

### Reportería *(todos los roles)*
- Tres modos: Ingresos, Egresos y **Consolidado** (ambos juntos, con Total Ingresos / Total Egresos / Balance).
- Exportación real a **Excel (.xlsx)** y **PDF**, con la paleta de colores del sistema y formato de moneda correcto (antes era CSV / ventana de impresión).
- Cada "Generar" queda registrado en Bitácora como consulta (no solo las exportaciones).

### Bitácora *(Administrador y Supervisor)*
Registra automáticamente login, altas/bajas/cambios en todos los módulos, cascadas y consultas de reportes. Descripción mostrada en líneas separadas (no en una sola oración con guiones). Exporta **solo a PDF** (se quitó Excel a pedido). Corregido un bug real: filtrar por la fecha de hoy no mostraba nada.

### Recuperación de contraseña *(mockups 02 y 03)*
Usa la tabla `sesiones` ya existente en el esquema (`tipo = 'recuperacion_password'`) para el token: aleatorio de 64 caracteres, vence en 30 minutos, de un solo uso. El mensaje de "correo enviado" es siempre el mismo exista o no la cuenta, para no revelar qué correos están registrados. Envío real por SMTP con **PHPMailer** (se agregó vía Composer); credenciales en `config/mail.php`, excluido de git porque por ahora usa un correo personal para pruebas/demo (a reemplazar por las del hosting definitivo). Cada envío y cada restablecimiento exitoso queda registrado en Bitácora. La plantilla del correo (`Mailer::plantillaBoton`) usa la paleta navy/teal/amber del sistema para que se vea como una notificación de la aplicación, no como un correo genérico. Los botones "¿Olvidó su contraseña?" y "Volver al inicio de sesión" ahora tienen la misma forma que los botones principales, diferenciados en color amber.

### Gestión de contraseñas por Administrador y autogestión
Regla de complejidad centralizada en `core/PasswordPolicy.php` (mínimo 8 caracteres, una mayúscula, un número, un símbolo) — la usan tanto la recuperación por correo como la creación/edición de usuarios, para no tener dos estándares distintos. Un Administrador puede fijar la contraseña de cualquier **otro** usuario, sin importar el rol, directamente desde Gestión → Usuarios, sin pasar por correo. Se agregó además una pantalla de autogestión ("Cambiar contraseña", accesible desde un ícono en la barra superior para cualquier rol) donde el propio usuario cambia su contraseña confirmando primero la actual. **Corrección de seguridad**: el formulario de Gestión → Usuarios permitía antes cambiarle la contraseña al usuario logueado sin confirmar la actual; ahora, al editar el propio usuario, ese formulario oculta los campos de contraseña y redirige a la pantalla de autogestión — reforzado también en el backend (`UsuariosController::guardar()` ignora cualquier contraseña recibida por ese formulario cuando el `id` editado es el del usuario logueado, incluso ante una petición manipulada).

---

## Infraestructura y correcciones técnicas

- **Composer** instalado (no existía) con **PhpOffice/PhpSpreadsheet** (Excel real) y **Dompdf** (PDF real); se habilitaron las extensiones PHP `gd` y `zip` que XAMPP traía apagadas.
- **Zona horaria corregida**: `php.ini` tenía `Europe/Berlin` de fábrica; ahora es `America/Guatemala`.
- **Seguridad**: `storage/` y `vendor/` protegidos con `.htaccess` (`Require all denied`) contra acceso directo por URL — importante porque todo el proyecto vive bajo `htdocs/`, no solo `public/`.
- Se resolvió una corrupción real de MySQL (motor Aria + tabla de privilegios) que impedía arrancar el servicio; quedó documentado el diagnóstico por si se repite.
- `.gitignore` agregado (no existía) para no versionar `vendor/`.

---

## Pendiente

- **Configurar SMTP definitivo**: `config/mail.php` usa temporalmente un correo personal para pruebas/demo; reemplazar por las credenciales del hosting antes de producción.
- **Traslados internos entre caja y banco**: el esquema actual no tiene concepto de "cuentas"; queda pendiente de decisión con quien lleva la tesorería.
- Revisar antes de producción: mover el `DocumentRoot` del hosting final a `public/`, o replicar los `.htaccess` de protección a `app/`, `core/`, `config/`.
- Confirmar visualmente el diseño responsivo en un celular real (se verificó matemáticamente que no hay desbordamiento horizontal, pero la herramienta de captura usada en desarrollo no permite simular un ancho de teléfono real menor a ~500px).

---

## Usuarios de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `Admin1234!` | Administrador |
| `tesorero1` | `Tester123!` | Tesorero |
| `supervisor1` | `Tester123!` | Supervisor |
| `consulta1` | `Tester123!` | Consulta |

*(scripts `tmp_create_admin.php` / `tmp_create_test_users.php` — borrar o cambiar contraseñas antes de producción)*
