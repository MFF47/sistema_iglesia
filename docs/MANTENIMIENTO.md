# Mantenimiento en Producción
## Sistema de Control Financiero y Administrativo — Iglesia Centroamericana Nueva Vida

Este documento explica cómo actualizar el sistema ya desplegado en `https://finanzasnv.com` (hosting Namecheap Stellar, cPanel, sin Terminal/SSH) y deja registradas las decisiones y datos específicos de este servidor para no tener que redescubrirlos.

---

## 1. Datos del servidor

| Dato | Valor |
|---|---|
| Usuario de cPanel | `finajcgt` |
| Ruta del proyecto | `/home/finajcgt/sistema_iglesia/` |
| Document Root del dominio | `/home/finajcgt/public_html` (no se pudo cambiar — ver sección 2) |
| Base de datos | `finajcgt_sistema_iglesia` |
| Usuario de BD | `finajcgt_admin` (contraseña solo en `config/database.php` del servidor, no en este documento) |
| Host/puerto MySQL | `localhost:3306` |
| SMTP (correo) | host `server369.web-hosting.com`, puerto `587`, encripción `tls`, usuario `noreply@finanzasnv.com` |
| PHP | 8.2 |

> La contraseña de la base de datos y la del correo **solo existen** en `config/database.php` y `config/mail.php` dentro del servidor — ninguno de los dos se sube a git ni se escribe en este documento.

---

## 2. Por qué la estructura es distinta a la de desarrollo

En desarrollo local, Apache sirve directamente desde `public/`. En este hosting **no se pudo** cambiar el Document Root del dominio principal (`finanzasnv.com` → cPanel solo permite editar el Document Root de subdominios/dominios adicionales, no del dominio principal de la cuenta). La solución fue un archivo "puente":

- `public_html/index.php` contiene una sola línea:
  ```php
  <?php
  require '/home/finajcgt/sistema_iglesia/public/index.php';
  ```
  PHP resuelve las rutas según dónde vive físicamente cada archivo, así que el `index.php` real (con toda su lógica) sigue funcionando sin cambios, sin importar que lo invoquen desde otro lado.
- Los archivos estáticos (`public/assets/`) sí tuvieron que **copiarse** físicamente a `public_html/assets/`, porque esos los sirve Apache directo del disco, sin pasar por PHP — no hay forma de "apuntar" a ellos como con el `index.php`.
- `app/`, `core/`, `config/`, `vendor/`, `storage/`, `sql/` quedan fuera de `public_html`, así que no son alcanzables por URL aunque fallaran sus `.htaccess`.

**Esto significa**: si en el futuro alguna vez se puede cambiar el Document Root (por ejemplo, si se contrata un plan superior con esa opción, o soporte de Namecheap lo habilita), se puede simplificar borrando el puente y apuntando el Document Root directo a `sistema_iglesia/public`. Mientras tanto, cualquier archivo nuevo dentro de `public/assets/` (una imagen, un ícono nuevo) hay que copiarlo también a mano a `public_html/assets/`.

---

## 3. Cambios chicos (1-3 archivos)

Para un bugfix puntual, un ajuste de texto, una validación:

1. Edita y prueba el cambio en tu XAMPP local primero.
2. En cPanel → **File Manager**, navega al archivo equivalente dentro de `sistema_iglesia/` (misma ruta relativa que en tu proyecto local).
3. **Edit** → reemplaza el contenido → **Save Changes**.
4. Prueba el cambio en `https://finanzasnv.com/`.

---

## 4. Cambios grandes (varios archivos, módulo nuevo)

1. Desarrolla y prueba todo en local, como siempre.
2. Corre `generar_paquete.ps1` desde la raíz del proyecto — genera `sistema_iglesia_deploy.zip` un nivel arriba del proyecto.
3. Sube ese `.zip` a `/home/finajcgt/` (File Manager → Upload) y **extráelo encima** de la carpeta `sistema_iglesia` ya existente (Extract, destino `/home/finajcgt/sistema_iglesia`). La extracción sobrescribe los archivos de código, pero:
   - **No incluye** `config/database.php`, `config/app.php` ni `config/mail.php` (el script los excluye siempre) — los de producción quedan intactos.
   - **No borra** lo que ya esté en `storage/adjuntos/` y `storage/facturas/` (comprobantes reales que la iglesia ya subió) — el paquete solo trae esas carpetas vacías, y extraer no elimina archivos que no vienen en el `.zip`, solo agrega/sobrescribe los que sí vienen.
4. **Paso que casi seguro hay que repetir**: después de extraer un `.zip` nuevo, las carpetas anidadas (sobre todo dentro de `vendor/`) suelen quedar en permiso `644` en vez de `755` — esto rompe el sistema con error 500 (ver sección 6). Sube `sql/fijar_permisos.php` a `public_html/` temporalmente, ábrelo una vez desde el navegador, y se autoelimina al terminar (mismo procedimiento que la primera vez).
5. Si cambiaste algo de `public/assets/` (CSS nuevo, por ejemplo), copia esos archivos también a mano a `public_html/assets/` — no vienen incluidos automáticamente en ese paso.
6. Corre la lista de verificación de la sección 7.

---

## 5. Cambios en la base de datos

No van dentro del `.zip`. Para agregar una tabla, columna, o corregir datos:

1. Prueba el cambio de esquema en tu MySQL local primero.
2. En el servidor, cPanel → **phpMyAdmin** → selecciona `finajcgt_sistema_iglesia` → pestaña **SQL** → pega y ejecuta el `ALTER TABLE`/`CREATE TABLE` correspondiente.
3. Si es un cambio de esquema importante, documéntalo en `docs/REPORTE_AVANCE.md` igual que los cambios anteriores (columna `id_factura`, eliminación de la tabla `configuracion`), para que quede el porqué.

---

## 6. Problemas ya conocidos de este hosting (para no perder tiempo re-diagnosticando)

- **Error 500 después de extraer un `.zip`**: casi siempre son permisos (carpetas en `644`). Solución: `sql/fijar_permisos.php` (sección 4, paso 4).
- **El correo de recuperación de contraseña no llega / tarda minutos**: revisa `storage/logs/app.log` primero (File Manager → Edit, directo, ese archivo no es accesible por web). Si dice *"Could not connect to SMTP host"* sin más detalle, es que el hosting bloquea ese puerto — probar el otro (465↔587). Si dice *"Peer certificate subjectAltName did not match"*, el certificado TLS del correo no coincide con el hostname usado — hay que conectarse con el nombre real del servidor (hoy es `server369.web-hosting.com`; si cPanel migra la cuenta a otro servidor, este nombre puede cambiar — se obtiene en **Email Accounts → Connect Devices** de la cuenta de correo).
- **Las horas en Bitácora u otros reportes se ven adelantadas/atrasadas**: `NOW()` de MySQL usa el reloj del sistema operativo del servidor de base de datos, no el de PHP. Ya está corregido en `core/Database.php` (`SET time_zone = '-06:00'` en cada conexión) — si se ve mal de nuevo, confirmar que ese archivo no se haya sobrescrito con una versión vieja al extraer un `.zip`.
- **El Document Root del dominio principal no se puede cambiar** desde cPanel → Domains → Manage (sale "No configuration options currently exist") — por eso existe el puente de la sección 2. No perder tiempo buscando esa opción de nuevo salvo que cambie el plan de hosting.

---

## 7. Lista de verificación después de cualquier actualización grande

- [ ] `https://finanzasnv.com/` carga el login (no error 500/403).
- [ ] Login con el usuario Administrador real funciona.
- [ ] `/app`, `/core`, `/config`, `/storage`, `/vendor`, `/sql` devuelven 403 directo por URL.
- [ ] Crear un ingreso con comprobante (prueba que `storage/` sigue escribible).
- [ ] Exportar Excel y PDF desde Reportería.
- [ ] Bitácora muestra los eventos nuevos con la hora correcta.
- [ ] (Si se tocó algo de autenticación) recuperación de contraseña: el correo llega y con el diseño correcto.

---

## 8. Scripts de instalación/mantenimiento disponibles

Todos viven en `sql/` (protegido por `.htaccess`, hay que copiarlos temporalmente a `public_html/` para correrlos por navegador, y luego se autoeliminan):

- `sql/instalacion.sql` — esquema limpio completo. Solo se usa para un ambiente nuevo desde cero (no para actualizar el actual).
- `sql/crear_administrador.php` — crea el primer Administrador. Se niega a correr si ya existe uno.
- `sql/fijar_permisos.php` — corrige permisos de carpetas tras extraer un `.zip` (sección 4, paso 4).

Si copias cualquiera de estos a `public_html/`, hazlo como **puente** (igual que `index.php`, sección 2) y no como copia directa — sus rutas relativas internas (`__DIR__ . '/../...'`) asumen que siguen viviendo dentro de `sql/`:

```php
<?php
require '/home/finajcgt/sistema_iglesia/sql/NOMBRE_DEL_SCRIPT.php';
@unlink(__FILE__);
```
