<?php
/**
 * HASHIP PROJECT - Módulo de Conexión y Autoconfiguración (PDO)
 * Autor: Nico (Lead Developer)
 * Versión: 2.1 (Sincronizado con Schema 2.0)
 * * DESCRIPCIÓN:
 * Este archivo gestiona la persistencia de datos mediante la interfaz PDO.
 * Además de la conexión, implementa una rutina de inicialización automática
 * para garantizar que las tablas de auditoría y evidencias existan en el sistema.
 */

// Configuración de parámetros del servidor (Entorno Local XAMPP)
$host    = 'localhost';
$db      = 'haship_db';
$user    = 'root';           // Usuario administrativo por defecto
$pass    = '';               // Contraseña vacía (Entorno de desarrollo)
$charset = 'utf8mb4';        // Codificación recomendada para seguridad y compatibilidad

// DSN (Data Source Name): Cadena de conexión estandarizada
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

/**
 * CONFIGURACIÓN DE ATRIBUTOS PDO:
 * 1. ATTR_ERRMODE: Lanza excepciones para capturar errores con bloques try-catch.
 * 2. DEFAULT_FETCH_MODE: Devuelve los resultados como arrays asociativos.
 * 3. EMULATE_PREPARES: Desactivado para forzar consultas preparadas nativas.
 */
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    /**
     * INSTANCIACIÓN Y CONEXIÓN:
     * El objeto $pdo es el túnel seguro hacia la infraestructura de datos.
     */
    $pdo = new PDO($dsn, $user, $pass, $options);

    /**
     * PROTOCOLO DE AUTO-INSTALACIÓN (BOOTSTRAPPING):
     * Verificamos la existencia de la arquitectura de tablas según el diseño 2.0.
     * Esto permite una portabilidad total del proyecto sin scripts SQL externos.
     */

    // 1. GESTIÓN DE IDENTIDADES (Usuarios con roles específicos)
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        rol ENUM('administrador', 'remitente', 'destinatario') DEFAULT 'destinatario' NOT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 2. ALMACENAMIENTO DE ACTIVOS DIGITALES (Documentos y Hash SHA-256)
    $pdo->exec("CREATE TABLE IF NOT EXISTS documentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_real VARCHAR(255) NOT NULL,
        nombre_almacenado VARCHAR(255) NOT NULL,
        id_propietario INT NOT NULL,
        id_destinatario INT NULL,
        mensaje_contexto TEXT,
        estado ENUM('pendiente', 'validado', 'rechazado', 'expirado') DEFAULT 'pendiente' NOT NULL,
        fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_validacion DATETIME NULL,
        hash_seguridad CHAR(64) NOT NULL,
        FOREIGN KEY (id_propietario) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (id_destinatario) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    // 3. PISTA DE AUDITORÍA (Evidencias técnicas para peritaje)
    $pdo->exec("CREATE TABLE IF NOT EXISTS evidencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_documento INT NOT NULL,
        id_usuario INT NULL,
        evento ENUM('SUBIDA', 'VISUALIZACION', 'FIRMA', 'RECHAZO', 'VERIFICACION_INTEGRIDAD') NOT NULL,
        ip_origen VARCHAR(45) NOT NULL,
        navegador_info TEXT,
        fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_documento) REFERENCES documentos(id) ON DELETE CASCADE,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    /**
     * SEMILLAS DE SEGURIDAD (Seeders):
     * Inyectamos el usuario administrador inicial si el sistema está vacío.
     * Credenciales: admin@haship.com / admin123
     */
    $stmtCheck = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmtCheck->fetchColumn() == 0) {
        $passHash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)")
            ->execute(['Nico Administrador', 'admin@haship.com', $passHash, 'administrador']);
    }

} catch (\PDOException $e) {
    /**
     * MANEJO DE EXCEPCIONES:
     * Capturamos errores de red, permisos o sintaxis SQL, deteniendo la carga
     * para evitar inconsistencias en el flujo de firma.
     */
    die("Error crítico de infraestructura: " . $e->getMessage());
}
?>