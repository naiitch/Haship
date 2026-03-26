<?php
/**
 * HASHIP PROJECT - Módulo de Formalización de Firma y Evidencia Digital
 * Autor: Nico (Lead Developer)
 * Versión: 2.3 (Validación de Autoridad de Firma + Transacciones ACID)
 */

require_once 'db.php';
require_once 'auth.php';
require_once 'Logger.php'; // RA8: Importamos la clase de auditoría para registrar eventos

$logger = new AuditLogger();
$logger->loguear("FIRMA_DOCUMENTO", $_SESSION['usuario_id'], ["doc_id" => $_POST['id']]);
checkAuth();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_doc'])) {
    
    $id = filter_var($_POST['id_doc'], FILTER_SANITIZE_NUMBER_INT);
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $id_usuario = $_SESSION['usuario_id'];
    $rol_usuario = $_SESSION['rol'];

    try {
        $pdo->beginTransaction();

        /**
         * 1. VALIDACIÓN DE AUTORIDAD:
         * Verificamos que el documento exista Y que el usuario sea el destinatario 
         * (O un administrador, si así lo decides en tu lógica de negocio).
         */
        $stmt_check = $pdo->prepare("SELECT id_destinatario FROM documentos WHERE id = ?");
        $stmt_check->execute([$id]);
        $doc = $stmt_check->fetch();

        if (!$doc) {
            throw new Exception("El activo no existe.");
        }

        // Si no es el destinatario Y no es administrador, bloqueamos la firma.
        if ($doc['id_destinatario'] != $id_usuario && $rol_usuario !== 'administrador') {
            throw new Exception("No tiene permisos legales para firmar este activo.");
        }

        /**
         * 2. ACTUALIZACIÓN DE ESTADO
         */
        $stmt = $pdo->prepare("UPDATE documentos SET estado = 'validado', fecha_validacion = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        /**
         * 3. GENERACIÓN DE EVIDENCIA (Audit Trail)
         * Usamos 'FIRMA_DIGITAL' como evento para el log.
         */
        $sql_evidencia = "INSERT INTO evidencias (id_documento, id_usuario, evento, ip_origen, navegador_info) 
                          VALUES (?, ?, 'FIRMA_DIGITAL', ?, ?)";
        $stmt_ev = $pdo->prepare($sql_evidencia);
        $stmt_ev->execute([$id, $id_usuario, $ip, $ua]);

        $pdo->commit();

        header("Location: ../../public/vista_doc.php?id=" . $id . "&msg=success");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Fallo crítico en firma Haship: " . $e->getMessage());
        // Redirigir con error para que el usuario sepa qué pasó
        die("ERROR DE PROTOCOLO: " . $e->getMessage());
    }
} else {
    header("Location: ../../public/dashboard.php");
    exit();
}