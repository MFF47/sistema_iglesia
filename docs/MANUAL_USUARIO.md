# Manual de Usuario
## Sistema de Control Financiero y Administrativo — Iglesia Centroamericana Nueva Vida

Este manual describe los módulos ya construidos y cómo usarlos. Corresponde al avance hasta el módulo de Bitácora (pendiente solo la pantalla de Configuración, sin mockup de referencia, y recuperación de contraseña).

**Demo funcional:** 27 de septiembre de 2026.

---

## 1. Ingreso al sistema (Login)

**URL local:** `http://localhost/sistema_iglesia/public/index.php?c=auth&a=login`

1. Ingrese su **usuario** y **contraseña**.
2. Si los datos son incorrectos, el sistema muestra un mensaje genérico ("Usuario o contraseña incorrectos") sin indicar cuál campo falló, por seguridad.
3. Después de **5 intentos fallidos consecutivos**, la cuenta se bloquea automáticamente. Debe contactar a un Administrador para desbloquearla.
4. Para salir, use **Cerrar sesión** en la esquina superior derecha (visible en todas las pantallas).

> "¿Olvidó su contraseña?" todavía no está implementado (mockups 02 y 03, pendientes).

### Usuarios de prueba disponibles

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `Admin1234!` | Administrador |
| `tesorero1` | `Tester123!` | Tesorero |
| `supervisor1` | `Tester123!` | Supervisor |
| `consulta1` | `Tester123!` | Consulta |

Estos usuarios se crearon con scripts temporales (`tmp_create_admin.php`, `tmp_create_test_users.php`) para poder probar el sistema. Se recomienda **cambiar sus contraseñas o eliminarlos** antes de usar el sistema con datos reales de la iglesia.

---

## 2. Roles y qué puede hacer cada uno

| Módulo | Administrador | Tesorero | Supervisor | Consulta |
|---|:---:|:---:|:---:|:---:|
| Dashboard | Ver | Ver | Ver | Ver |
| Ingresos | Gestionar | Gestionar | Solo ver | Sin acceso |
| Egresos | Gestionar | Gestionar | Solo ver | Sin acceso |
| Facturas y Recibos | Gestionar | Gestionar | Solo ver | Sin acceso |
| Categorías | Gestionar | Sin acceso | Sin acceso | Sin acceso |
| Usuarios y Roles | Gestionar | Sin acceso | Sin acceso | Sin acceso |
| Reportería | Usar | Usar | Usar | Usar |
| Bitácora | Ver | Sin acceso | Ver | Sin acceso |

"Gestionar" incluye crear, editar y anular. "Solo ver"/"Ver" significa que el módulo aparece en el menú y se puede consultar, pero no aparecen los botones para crear, editar o anular. El menú lateral (sidebar) muestra automáticamente solo las opciones permitidas para el rol de la sesión activa.

---

## 3. Dashboard

Pantalla de inicio tras iniciar sesión (`Dashboard` en el menú).

- **Selector de período**: Mes actual, Mes anterior o Año actual. Al cambiarlo y presionar "Actualizar", se recalculan todos los indicadores y gráficas.
- **Tarjetas KPI**: Total Ingresos, Total Egresos, Balance del Período (superávit o déficit) y Movimientos totales, cada una comparada contra el período anterior equivalente (↑/↓ %).
- **Gráfica "Ingresos vs Egresos por Mes"**: barras de los últimos 6 meses.
- **Gráfica "Distribución de Ingresos por Tipo"**: dona con el peso de Diezmos, Ofrendas, Donaciones y Actividades Especiales.
- **Gráfica "Comparativo Mensual del Año"**: balance (ingresos − egresos) mes a mes del año en curso.

---

## 4. Usuarios y Roles *(solo Administrador)*

Menú: **Gestión → Usuarios y Roles**.

- **Listado**: buscar por nombre/usuario/correo, filtrar por rol y por estado (Activo/Inactivo), con paginación.
- **+ Nuevo Usuario**: complete nombre de usuario, nombre completo, correo, teléfono (opcional), contraseña (mínimo 8 caracteres) y confirmación, rol y estado.
- **Editar** (ícono de lápiz): igual que crear, pero la contraseña es opcional — si se deja en blanco, la contraseña actual no cambia.
- **Activar/Desactivar** (ícono circular): cambia el estado del usuario. No puede desactivar su propio usuario mientras tiene la sesión abierta.

---

## 5. Categorías *(solo Administrador)*

Menú: **Gestión → Categorías**. Clasifican todos los ingresos y egresos del sistema.

- **Listado**: buscar por nombre, filtrar por tipo (Ingreso/Egreso) y estado. La columna **Movimientos** muestra cuántos ingresos o egresos ya usan esa categoría.
- **+ Nueva Categoría**: nombre, tipo, descripción (opcional), estado.
- No se permite un nombre duplicado dentro del mismo tipo (puede repetirse el nombre entre una categoría de ingreso y una de egreso).
- **No se pueden eliminar** — solo desactivar, para no perder el historial de los movimientos que ya las usan.

---

## 6. Ingresos *(Administrador y Tesorero gestionan; Supervisor solo consulta)*

Menú: **Movimientos → Ingresos**.

- **KPIs del mes**: total, cantidad de registros y promedio diario.
- **Filtros**: rango de fechas, categoría, tipo de ingreso.
- **+ Nuevo Ingreso**:
  - Fecha, **Tipo de Ingreso** (Diezmo, Ofrenda, Donación Especial, Actividad Especial), Categoría, Monto, Actividad asociada (opcional), Descripción (opcional).
  - **Aportante**: opcional en general, pero **obligatorio para Diezmo y Donación Especial**. Puede elegir uno ya registrado o crear uno nuevo sin salir del formulario con el botón **"+ Registrar Nuevo Aportante"**.
  - **Comprobante**: opcional, PDF/JPG/PNG hasta 3 MB. Se puede arrastrar el archivo a la zona marcada o hacer clic para elegirlo.
- En el listado, el ícono de ojo bajo "Comprobante" abre el archivo adjunto (si existe).
- **Anular**: oculta el ingreso del listado y de todos los totales/KPIs. El registro no se borra de la base de datos (queda para auditoría en la Bitácora), pero ya no puede reactivarse desde la pantalla.

---

## 7. Egresos *(mismo esquema de permisos que Ingresos)*

Menú: **Movimientos → Egresos**.

- **KPIs del mes**: total, cantidad de registros y monto promedio.
- **Filtros**: rango de fechas, categoría, beneficiario.
- **+ Nuevo Egreso**: fecha, categoría, monto, **Beneficiario** (obligatorio) y **Descripción** (obligatoria), Observaciones (opcional).
- **Vincular Factura (opcional)**: elija una factura ya registrada, o cree una nueva con el botón **"+ Registrar Nueva Factura"** sin salir del formulario.
- **Anular**: igual que en Ingresos, oculta el registro de listados y totales sin borrarlo.

---

## 8. Facturas y Recibos *(mismo esquema de permisos)*

Menú: **Movimientos → Facturas y Recibos**. Son los documentos que respaldan los ingresos/egresos (Factura, Recibo o Nota de Crédito).

- **Filtros**: buscar por número de documento o beneficiario, tipo de documento, rango de fechas de emisión.
- **+ Nueva Factura**: número de documento, tipo, fecha de emisión, monto, beneficiario/emisor, descripción (opcional).
- **Archivo del documento**: **obligatorio al crear** (PDF/JPG/PNG hasta 5 MB). Al editar es opcional — si no se sube uno nuevo, se conserva el archivo original.
- **Vincular a Egreso (opcional)**: asocia el documento a un egreso ya existente; la columna "Vinculado a" del listado muestra a qué egreso pertenece.
- **Anular**: mismo comportamiento que en los demás módulos.

---

## 9. Reportería *(todos los roles, incluido Consulta)*

Menú: **Reportes → Reportería**.

1. Elija **Tipo de Reporte** (Ingresos o Egresos), opcionalmente una **Categoría**, y el rango de fechas (**Desde/Hasta**, obligatorios).
2. Presione **Generar** para ver la vista previa: KPIs (Total del período, Registros, Promedio) y la tabla detallada, con el total al pie.
3. **Exportar Excel**: descarga un archivo `.csv` (se abre directamente en Excel, con acentos correctos).
4. **Exportar PDF**: abre una vista de impresión limpia en una pestaña nueva; en el diálogo de impresión del navegador, elija **"Guardar como PDF"** como destino.

---

## 10. Bitácora *(Administrador y Supervisor)*

Menú: **Reportes → Bitácora**. Registro de auditoría de todo lo que ocurre en el sistema.

- Registra automáticamente: inicios de sesión (exitosos y fallidos), creación/modificación/anulación de usuarios, categorías, ingresos, egresos y facturas, y cada exportación realizada desde Reportería o desde la propia Bitácora.
- **Es de solo lectura**: ningún evento puede modificarse ni eliminarse, para garantizar la integridad de la auditoría.
- **Filtros**: rango de fecha/hora, usuario, módulo, acción.
- **Exportar Bitácora**: descarga el registro filtrado en CSV.

---

## Notas para quien administre el sistema

- Los comprobantes e imágenes de facturas se guardan en `storage/adjuntos/` y `storage/facturas/`, **fuera del acceso público** (protegidos con `.htaccess`); solo se pueden ver a través del sistema, con sesión iniciada y permiso sobre el módulo correspondiente.
- Los archivos `tmp_create_admin.php` y `tmp_create_test_users.php` son herramientas de un solo uso para crear los usuarios iniciales. Pueden borrarse cuando ya no se necesiten.
- **Pendiente** (no bloqueante para la demo): pantalla de Configuración del sistema (la tabla existe en la base de datos pero no tiene mockup de referencia) y recuperación de contraseña por correo.
