# Código Fuente Relevante
## Sistema de Control Financiero y Administrativo — Iglesia Centroamericana Nueva Vida

Este capítulo no pretende documentar la totalidad del código del proyecto, sino únicamente los fragmentos que representan una decisión de diseño, una medida de seguridad o una regla de negocio no trivial. Cada sección incluye: el archivo y método de origen, una breve explicación de por qué es relevante, el fragmento de código correspondiente, y una sugerencia de dónde insertar una captura de pantalla del sistema en funcionamiento.

---

## 1. Autenticación segura: verificación de credenciales y bloqueo por intentos fallidos

**Archivo:** `app/controllers/AuthController.php`, método `autenticar()` · **Archivo:** `app/models/Usuario.php`, método `registrarIntentoFallido()`

El proceso de login incluye dos medidas que van más allá de una simple comparación de contraseña:

- Cuando el usuario no existe en la base de datos, el sistema igualmente ejecuta un `password_verify()` contra un hash ficticio. Esto evita que un atacante pueda distinguir "usuario no existe" de "contraseña incorrecta" midiendo el tiempo de respuesta del servidor (ataque de temporización / enumeración de usuarios).
- Tras 5 intentos fallidos consecutivos, la cuenta se bloquea automáticamente (`bloqueado = 1`) y requiere intervención de un Administrador para reactivarse.

```php
private function autenticar($nombreUsuario, $contrasena) {
    $usuarioModel = new Usuario();
    $usuario = $usuarioModel->buscarPorNombreUsuario($nombreUsuario);

    if ($usuario && password_verify($contrasena, $usuario['contrasena_hash'])) {
        if (!$usuario['activo']) {
            return 'Su cuenta está inactiva. Contacte al administrador.';
        }
        if ($usuario['bloqueado']) {
            return 'Su cuenta está bloqueada por múltiples intentos fallidos. Contacte al administrador.';
        }

        $usuarioModel->registrarAccesoExitoso($usuario['id_usuario']);
        Auth::login($usuario);
        Bitacora::registrar('Login', 'Autenticación', 'Inicio de sesión exitoso');
        $this->redirect('index.php?c=home&a=index');
    }

    // Mantiene un tiempo de respuesta similar cuando el usuario no existe.
    password_verify($contrasena, '$2y$10$abcdefghijklmnopqrstuuVDXbUeC7ZgO2wG1zW1r9J0Y0e6b6f6O');

    if ($usuario && !$usuario['bloqueado']) {
        $usuarioModel->registrarIntentoFallido($usuario['id_usuario'], self::MAX_INTENTOS);
    }

    return 'Usuario o contraseña incorrectos.';
}
```

```php
public function registrarIntentoFallido($idUsuario, $maxIntentos) {
    $stmt = $this->db->prepare('UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id_usuario = ?');
    $stmt->execute([$idUsuario]);

    $stmt = $this->db->prepare('SELECT intentos_fallidos FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$idUsuario]);
    $intentos = (int) $stmt->fetchColumn();

    if ($intentos >= $maxIntentos) {
        $stmt = $this->db->prepare('UPDATE usuarios SET bloqueado = 1, fecha_bloqueo = NOW() WHERE id_usuario = ?');
        $stmt->execute([$idUsuario]);
    }
}
```

**Pantallazo sugerido:** pantalla de login mostrando el mensaje "Su cuenta está bloqueada por múltiples intentos fallidos."

---

## 2. Protección CSRF en formularios

**Archivo:** `core/Csrf.php`

Todos los formularios del sistema (login, creación/edición en cada módulo, cambios de contraseña) incluyen un token oculto que se valida en el servidor antes de procesar la solicitud. Esto evita que un sitio malicioso induzca al navegador de un usuario autenticado a enviar una petición no deseada (Cross-Site Request Forgery).

```php
class Csrf {
    public static function token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verify($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', (string) $token);
    }
}
```

El uso de `hash_equals()` (en vez de `===`) es intencional: evita otro tipo de ataque de temporización al comparar el token recibido contra el almacenado en sesión.

**Pantallazo sugerido:** el campo oculto `csrf_token` visible en el HTML fuente de cualquier formulario (herramientas de desarrollador del navegador).

---

## 3. Recuperación de contraseña con token de un solo uso

**Archivo:** `app/models/Sesion.php`, método `crearTokenRecuperacion()` · **Archivo:** `app/controllers/AuthController.php`, método `recuperar()`

Al solicitar la recuperación de contraseña, el sistema genera un token aleatorio criptográficamente seguro (32 bytes, 64 caracteres hexadecimales), lo asocia al usuario con una expiración de 30 minutos, y lo marca como usado una vez consumido — de modo que el mismo enlace no pueda reutilizarse.

```php
class Sesion extends Model {
    const MINUTOS_VALIDEZ_RECUPERACION = 30;

    public function crearTokenRecuperacion($idUsuario) {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->db->prepare(
            'INSERT INTO sesiones (id_usuario, token, tipo, ip_origen, user_agent, fecha_expiracion)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
        );
        $stmt->execute([
            $idUsuario, $token, 'recuperacion_password',
            $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null,
            self::MINUTOS_VALIDEZ_RECUPERACION,
        ]);

        return $token;
    }
}
```

Adicionalmente, la respuesta al usuario es siempre el mismo mensaje genérico, exista o no el correo en la base de datos:

```php
// Mensaje genérico siempre, exista o no el correo, para no revelar qué cuentas existen.
$mensaje = 'Si el correo está registrado, se ha enviado un enlace de recuperación. Revise su bandeja de entrada.';
```

**Pantallazo sugerido:** el correo de recuperación recibido (con el botón "Restablecer contraseña") y la pantalla de "Nueva Contraseña".

---

## 4. Defensa en profundidad: prevención de auto-cambio de contraseña sin verificación

**Archivo:** `app/controllers/UsuariosController.php`, método `guardar()`

Un Administrador puede fijar la contraseña de cualquier otro usuario directamente desde Gestión de Usuarios, sin conocer la contraseña anterior — es un privilegio válido de su rol. Sin embargo, permitir que use ese mismo formulario para cambiar **su propia** contraseña sería una falla de seguridad, ya que evadiría la verificación de contraseña actual que sí exige la pantalla de autogestión.

La solución no se limita a ocultar los campos en la interfaz: el controlador descarta explícitamente cualquier valor de contraseña recibido cuando el registro editado corresponde al usuario que tiene la sesión activa, incluso si la petición HTTP fue manipulada para incluirlo.

```php
$esUsuarioActual = $id && (int) Auth::user()['id_usuario'] === (int) $id;


// "Cambiar contraseña" (confirma la contraseña actual); este formulario
// de Usuarios nunca la modifica para el usuario logueado, aunque se envíe.
$contrasena = $esUsuarioActual ? '' : ($_POST['contrasena'] ?? '');
$confirmar = $esUsuarioActual ? '' : ($_POST['confirmar_contrasena'] ?? '');
```

Esta medida se verificó mediante una prueba de seguridad dedicada (ver Tabla de Pruebas — "Intento de auto-cambio de contraseña desde el formulario de edición de usuario"), simulando una petición POST manipulada con herramientas de desarrollador.

**Pantallazo sugerido:** el formulario de edición del propio usuario, mostrando el aviso "Por seguridad, su propia contraseña se cambia desde una pantalla aparte..." en lugar de los campos de contraseña.

---

## 5. Enrutamiento y saneamiento de parámetros de entrada

**Archivo:** `core/Router.php`

Todas las peticiones pasan por un único punto de entrada (`public/index.php`), que delega en `Router::route()` según los parámetros `c` (controlador) y `a` (acción) de la URL. Antes de usarlos para construir un nombre de clase y una ruta de archivo, ambos se sanean con una expresión regular que solo permite letras, números y guion bajo — evitando que un valor arbitrario en la URL provoque inclusión de archivos no deseados.

```php
class Router {
    public static function route($controller, $action = 'index') {
        $controller = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $controller) ?: 'home';
        $action = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $action) ?: 'index';

        $controllerName = ucfirst($controller) . 'Controller';
        $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            throw new Exception("Controlador no encontrado: {$controllerName}");
        }

        require_once $controllerFile;
        $instance = new $controllerName();

        if (!method_exists($instance, $action)) {
            throw new Exception("Acción no encontrada: {$action}");
        }

        $instance->$action();
    }
}
```

**Pantallazo sugerido:** la pantalla de error 404 al acceder a una ruta inexistente (ej. `?c=noexiste`).

---

## 6. Control de acceso por roles centralizado

**Archivo:** `core/Middleware.php` · **Archivo:** `config/permisos.php`

La decisión de diseño más importante en materia de autorización es tener **una sola fuente de verdad** para "qué rol puede acceder a qué módulo": el arreglo `config/permisos.php`. Tanto el menú lateral (que oculta las opciones no permitidas) como cada controlador (que bloquea el acceso directo por URL) consultan la misma matriz — evitando que ambos se desincronicen.

```php
// config/permisos.php
return [
    'dashboard'  => ['Administrador', 'Tesorero', 'Supervisor', 'Consulta'],
    'ingresos'   => ['Administrador', 'Tesorero', 'Supervisor'],
    'categorias' => ['Administrador'],
    'usuarios'   => ['Administrador'],
    'bitacora'   => ['Administrador', 'Supervisor'],
    // ...
];
```

```php
class Middleware {
    public static function requireRole(array $rolesPermitidos) {
        self::requireAuth();
        $usuario = Auth::user();

        if (!in_array($usuario['rol_nombre'], $rolesPermitidos, true)) {
            http_response_code(403);
            View::render('errores.403', ['usuario' => $usuario]);
            exit;
        }
    }

    public static function requirePermiso($modulo) {
        self::requireRole(Permisos::rolesPermitidos($modulo));
    }
}
```

**Pantallazo sugerido:** la página de error 403 al intentar acceder a un módulo no permitido (ej. un Supervisor entrando a `?c=usuarios&a=index` directamente por URL).

---

## 7. Consultas preparadas y protección de archivos sensibles

**Archivo:** `app/models/Usuario.php` (ejemplo representativo) · **Archivos:** `storage/.htaccess`, `vendor/.htaccess`

Todo acceso a base de datos en el sistema usa PDO con **consultas preparadas**, nunca concatenación de variables dentro del SQL — la medida estándar contra inyección SQL:

```php
public function buscarPorNombreUsuario($nombreUsuario) {
    $stmt = $this->db->prepare(
        'SELECT u.*, r.nombre AS rol_nombre, r.descripcion AS rol_descripcion
         FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.nombre_usuario = ? LIMIT 1'
    );
    $stmt->execute([$nombreUsuario]);
    return $stmt->fetch() ?: null;
}
```

Por otro lado, dado que el proyecto completo reside bajo `htdocs/` (no solo la carpeta `public/`, que sería lo ideal en un hosting de producción), las carpetas `storage/` (comprobantes y documentos subidos por los usuarios) y `vendor/` (dependencias de Composer) se protegen contra acceso directo por URL:

```apache
# storage/.htaccess y vendor/.htaccess
Require all denied
```

**Pantallazo sugerido:** intento de acceder directamente a un archivo dentro de `storage/facturas/` por URL, mostrando el error 403 del servidor Apache.

---

## 8. Cascada de anulación entre Ingresos/Egresos y Facturas vinculadas

**Archivo:** `app/controllers/IngresosController.php`, método `anular()`

Una regla de negocio central del sistema es que un Ingreso o Egreso puede tener un documento (Factura/Recibo) vinculado. Anular el movimiento debe anular también el documento — de lo contrario quedaría un comprobante "vivo" de un movimiento que ya no existe. Este comportamiento se replica de forma simétrica desde Egresos y desde Facturas.

```php
public function anular() {
    Middleware::requireRole(self::ROLES_GESTION);

    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $this->redirect('index.php?c=ingresos&a=index');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $ingresoModel = new Ingreso();
    $registro = $ingresoModel->buscarPorId($id);

    $ingresoModel->cambiarEstado($id, 0);
    Bitacora::registrar('Anular', 'Ingresos', "Anulación de ingreso #{$id}", 'ingresos', $id);

    if ($registro && $registro['id_factura']) {
        $facturaModel = new Factura();
        $facturaModel->cambiarEstado($registro['id_factura'], 0);
        Bitacora::registrar(
            'Anular', 'Facturas',
            "Anulación en cascada del documento {$registro['factura_numero']} por anulación del ingreso #{$id}",
            'facturas', $registro['id_factura']
        );
    }

    Flash::set('success', 'Ingreso anulado correctamente.');
    $this->redirect('index.php?c=ingresos&a=index');
}
```

Nótese que la anulación en cascada genera **dos** entradas independientes en la Bitácora (una por cada tabla afectada), preservando la trazabilidad completa de la operación.

**Pantallazo sugerido:** el cuadro de confirmación que advierte "También se anulará el documento vinculado" antes de anular un ingreso.

---

## 9. Cálculo del disponible en Caja General

**Archivo:** `app/models/Reporte.php`, método `disponibleCajaGeneral()`

Este cálculo es, conceptualmente, el corazón del sistema: cuánto dinero hay disponible en la iglesia en un momento dado. A diferencia de los KPIs mensuales (que se filtran por período), este valor es **histórico acumulado** — la suma de todos los ingresos activos menos la suma de todos los egresos activos desde que existe el sistema — y por eso se muestra siempre en un cuadro visualmente separado de los totales del mes.

```php
public function disponibleCajaGeneral() {
    $totalIngresos = (float) $this->db
        ->query('SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE activo = 1')
        ->fetchColumn();
    $totalEgresos = (float) $this->db
        ->query('SELECT COALESCE(SUM(monto), 0) FROM egresos WHERE activo = 1')
        ->fetchColumn();

    return $totalIngresos - $totalEgresos;
}
```

El filtro `activo = 1` es lo que garantiza que un ingreso o egreso anulado (ver sección anterior) no siga afectando este saldo, sin necesidad de borrar el registro de la base de datos.

**Pantallazo sugerido:** el cuadro verde "Disponible Caja General" visible en el Dashboard y en el listado de Ingresos.

---

## 10. Dashboard interactivo: gráficas dinámicas con Chart.js

**Archivo:** `app/views/home/index.php`

El Dashboard combina cuatro gráficas (barras, dos donas y una línea) construidas con Chart.js a partir de datos calculados en PHP y transmitidos al navegador como JSON. La pieza más interesante desde el punto de vista técnico es un **plugin de Chart.js hecho a la medida** para dibujar el monto total dentro del círculo de las donas, con una función reutilizable en lugar de duplicar la lógica para "Ingresos por Tipo" y "Egresos por Categoría":

```php
crearDonutConTotal(
    'chartDistribucion',
    'etiquetaDistribucion',
    <?= json_encode(array_map('floatval', array_column($distribucionIngresos, 'total'))) ?>,
    <?= json_encode(array_column($distribucionIngresos, 'nombre')) ?>,
    [colorTeal, colorAmber, colorGreen, colorRed, '#7c92a3']
);
```

```javascript
function crearDonutConTotal(canvasId, etiquetaElementId, datos, etiquetas, colores) {
    const total = datos.reduce(function (a, b) { return a + b; }, 0);
    const elementoEtiqueta = document.getElementById(etiquetaElementId);

    const pluginTotalCentro = {
        id: 'totalCentro',
        afterDraw: function (chart) {
            // Determina si el usuario aisló un segmento desde la leyenda,
            // para mostrar Total / Subtotal / el nombre puntual según el caso.
            const indicesVisibles = [];
            datos.forEach(function (_, i) {
                if (chart.getDataVisibility(i)) { indicesVisibles.push(i); }
            });

            let etiqueta = 'Total';
            let suma = total;
            if (indicesVisibles.length === 1) {
                etiqueta = etiquetas[indicesVisibles[0]];
                suma = datos[indicesVisibles[0]];
            } else if (indicesVisibles.length < datos.length) {
                etiqueta = 'Subtotal';
                suma = indicesVisibles.reduce((acc, i) => acc + datos[i], 0);
            }

            elementoEtiqueta.textContent = etiqueta; // fuera de la dona, en el DOM
            const ctx = chart.ctx;
            const cx = (chart.chartArea.left + chart.chartArea.right) / 2;
            const cy = (chart.chartArea.top + chart.chartArea.bottom) / 2;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = 'bold 13px "Segoe UI", sans-serif';
            ctx.fillText(fmtQCompleto(suma), cx, cy);
        },
    };

    return new Chart(document.getElementById(canvasId), {
        type: 'doughnut',
        data: { labels: etiquetas, datasets: [{ data: datos, backgroundColor: colores }] },
        plugins: [pluginTotalCentro],
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%' },
    });
}
```

Este diseño permite que, al dar clic en un elemento de la leyenda para ocultarlo, la cifra central se recalcule automáticamente sin escribir código adicional — Chart.js dispara `afterDraw` en cada redibujado, y la función ya contempla los tres estados posibles (total, subtotal, segmento único).

**Pantallazo sugerido:** el Dashboard completo con sus cuatro gráficas, e idealmente una segunda captura mostrando una dona con un segmento aislado (leyenda) para evidenciar el cambio dinámico de "Total" a "Subtotal".

---

*Nota: los fragmentos de código anteriores corresponden al estado actual del repositorio. Al insertar las capturas de pantalla, verificar que los datos visibles (montos, nombres de usuario) sean consistentes entre las imágenes y coincidan con el ambiente de pruebas mostrado en la Tabla de Pruebas del Sistema.*
