-- ============================================================================
-- Sistema de Control Financiero y Administrativo
-- Iglesia Centroamericana Nueva Vida
--
-- Script de instalación limpio: crea las 12 tablas del esquema y siembra
-- únicamente los catálogos estructurales que el código necesita para
-- funcionar (roles y tipos de ingreso). NO incluye usuarios, categorías,
-- ingresos, egresos, facturas, bitácora ni ningún otro dato de prueba.
--
-- Uso: importar este archivo completo en una base de datos vacía (phpMyAdmin
-- > Importar, o `mysql nombre_bd < instalacion.sql`).
--
-- Después de importar:
--   1. Cree el primer usuario Administrador con sql/crear_administrador.php
--      (ver instrucciones en ese archivo).
--   2. Inicie sesión y cree, desde Gestión → Categorías, las categorías de
--      ingreso/egreso reales de la iglesia (no vienen precargadas a propósito,
--      para no imponer un catálogo de ejemplo).
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- roles
-- ----------------------------------------------------------------------------
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- tipos_ingreso
-- ----------------------------------------------------------------------------
CREATE TABLE `tipos_ingreso` (
  `id_tipo_ingreso` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `requiere_aportante` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tipo_ingreso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- categorias (vacía — se llena desde la aplicación, Gestión → Categorías)
-- ----------------------------------------------------------------------------
CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_categoria`),
  KEY `idx_categorias_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- actividades
-- ----------------------------------------------------------------------------
CREATE TABLE `actividades` (
  `id_actividad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `meta_recaudacion` decimal(12,2) DEFAULT NULL,
  `estado` enum('planificada','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'planificada',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- aportantes
-- ----------------------------------------------------------------------------
CREATE TABLE `aportantes` (
  `id_aportante` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) NOT NULL,
  `documento_identidad` varchar(30) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_aportante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- usuarios
-- ----------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `intentos_fallidos` tinyint(4) NOT NULL DEFAULT 0,
  `bloqueado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_bloqueo` datetime DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `fk_usuarios_rol` (`id_rol`),
  KEY `idx_usuarios_correo` (`correo`),
  KEY `idx_usuarios_activo` (`activo`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- facturas
-- ----------------------------------------------------------------------------
CREATE TABLE `facturas` (
  `id_factura` int(11) NOT NULL AUTO_INCREMENT,
  `numero_documento` varchar(50) DEFAULT NULL,
  `tipo_documento` enum('factura','recibo','nota_credito') NOT NULL DEFAULT 'factura',
  `beneficiario` varchar(150) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `ruta_archivo` varchar(500) DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_factura`),
  KEY `fk_facturas_usuario` (`id_usuario_registro`),
  KEY `idx_facturas_fecha` (`fecha_emision`),
  CONSTRAINT `fk_facturas_usuario` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- ingresos
-- ----------------------------------------------------------------------------
CREATE TABLE `ingresos` (
  `id_ingreso` int(11) NOT NULL AUTO_INCREMENT,
  `id_categoria` int(11) NOT NULL,
  `id_tipo_ingreso` int(11) NOT NULL,
  `id_aportante` int(11) DEFAULT NULL,
  `id_actividad` int(11) DEFAULT NULL,
  `id_factura` int(11) DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ingreso`),
  KEY `fk_ingresos_categoria` (`id_categoria`),
  KEY `fk_ingresos_tipo_ingreso` (`id_tipo_ingreso`),
  KEY `fk_ingresos_aportante` (`id_aportante`),
  KEY `fk_ingresos_actividad` (`id_actividad`),
  KEY `fk_ingresos_usuario` (`id_usuario_registro`),
  KEY `idx_ingresos_fecha` (`fecha_ingreso`),
  KEY `idx_ingresos_activo` (`activo`),
  KEY `fk_ingresos_factura` (`id_factura`),
  CONSTRAINT `fk_ingresos_actividad` FOREIGN KEY (`id_actividad`) REFERENCES `actividades` (`id_actividad`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ingresos_aportante` FOREIGN KEY (`id_aportante`) REFERENCES `aportantes` (`id_aportante`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ingresos_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ingresos_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE SET NULL,
  CONSTRAINT `fk_ingresos_tipo_ingreso` FOREIGN KEY (`id_tipo_ingreso`) REFERENCES `tipos_ingreso` (`id_tipo_ingreso`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ingresos_usuario` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- egresos
-- ----------------------------------------------------------------------------
CREATE TABLE `egresos` (
  `id_egreso` int(11) NOT NULL AUTO_INCREMENT,
  `id_categoria` int(11) NOT NULL,
  `id_factura` int(11) DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `fecha_egreso` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `beneficiario` varchar(150) DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_egreso`),
  KEY `fk_egresos_categoria` (`id_categoria`),
  KEY `fk_egresos_factura` (`id_factura`),
  KEY `fk_egresos_usuario` (`id_usuario_registro`),
  KEY `idx_egresos_fecha` (`fecha_egreso`),
  KEY `idx_egresos_activo` (`activo`),
  CONSTRAINT `fk_egresos_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_egresos_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_egresos_usuario` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- adjuntos
-- ----------------------------------------------------------------------------
CREATE TABLE `adjuntos` (
  `id_adjunto` int(11) NOT NULL AUTO_INCREMENT,
  `tabla_origen` enum('ingresos','egresos','facturas','actividades','aportantes') NOT NULL,
  `id_origen` int(11) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_almacenado` varchar(255) NOT NULL,
  `ruta` varchar(500) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano_bytes` bigint(20) DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_adjunto`),
  KEY `fk_adjuntos_usuario` (`id_usuario_registro`),
  KEY `idx_adjuntos_origen` (`tabla_origen`,`id_origen`),
  CONSTRAINT `fk_adjuntos_usuario` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- bitacora
-- ----------------------------------------------------------------------------
CREATE TABLE `bitacora` (
  `id_bitacora` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `nombre_usuario` varchar(50) DEFAULT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `id_registro_afectado` int(11) DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `fecha_evento` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_bitacora`),
  KEY `fk_bitacora_usuario` (`id_usuario`),
  KEY `idx_bitacora_fecha` (`fecha_evento`),
  KEY `idx_bitacora_modulo` (`modulo`),
  CONSTRAINT `fk_bitacora_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- sesiones (login persistente y tokens de recuperación de contraseña)
-- ----------------------------------------------------------------------------
CREATE TABLE `sesiones` (
  `id_sesion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `tipo` enum('login','recuperacion_password') NOT NULL DEFAULT 'login',
  `ip_origen` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_sesiones_usuario` (`id_usuario`),
  KEY `idx_sesiones_token` (`token`),
  KEY `idx_sesiones_tipo` (`tipo`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Semillas estructurales (requeridas por el código, no son datos de prueba)
-- ============================================================================

INSERT INTO `roles` (`nombre`, `descripcion`) VALUES
  ('Administrador', 'Acceso completo al sistema'),
  ('Tesorero', 'Gestión de ingresos, egresos y facturas'),
  ('Supervisor', 'Consulta y supervisión, incluye bitácora'),
  ('Consulta', 'Acceso de solo lectura a reportes y dashboard');

INSERT INTO `tipos_ingreso` (`nombre`, `descripcion`, `requiere_aportante`) VALUES
  ('Diezmo', 'Diezmo regular de un miembro identificado', 1),
  ('Ofrenda', 'Ofrenda general de culto, normalmente colectiva', 0),
  ('Donación Especial', 'Donación dirigida a un propósito específico', 1),
  ('Actividad Especial', 'Ingreso proveniente de una actividad o evento', 0);
