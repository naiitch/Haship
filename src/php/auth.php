<?php
/**
 * HASHIP PROJECT - Módulo de Autenticación y Control de Sesiones
 * Autor: Nico (Lead Developer)
 * Versión: 3.1 (Sincronización de Protocolos y Borrado Lógico)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

/**
 * HANDSHAKE DE SEGURIDAD
 * Verifica si el usuario está en sesión y si su nodo sigue ACTIVO.
 */
function checkAuth() {
    global $pdo;
    
    // 1. Verificación de existencia de sesión
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: index.php?error=2"); 
        exit();
    }

    // 2. Verificación de persistencia (Evita que un usuario suspendido siga navegando)
    $stmt = $pdo->prepare("SELECT activo FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    $userStatus = $stmt->fetch();

    if (!$userStatus || $userStatus['activo'] == 0) {
        // El usuario ha sido suspendido por un Admin mientras estaba logueado
        session_destroy();
        header("Location: index.php?error=suspended");
        exit();
    }
}

/**
 * PROTOCOLO DE LOGIN
 */
if (isset($_POST['login'])) {
    
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    /**
     * FILTRO DE ESTADO: 
     * Solo permitimos el fetch si el usuario tiene el flag 'activo' en 1.
     */
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        session_regenerate_id(true);

        $_SESSION['usuario_id']     = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['rol']            = $user['rol'];
        
        header("Location: ../../public/dashboard.php");
        exit();
    } else {
        // Si no existe, la clave es errónea o el usuario está "suspendido" (activo = 0)
        header("Location: ../../public/index.php?error=1");
        exit();
    }
}

/**
 * PROTOCOLO DE CIERRE (LOGOUT)
 */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array(); 
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();   
    header("Location: ../../public/index.php?logout=1");
    exit();
}