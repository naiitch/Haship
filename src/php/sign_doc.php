<?php
/**
 * HASHIP PROJECT - Módulo de Formalización de Firma y Evidencia Digital
 * Autor: Nico (Lead Developer)
 * Versión: 1.0
 * * DESCRIPCIÓN:
 * Este componente procesa la aceptación del documento por parte del usuario.
 * No solo cambia un estado en la base de datos, sino que genera un registro
 * pericial (Audit Trail) capturando metadatos del entorno del firmante.
 */

session_start();
require_once 'db.php';

/**
 * VALIDACIÓN DE ENTRADA:
 * Aseguramos que el flujo provenga de un formulario (POST) y contenga 
 * la referencia al activo digital (id_doc).
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_doc'])) {
    
    // Captura de variables de entorno y sesión
    $id = $_POST['id_doc'];
    $ip = $_SERVER['REMOTE_ADDR'];      // IP del firmante (vincular lugar/red)
    $ua = $_SERVER['HTTP_USER_AGENT'];  // Huella del navegador (vincular dispositivo)
    
    // Identificación del sujeto activo de la firma
    $id_usuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

    try {
        /**
         * TRANSACCIONALIDAD (ACID):
         * Iniciamos una transacción para garantizar que la actualización del estado
         * y la creación de la evidencia sean atómicas. Si una falla, ninguna se aplica.
         */
        $pdo->beginTransaction();

        // 1. CAMBIO DE ESTADO: Transición de 'pendiente' a 'validado'
        $stmt = $pdo->prepare("UPDATE documentos SET estado = 'validado' WHERE id = ?");
        $stmt->execute([$id]);

        /**
         * 2. REGISTRO DE EVIDENCIA TÉCNICA:
         * Insertamos los metadatos en la tabla de evidencias. Este registro es
         * fundamental para procesos de auditoría, ya que vincula el Hash del 
         * documento con la IP y el User Agent del usuario en el instante exacto.
         */
        $sql_evidencia = "INSERT INTO evidencias (id_documento, id_usuario, evento, ip_origen, navegador_info) 
                          VALUES (?, ?, 'FIRMA', ?, ?)";
        $stmt_ev = $pdo->prepare($sql_evidencia);
        $stmt_ev->execute([$id, $id_usuario, $ip, $ua]);

        // Consolidación definitiva de la firma en la persistencia
        $pdo->commit();

        /**
         * REDIRECCIÓN DE RETORNO:
         * Devolvemos al usuario a la vista del documento con una bandera de éxito
         * para que el Frontend muestre el feedback visual correspondiente.
         */
        header("Location: ../../public/vista_doc.php?id=" . $id . "&msg=success");
        exit();

    } catch (Exception $e) {
        /**
         * GESTIÓN DE FALLOS:
         * En caso de excepción, realizamos un rollBack para mantener la base de datos
         * en un estado consistente y evitar firmas parciales o corruptas.
         */
        $pdo->rollBack();
        error_log("Fallo en proceso de firma: " . $e->getMessage()); // Registro interno de errores
        die("Error crítico al procesar la firma digital. Inténtelo de nuevo.");
    }
} else {
    /**
     * PROTECCIÓN DE ACCESO DIRECTO:
     * Si se intenta acceder al script sin los parámetros POST, redirigimos
     * al panel principal por seguridad.
     */
    header("Location: ../../public/dashboard.php");
    exit();
}
?>