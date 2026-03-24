<?php
/**
 * HASHIP PROJECT - Protocolo de Suspensión de Nodo (Borrado Lógico)
 * Autor: Nico (Lead Developer)
 * Versión: 2.0
 */

require_once 'db.php';
require_once 'auth.php';

checkAuth();

if ($_SESSION['rol'] !== 'administrador') {
    header("Location: ../../public/dashboard.php?error=unauthorized");
    exit();
}

$id_a_suspender = isset($_GET['id']) ? (int)$_GET['id'] : null;
$admin_id = $_SESSION['usuario_id'];

if (!$id_a_suspender || $id_a_suspender === $admin_id) {
    header("Location: ../../public/admin_usuarios.php?error=invalid_operation");
    exit();
}

try {
    // Simplemente marcamos el nodo como inactivo
    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt->execute([$id_a_suspender]);

    header("Location: ../../public/admin_usuarios.php?msg=user_suspended");
} catch (Exception $e) {
    error_log("Error en suspensión de usuario: " . $e->getMessage());
    die("ERROR_SYSTEM_INTEGRITY");
}