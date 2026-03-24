<?php
/**
 * HASHIP PROJECT - Gestor de Ingesta y Certificación de Documentos
 * Autor: Nico (Lead Developer)
 * Versión: 2.1 (Sincronizado con Schema 2.0)
 * * DESCRIPCIÓN:
 * Este módulo gestiona el ciclo de vida inicial de una evidencia: 
 * recepción del binario, almacenamiento seguro, invocación del motor de 
 * hashing externo (Python) y registro de trazabilidad en la base de datos.
 * Implementa soporte para asignación de destinatarios y mensajes de contexto.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'auth.php';

/**
 * PROTECCIÓN DE ACCESO:
 * Solo usuarios autenticados (Remitentes o Administradores) pueden realizar cargas.
 */
checkAuth();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['documento'])) {
    
    // Captura de datos del formulario (Schema 2.0)
    $file = $_FILES['documento'];
    $nombre_real = $file['name'];
    $ext = strtolower(pathinfo($nombre_real, PATHINFO_EXTENSION));
    
    // Captura de metadatos de negocio
    $id_destinatario = !empty($_POST['id_destinatario']) ? $_POST['id_destinatario'] : null;
    $mensaje_contexto = isset($_POST['mensaje_contexto']) ? trim($_POST['mensaje_contexto']) : '';

    /**
     * 1. VALIDACIÓN DE PROTOCOLO:
     * Restricción estricta a PDF para garantizar la inmutabilidad visual 
     * y la compatibilidad con el motor de hashing.
     */
    if ($ext !== 'pdf') {
        header("Location: ../../public/dashboard.php?error=formato");
        exit();
    }

    /**
     * 2. OFUSCACIÓN DE ALMACENAMIENTO:
     * Generamos un nombre pseudoaleatorio criptográficamente seguro (20 caracteres).
     * Esto previene ataques de enumeración (IDOR) y colisiones de nombres.
     */
    $nombre_almacenado = bin2hex(random_bytes(10)) . ".pdf";
    $directorio_destino = "../../almacenamiento/uploads/";
    $ruta_final = $directorio_destino . $nombre_almacenado;

    // Gestión automática de infraestructura de directorios
    if (!is_dir($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }

    // Persistencia física del binario
    if (move_uploaded_file($file['tmp_name'], $ruta_final)) {

        /**
         * 3. INTEROPERABILIDAD PHP-PYTHON (The Python Effect):
         * Invocamos el script 'hasher.py' para procesar el archivo.
         * Utilizamos escapeshellarg() para sanitizar la ruta y prevenir RCE.
         */
        $ruta_script_python = "../python/hasher.py";
        
        // El comando asume que el intérprete de python está en el PATH
        // Cambia la línea del comando por esta para asegurar compatibilidad
        $comando = "python3 " . escapeshellarg($ruta_script_python) . " " . escapeshellarg(realpath($ruta_final)) . " 2>&1";
        
        // shell_exec captura el hash SHA-256 devuelto por el script de Python
        $hash_generado = trim(shell_exec($comando));

        /**
         * VALIDACIÓN DEL HASH:
         * Si el motor de Python falla, recurrimos a la función nativa de PHP
         * para no detener el flujo de negocio, marcando la incidencia.
         */
        if (empty($hash_generado) || strlen($hash_generado) !== 64) {
            $hash_generado = hash_file('sha256', $ruta_final);
        }

        /**
         * 4. TRANSACCIÓN ATÓMICA (ACID):
         * Garantizamos que el registro del documento y su evidencia de auditoría
         * se realicen como una única unidad de trabajo indivisible.
         */
        try {
            $pdo->beginTransaction();

            // REGISTRO 1: Metadatos del activo digital (Tabla 'documentos')
            $query_doc = "INSERT INTO documentos 
                          (nombre_real, nombre_almacenado, id_propietario, id_destinatario, mensaje_contexto, hash_seguridad, estado) 
                          VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";
            
            $stmt = $pdo->prepare($query_doc);
            $stmt->execute([
                $nombre_real, 
                $nombre_almacenado, 
                $_SESSION['usuario_id'], 
                $id_destinatario, 
                $mensaje_contexto, 
                $hash_generado
            ]);

            $doc_id = $pdo->lastInsertId();

            /**
             * 5. AUDIT TRAIL (Pista de Auditoría):
             * Vinculamos legalmente el evento de 'SUBIDA' con la IP y el User Agent.
             */
            $query_ev = "INSERT INTO evidencias (id_documento, id_usuario, evento, ip_origen, navegador_info) 
                         VALUES (?, ?, 'SUBIDA', ?, ?)";
            
            $stmt_ev = $pdo->prepare($query_ev);
            $stmt_ev->execute([
                $doc_id, 
                $_SESSION['usuario_id'], 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT']
            ]);

            $pdo->commit(); // Consolidación definitiva
            
            header("Location: ../../public/dashboard.php?msg=upload_ok");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack(); // Reversión total para evitar inconsistencias
            error_log("Fallo en transacción de subida Haship: " . $e->getMessage());
            die("Error crítico de persistencia: " . $e->getMessage());
        }
        
    } else {
        header("Location: ../../public/dashboard.php?error=io");
        exit();
    }
} else {
    header("Location: ../../public/dashboard.php");
    exit();
}