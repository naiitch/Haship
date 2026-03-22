/**
 * HASHIP PROJECT - Cimientos de Base de Datos Relacional
 * Autor: Nico (Lead Developer)
 * Versión: 1.0
 * * DESCRIPCIÓN:
 * Este script inicializa el entorno de persistencia de datos para Haship.
 * Implementa una arquitectura de Audit Trail (pista de auditoría) para
 * garantizar la integridad de los documentos y la trazabilidad de las firmas.
 */

-- ==========================================================
-- 1. ENTORNO Y LIMPIEZA
-- ==========================================================
CREATE DATABASE IF NOT EXISTS haship_db;
USE haship_db;

-- Desactivamos cheques de integridad temporalmente para limpieza profunda
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
  password VARCHAR(255) NOT NULL, -- Almacenado mediante BCRYPT (PHP password_hash)
  rol ENUM('administrador', 'cliente') DEFAULT 'cliente' NOT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==========================================================
-- 3. ALMACENAMIENTO DE ACTIVOS DIGITALES (Documentos)
-- ==========================================================
CREATE TABLE documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_real VARCHAR(255) NOT NULL,       -- Nombre original del archivo subido
  nombre_almacenado VARCHAR(255) NOT NULL, -- Nombre ofuscado en el servidor (bin2hex)
  id_propietario INT NOT NULL,             -- Usuario que sube el documento (RRHH/Admin)
  id_destinatario INT NULL,                -- Usuario asignado para la firma
  estado ENUM('pendiente', 'validado', 'rechazado') DEFAULT 'pendiente' NOT NULL,
  fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  /**
   * HASH_SEGURIDAD:
   * Usamos CHAR(64) porque el algoritmo SHA-256 genera siempre una cadena
   * de longitud fija. Esto optimiza el rendimiento de búsqueda e indexación.
   */
  hash_seguridad CHAR(64) NOT NULL,
  
  -- Integridad Referencial: Si se borra un propietario, sus docs desaparecen (CASCADE)
  -- Si se borra un destinatario, el doc permanece pero queda huérfano (SET NULL)
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
  evento ENUM('SUBIDA', 'VISUALIZACION', 'FIRMA', 'RECHAZO', 'VERIFICACION_INTEGRIDAD') NOT NULL,
  ip_origen VARCHAR(45) NOT NULL, -- Soporta IPv4 e IPv6 (máx 45 caracteres)
  navegador_info TEXT,            -- User-Agent completo para peritaje técnico
  fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (id_documento) REFERENCES documentos(id) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 5. AJUSTES DE SISTEMA (Configuración)
-- ==========================================================
CREATE TABLE configuracion_empresa (
  id INT PRIMARY KEY,
  nombre_empresa VARCHAR(255),
  logo_ruta VARCHAR(255),
  notificaciones_email BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- ==========================================================
-- 6. SEMILLAS (Seeders) - DATOS DE PRUEBA
-- ==========================================================
-- Credenciales de Acceso: admin@haship.com | Contraseña: admin123
INSERT INTO usuarios (nombre, email, password, rol) 
VALUES (
  'Nico Administrador', 
  'admin@haship.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Hash de admin123
  'administrador'
);