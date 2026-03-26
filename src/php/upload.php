<?php
/**
 * HASHIP PROJECT - Gestor de Ingesta y Certificación de Documentos
 * Autor: Nico (Lead Developer)
 * Versión: 3.0 (Update Examen RA - Integración Multicapa)
 * * DESCRIPCIÓN:
 * Evolución del módulo de ingesta con soporte para:
 * - RA3/RA6: Validación avanzada de tipos y control de excepciones.
 * - RA8/RA9: Persistencia híbrida (MySQL + Log de Auditoría JSONL).
 * - RA5: Gestión de flujos de E/S de archivos binarios.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'auth.php';
// RA8: Importación de la lógica de persistencia no relacional
require_once 'Logger.php'; 

/**
 * PROTECCIÓN DE ACCESO:
 * Solo usuarios autenticados pueden realizar cargas.
 */
checkAuth();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['documento'])) {
    
    /**
     * RA3/RA6: GESTIÓN DE EXCEPCIONES Y TIPOS AVANZADOS
     * Envolvemos el proceso en un bloque try para garantizar la integridad (RA9)
     * y utilizamos estructuras de datos para validaciones robustas.
     */
    try {
        // Captura de datos
        $file = $_FILES['documento'];
        $nombre_real = $file['name'];
        
        // RA6: Manipulación de información mediante tipos avanzados (Arrays de configuración)
        $extensiones_permitidas = ['pdf', 'png', 'jpg']; 
        $ext = strtolower(pathinfo($nombre_real, PATHINFO_EXTENSION));
        
        // 1. VALIDACIÓN DE PROTOCOLO (RA3: Estructura de control preventiva)
        if (!in_array($ext, $extensiones_permitidas)) {
            throw new Exception("Formato de archivo no soportado para certificación.");
        }

        // Captura de metadatos de negocio
        $id_destinatario = !empty($_POST['id_destinatario']) ? $_POST['id_destinatario'] : null;
        $mensaje_contexto = isset($_POST['mensaje_contexto']) ? trim($_POST['mensaje_contexto']) : '';

        /**
         * 2. OFUSCACIÓN Y PREPARACIÓN DE E/S (RA5):
         * Generamos un nombre pseudoaleatorio para evitar ataques de enumeración.
         */
        $nombre_almacenado = bin2hex(random_bytes(10)) . "." . $ext;
        $directorio_destino = "../../almacenamiento/uploads/";
        $ruta_final = $directorio_destino . $nombre_almacenado;

        if (!is_dir($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }

        // Persistencia física del binario (RA5: Operación de Salida)
        if (!move_uploaded_file($file['tmp_name'], $ruta_final)) {
            throw new Exception("Fallo en la persistencia física del binario (I/O Error).");
        }

        /**
         * 3. MOTOR DE INTEGRIDAD CRIPTOGRÁFICA (The Python Effect):
         * RA9: Asegurando la consistencia mediante hashing externo.
         */
        $ruta_script_python = "../python/hasher.py";
        $comando = "python3 " . escapeshellarg($ruta_script_python) . " " . escapeshellarg(realpath($ruta_final)) . " 2>&1";
        $hash_generado = trim(shell_exec($comando));

        // Fallback de seguridad si el motor Python no responde
        if (empty($hash_generado) || strlen($hash_generado) !== 64) {
            $hash_generado = hash_file('sha256', $ruta_final);
        }

        /**
         * 4. TRANSACCIÓN ATÓMICA Y PERSISTENCIA (RA8/RA9):
         * Sincronizamos MySQL con nuestro nuevo sistema de Log JSONL.
         */
        $pdo->beginTransaction();

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

        // RA8: PERSISTENCIA EN LOG TEXTUAL (JSONL)
        // Registramos el evento de forma persistente fuera de la DB relacional.
        $logger = new AuditLogger('../../storage/audit_log.jsonl');
        $logger->loguear("UPLOAD_SUCCESS", $_SESSION['usuario_id'], ["doc_id" => $doc_id, "hash" => $hash_generado]);

        $pdo->commit(); 
        
        header("Location: ../../public/dashboard.php?msg=upload_ok");
        exit();

    } catch (Exception $e) {
        // RA3/RA9: En caso de error, revertimos cambios para mantener la consistencia
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Registro del error para depuración técnica
        error_log("CRITICAL_ERROR [upload.php]: " . $e->getMessage());
        
        // Redirección con feedback al usuario
        header("Location: ../../public/dashboard.php?error=" . urlencode($e->getMessage()));
        exit();
    }

} else {
    header("Location: ../../public/dashboard.php");
    exit();
}