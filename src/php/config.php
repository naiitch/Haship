<?php
/**
 * HASHIP PROJECT - Configuración Global de Rutas y Constantes
 */

define('APP_NAME', 'Haship Project');
define('UPLOAD_DIR', __DIR__ . '/../../almacenamiento/uploads/');
define('PYTHON_EXEC', 'python'); // O 'python3' según el servidor
define('HASHER_SCRIPT', __DIR__ . '/../python/hasher.py');

// Configuración de visualización de errores (desactivar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);