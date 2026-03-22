<?php
/**
 * HASHIP PROJECT - Gestor de Ingesta y Certificación de Documentos
 * Autor: Nico (Lead Developer)
 * Versión: 1.0
 * * DESCRIPCIÓN:
 * Este módulo gestiona el ciclo de vida inicial de una evidencia: 
 * recepción del binario, almacenamiento seguro, invocación del motor de 
 * hashing externo (Python) y registro de trazabilidad en la base de datos.
 */

session_start();
require_once 'db.php';

// CONTROL DE ACCESO: Solo usuarios autenticados pueden realizar cargas
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado: Se requiere sesión activa.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['documento'])) {
    $file = $_FILES['documento'];
    $nombre_real = $file['name'];
    $ext = strtolower(pathinfo($nombre_real, PATHINFO_EXTENSION));

    // 1. VALIDACIÓN DE FORMATO: Restricción estricta a PDF para integridad documental
    if ($ext !== 'pdf') {
        header("Location: ../../public/dashboard.php?error=formato");
        exit();
    }

    /**
     * 2. OFUSCACIÓN DE ALMACENAMIENTO:
     * Generamos un nombre pseudoaleatorio criptográficamente seguro (20 caracteres).
     * Esto previene ataques de enumeración de archivos y colisiones de nombres.
     */
    $nombre_almacenado = bin2hex(random_bytes(10)) . ".pdf";
    $directorio_destino = "../../almacenamiento/uploads/";
    $ruta_final = $directorio_destino . $nombre_almacenado;

    // Gestión de infraestructura de directorios
    if (!is_dir($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }

    // Persistencia física del archivo en el servidor
    if (move_uploaded_file($file['tmp_name'], $ruta_final)) {

        /**
         * 3. INTEROPERABILIDAD PHP-PYTHON:
         * Invocamos el script 'hasher.py' para procesar el archivo.
         * Utilizamos escapeshellarg() para sanitizar la ruta y prevenir
         * inyecciones de comandos en el shell del servidor.
         */
        $ruta_script_python = "../python/hasher.py";
        $comando = "python " . escapeshellarg($ruta_script_python) . " " . escapeshellarg($ruta_final);
        
        // shell_exec captura la salida estándar (el hash SHA-256) generada por Python
        $hash_generado = trim(shell_exec($comando));

        if (empty($hash_generado)) {
            $hash_generado = "HASH_ERROR_LOG"; // Fallback en caso de fallo en el motor
        }

        /**
         * 4. TRANSACCIÓN ATÓMICA:
         * Garantizamos que el registro del documento y su evidencia de auditoría
         * se realicen como una única unidad de trabajo.
         */
        try {
            $pdo->beginTransaction();

            // Registro de metadatos del activo digital
            $stmt = $pdo->prepare("INSERT INTO documentos (nombre_real, nombre_almacenado, id_propietario, hash_seguridad, estado) VALUES (?, ?, ?, ?, 'pendiente')");
            $stmt->execute([$nombre_real, $nombre_almacenado, $_SESSION['usuario_id'], $hash_generado]);

            /**
             * 5. AUDIT TRAIL (Pista de Auditoría):
             * Capturamos la IP y el User Agent para vincular legalmente la subida
             * con un entorno técnico específico.
             */
            $doc_id = $pdo->lastInsertId();
            $stmt_ev = $pdo->prepare("INSERT INTO evidencias (id_documento, id_usuario, evento, ip_origen, navegador_info) VALUES (?, ?, 'SUBIDA', ?, ?)");
            $stmt_ev->execute([$doc_id, $_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

            $pdo->commit(); // Confirmación de cambios en la DB
            header("Location: ../../public/dashboard.php?msg=ok");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack(); // Reversión en caso de error para evitar datos huérfanos
            die("Error en la transacción de base de datos: " . $e->getMessage());
        }
        
    } else {
        die("Error de E/S: Fallo al mover el binario al repositorio final.");
    }
}
?>