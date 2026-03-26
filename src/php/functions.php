<?php
/**
 * HASHIP PROJECT - Librería de Funciones Auxiliares
 * Autor: Nico (Lead Developer)
 */

/**
 * Traduce los estados del ENUM a etiquetas visuales CSS
 */
function getEstadoLabel($estado) {
    $labels = [
        'pendiente' => '<span class="badge badge-warning">Pendiente</span>',
        'validado'  => '<span class="badge badge-success">Validado</span>',
        'rechazado' => '<span class="badge badge-danger">Rechazado</span>',
        'expirado'  => '<span class="badge badge-muted">Expirado</span>'
    ];
    return $labels[$estado] ?? $estado;
}

/**
 * Formatea el Hash para visualización (acortado con puntos suspensivos)
 */
function formatHash($hash) {
    return substr($hash, 0, 8) . "..." . substr($hash, -8);
}

/**
 * Obtiene el nombre de un usuario dado su ID
 */
function getUsuarioNombre($pdo, $id) {
    if (!$id) return "No asignado";
    $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    return $res ? $res['nombre'] : "Usuario desconocido";
}

function registrarLogAuditoria($evento, $usuario, $detalles) {
    // 1. Definimos la ruta del archivo
    $relative_path = '../../storage/audit_log.jsonl';
    
    // 2. Preparamos los datos (RA6: Tipos de datos avanzados / RA8: Persistencia)
    $logEntry = [
        "timestamp" => date("c"), // Formato ISO 8601
        "evento"    => $evento,
        "usuario"   => $usuario,
        "detalles"  => $detalles
    ];

    // 3. Convertimos a JSON y añadimos un salto de línea (Formato JSONL)
    $jsonLine = json_encode($logEntry) . PHP_EOL;

    // 4. RA8: Gestión de persistencia. 
    // FILE_APPEND lo crea si no existe. 
    // LOCK_EX evita que dos procesos escriban al mismo tiempo (integridad RA9).
    file_put_contents($relative_path, $jsonLine, FILE_APPEND | LOCK_EX);
}