<?php
/**
 * HASHIP PROJECT - Módulo de Conexión a Base de Datos (PDO)
 * Autor: Nico (Lead Developer)
 * Versión: 1.0
 * * DESCRIPCIÓN:
 * Este archivo gestiona la persistencia de datos mediante la interfaz PDO.
 * Se ha configurado para ser altamente seguro y eficiente, utilizando
 * el set de caracteres utf8mb4 para soportar cualquier símbolo técnico.
 */

// Configuración de parámetros del servidor (Entorno Local XAMPP)
$host    = 'localhost';
$db      = 'haship_db';
$user    = 'root';          // Usuario administrativo por defecto
$pass    = '';              // Contraseña vacía (Entorno de desarrollo)
$charset = 'utf8mb4';       // Codificación recomendada para seguridad y compatibilidad

// DSN (Data Source Name): Cadena de conexión estandarizada
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

/**
 * CONFIGURACIÓN DE ATRIBUTOS PDO:
 * 1. ATTR_ERRMODE: Lanza excepciones para capturar errores con bloques try-catch.
 * 2. DEFAULT_FETCH_MODE: Devuelve los resultados como arrays asociativos.
 * 3. EMULATE_PREPARES: Desactivado para forzar consultas preparadas nativas de MySQL,
 * eliminando el riesgo de Inyección SQL.
 */
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    /**
     * Instanciación del objeto $pdo.
     * Este objeto será compartido por todo el backend (Auth, Upload, Sign)
     * para realizar operaciones CRUD sobre la base de datos.
     */
    $pdo = new PDO($dsn, $user, $pass, $options);
    
} catch (\PDOException $e) {
    /**
     * MANEJO DE EXCEPCIONES:
     * En caso de fallo en la conexión, detenemos la ejecución y registramos 
     * el error técnico. Esto previene que la aplicación intente ejecutar
     * lógica de negocio sobre una conexión nula.
     */
    die("Error crítico de conexión: " . $e->getMessage());
}
?>