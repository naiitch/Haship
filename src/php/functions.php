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