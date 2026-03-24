<?php
require_once 'db.php';
require_once 'auth.php';
checkAuth();

if ($_SESSION['rol'] === 'administrador' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: ../../public/admin_usuarios.php?msg=restored");
} else {
    header("Location: ../../public/dashboard.php");
}