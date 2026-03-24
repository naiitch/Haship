/**
 * HASHIP PROJECT - Infraestructura de Datos Relacional
 * Autor: Nico (Lead Developer)
 * Versión: 2.3 (Limpieza de Hashes y Sincronización)
 */

-- ==========================================================
-- 1. ENTORNO Y LIMPIEZA
-- ==========================================================
CREATE DATABASE IF NOT EXISTS haship_db;
USE haship_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS evidencias;
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS configuracion_empresa;
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- 2. GESTIÓN DE IDENTIDADES (Usuarios)
-- ==========================================================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL, 
  rol ENUM('administrador', 'remitente', 'destinatario') DEFAULT 'destinatario' NOT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==========================================================
-- 3. ALMACENAMIENTO DE ACTIVOS DIGITALES (Documentos)
-- ==========================================================
CREATE TABLE documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_real VARCHAR(255) NOT NULL,
  nombre_almacenado VARCHAR(255) NOT NULL,
  id_propietario INT NOT NULL, 
  id_destinatario INT NULL,
  mensaje_contexto TEXT, 
  estado ENUM('pendiente', 'validado', 'rechazado', 'expirado') DEFAULT 'pendiente' NOT NULL,
  fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_validacion DATETIME NULL,
  hash_seguridad CHAR(64) NOT NULL, -- SHA-256
  
  FOREIGN KEY (id_propietario) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (id_destinatario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 4. PISTA DE AUDITORÍA (Evidencias)
-- ==========================================================
CREATE TABLE evidencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_documento INT NOT NULL,
  id_usuario INT NULL, 
  evento ENUM('SUBIDA', 'VISUALIZACION', 'FIRMA', 'RECHAZO', 'LOGIN', 'VERIFICACION_INTEGRIDAD') NOT NULL,
  ip_origen VARCHAR(45) NOT NULL,
  navegador_info TEXT,
  fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (id_documento) REFERENCES documentos(id) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 5. AJUSTES DE SISTEMA (Configuración)
-- ==========================================================
CREATE TABLE configuracion_empresa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_empresa VARCHAR(255) DEFAULT 'Haship Corp',
  logo_ruta VARCHAR(255) DEFAULT 'assets/img/logo_transparente.png',
  notificaciones_email BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- ==========================================================
-- 6. SEMILLAS (Seeders) - ACCESO: admin123
-- ==========================================================

INSERT INTO usuarios (nombre, email, password, rol) 
VALUES 
('Nico Administrador', 'admin@haship.com', '$2y$10$7R0p/N8pL9YI0E6.6.v8IO7Bv9n6jLz6fUfH.h9E7O3.WfG8h.hK.', 'administrador'),
('Empresa Remitente', 'remitente@haship.com', '$2y$10$7R0p/N8pL9YI0E6.6.v8IO7Bv9n6jLz6fUfH.h9E7O3.WfG8h.hK.', 'remitente'),
('Cliente Destinatario', 'cliente@haship.com', '$2y$10$7R0p/N8pL9YI0E6.6.v8IO7Bv9n6jLz6fUfH.h9E7O3.WfG8h.hK.', 'destinatario');

INSERT INTO configuracion_empresa (nombre_empresa) VALUES ('Haship - Certificación de Integridad');

-- ==========================================================
-- 7. ACTIVACIÓN/DESACTIVACIÓN DE NODOS
-- ==========================================================

ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) DEFAULT 1;