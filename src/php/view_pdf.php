<?php
/**
 * HASHIP PROJECT - Protocolo de Visualización Segura
 * Autor: Nico (Lead Developer)
 * Versión: 1.2 (Optimización de Streaming y Control de Metadatos)
 * * DESCRIPCIÓN: Abstracción de activos físicos. Valida la sesión y el rol 
 * antes de servir el flujo binario del PDF para evitar Hotlinking y
 * asegurar que el visor reciba el tamaño exacto del buffer.
 */

require_once 'db.php';
require_once 'auth.php';

// 1. Validación de Identidad (Handshake de Sesión)
checkAuth();

// SINCRONIZACIÓN: Usamos las claves exactas definidas en auth.php
$usuario_id  = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['rol']; 
$doc_id      = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$doc_id) {
    header("HTTP/1.1 400 Bad Request");
    die("ERROR_ID: Identificador de activo no proporcionado.");
}

/**
 * 2. LÓGICA DE AUTORIZACIÓN (Access Control List):
 * El sistema verifica si el solicitante tiene permisos de lectura sobre el binario.
 */
$stmt = $pdo->prepare("SELECT nombre_almacenado, id_propietario, id_destinatario FROM documentos WHERE id = ?");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    header("HTTP/1.1 404 Not Found");
    die("ERROR_NOT_FOUND: El activo no existe en el nodo.");
}

// Verificación de privilegios mediante matriz de acceso
$es_propietario  = ($doc['id_propietario'] == $usuario_id);
$es_destinatario = ($doc['id_destinatario'] == $usuario_id);
$es_admin         = ($usuario_rol === 'administrador');

if ($es_propietario || $es_destinatario || $es_admin) {
    
    // Ajuste de ruta absoluta para acceso al almacenamiento protegido
    $ruta_archivo = "../../almacenamiento/uploads/" . $doc['nombre_almacenado'];

    if (file_exists($ruta_archivo)) {
        /**
         * 3. CABECERAS DE TRANSMISIÓN SEGURA Y STREAMING
         * Configuramos el flujo para que el navegador lo renderice como un activo inmutable.
         */
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="haship_audit_'. $doc_id .'.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        
        // Sincronización de tamaño: Permite al visor mostrar progreso de carga real
        header('Content-Length: ' . filesize($ruta_archivo));
        
        // Protocolos Anti-Caché: Garantiza que la auditoría sea siempre sobre el archivo actual
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        /**
         * 4. LIMPIEZA DE BÚFER Y TRANSMISIÓN BINARIA
         * Eliminamos cualquier residuo en el búfer de salida para evitar corrupción del PDF.
         */
        if (ob_get_length()) ob_clean();
        flush();
        
        // Volcado directo del archivo al flujo de salida (Stream)
        readfile($ruta_archivo);
        exit;

    } else {
        header("HTTP/1.1 500 Internal Server Error");
        error_log("CRITICAL_FILESYSTEM_MISSING: ID " . $doc_id);
        die("ERROR_FILESYSTEM: El archivo físico no se encuentra en el repositorio.");
    }

} else {
    // Intento de violación de privilegios (Unauthorized access)
    header("HTTP/1.1 403 Forbidden");
    die("ERROR_UNAUTHORIZED: El nodo denegó el acceso por falta de privilegios.");
}