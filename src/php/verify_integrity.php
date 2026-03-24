<?php
/**
 * HASHIP PROJECT - Verificador de Integridad Forense
 * Compara el hash actual del archivo vs el hash registrado en el momento de subida.
 */
require_once 'db.php';
require_once 'auth.php';
checkAuth();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 1. Recuperamos los datos originales
    $stmt = $pdo->prepare("SELECT nombre_almacenado, hash_seguridad FROM documentos WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if ($doc) {
        $ruta_fisica = "../../almacenamiento/uploads/" . $doc['nombre_almacenado'];
        
        if (file_exists($ruta_fisica)) {
            // 2. Recalculamos el Hash en caliente
            $hash_actual = hash_file('sha256', $ruta_fisica);

            // 3. COMPARATIVA CRIPTOGRÁFICA
            if ($hash_actual === $doc['hash_seguridad']) {
                header("Location: ../../public/vista_doc.php?id=$id&integrity=valid");
            } else {
                header("Location: ../../public/vista_doc.php?id=$id&integrity=corrupt");
            }
        } else {
            die("Error: El activo físico ha sido eliminado del repositorio.");
        }
    }
}