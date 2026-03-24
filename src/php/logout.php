<?php
/**
 * HASHIP PROJECT - Cierre de Sesión Seguro (Protocolo de Desconexión)
 * Autor: Nico (Lead Developer)
 * Versión: 2.0
 * * DESCRIPCIÓN:
 * Garantiza la invalidación total del token de sesión tanto en el cliente
 * como en el servidor, redirigiendo al usuario al portal de acceso con 
 * flags de confirmación.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 1. INVALIDACIÓN DE DATOS:
 * Vaciamos el array superglobal para evitar persistencia en memoria.
 */
$_SESSION = array();

/**
 * 2. DESTRUCCIÓN DE COOKIE DE SESIÓN:
 * Eliminamos el rastro del PHPSESSID en el navegador del cliente.
 */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

/**
 * 3. TERMINACIÓN DE SESIÓN EN SERVIDOR:
 * Liberamos los recursos del servidor asociados a este ID de sesión.
 */
session_destroy();

/**
 * 4. REDIRECCIÓN DE RETORNO:
 * Volvemos al portal de acceso dentro de la carpeta public.
 * Usamos 'msg=logout' para que coincida con la lógica de alertas del index.php
 */
header("Location: ../../public/index.php?msg=logout");
exit();