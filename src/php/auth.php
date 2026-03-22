<?php
/**
 * HASHIP PROJECT - Módulo de Autenticación y Control de Sesiones
 * Autor: Nico (Lead Developer)
 * Versión: 1.0
 * * DESCRIPCIÓN:
 * Centraliza la lógica de seguridad para el acceso a la plataforma. 
 * Implementa el manejo de sesiones seguras de PHP, validación de 
 * credenciales mediante algoritmos de hash (BCRYPT) y protección de rutas.
 */

session_start();
require_once 'db.php';

/**
 * FUNCIÓN DE PROTECCIÓN DE RUTAS (Middleware)
 * Verifica si existe una sesión activa antes de renderizar vistas privadas.
 * Se debe invocar al inicio de archivos como dashboard.php o vista_doc.php.
 */
function checkAuth() {
    if (!isset($_SESSION['usuario_id'])) {
        // Redirección forzada si se intenta acceder por URL sin loguearse
        header("Location: index.php?error=2"); 
        exit();
    }
}

// ==========================================================
// 1. PROCESAMIENTO DE LOGIN (POST)
// ==========================================================
if (isset($_POST['login'])) {
    // Sanitización básica de entrada
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    /**
     * CONSULTA PREPARADA:
     * Buscamos al usuario por su email único. No concatenamos variables 
     * directamente en el SQL para evitar Inyecciones SQL.
     */
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    /**
     * VALIDACIÓN CRIPTOGRÁFICA:
     * Comparamos el texto plano del formulario con el hash de la DB.
     * El uso de password_verify() es el estándar de oro en PHP para mitigar
     * ataques de fuerza bruta y comparación de tiempos (Timing Attacks).
     */
    if ($user && password_verify($password, $user['password'])) {
        
        /**
         * SEGURIDAD DE SESIÓN:
         * session_regenerate_id(true) destruye el ID antiguo y crea uno nuevo.
         * Es la defensa principal contra ataques de 'Session Fixation'.
         */
        session_regenerate_id(true);

        // Hidratamos la sesión con los metadatos del usuario
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        
        header("Location: ../../public/dashboard.php");
        exit();
    } else {
        // Fallo de autenticación: Retornamos al login con flag de error
        header("Location: ../../public/index.php?error=1");
        exit();
    }
}

// ==========================================================
// 2. PROCESAMIENTO DE LOGOUT (GET)
// ==========================================================
if (isset($_GET['logout'])) {
    // Limpieza total del array de sesión en memoria
    $_SESSION = array(); 

    // Destrucción física de la sesión en el servidor
    session_destroy();   
    
    /**
     * ELIMINACIÓN DE COOKIE DE SESIÓN:
     * Por seguridad, forzamos al navegador a borrar la cookie 'PHPSESSID' 
     * estableciendo una fecha de expiración en el pasado.
     */
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    header("Location: ../../public/index.php?msg=logout");
    exit();
}
?>