<?php
/**
 * HASHIP PROJECT - Visor de Certificación y Terminal de Firma
 * Autor: Nico (Lead Developer)
 * Versión: 1.1
 * * DESCRIPCIÓN:
 * Este módulo actúa como la interfaz de revisión legal. Permite al usuario
 * contrastar visualmente el contenido del PDF con su Hash SHA-256 antes
 * de proceder a la firma electrónica.
 */

require_once '../src/php/db.php';
require_once '../src/php/auth.php';

// Verificación de integridad de la sesión
checkAuth();

// Validación de parámetro de entrada
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];

/**
 * CONSULTA DE ACTIVOS:
 * Recuperamos los metadatos del documento para mostrar la trazabilidad.
 */
$stmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    die("Error: El activo digital no existe en el repositorio.");
}

/**
 * GESTIÓN DE RUTAS:
 * El visor apunta a la carpeta de almacenamiento ofuscado.
 * Añadimos '#toolbar=0' para una experiencia de lectura más limpia (UI).
 */
$file_path = "../almacenamiento/uploads/" . $doc['nombre_almacenado'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificación - <?= htmlspecialchars($doc['nombre_real']) ?></title>
    
    <style>
        /**
         * VISUAL DESIGN SYSTEM:
         * Diseño enfocado en la legibilidad y la solemnidad del acto de firma.
         */
        body { 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            background: #f8fafc; 
            margin: 0; 
            padding: 40px 20px; 
        }
        
        .container { width: 100%; max-width: 1000px; }

        .header-panel { 
            background: white; 
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
            margin-bottom: 30px; 
            border: 1px solid #e2e8f0;
        }

        h1 { color: #1e293b; margin-top: 0; font-size: 1.75rem; }
        
        /* Estilo para el Hash: Resaltado como dato crítico */
        .hash-badge { 
            background: #f1f5f9; 
            color: #475569; 
            padding: 8px 12px; 
            border-radius: 8px; 
            font-family: 'Fira Code', 'Courier New', monospace; 
            font-size: 0.85em; 
            border: 1px solid #cbd5e1;
            word-break: break-all;
            display: block;
            margin-top: 10px;
        }
        
        /* Contenedor del Visor PDF */
        .viewer-card { 
            width: 100%; 
            height: 700px; 
            background: #334155; 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); 
            border: 1px solid #1e293b; 
        }
        iframe { width: 100%; height: 100%; border: none; }
        
        /* Panel de Acciones Finales */
        .action-panel { 
            margin-top: 30px; 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            text-align: center; 
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .btn-firmar { 
            background: #10b981; 
            color: white; 
            padding: 18px 50px; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            font-size: 1.1em; 
            font-weight: 700; 
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.39);
        }
        .btn-firmar:hover { 
            background: #059669; 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.23);
        }

        .status-valid { color: #059669; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-back { display: inline-block; margin-top: 25px; color: #64748b; text-decoration: none; font-size: 0.9em; font-weight: 500; }
        .btn-back:hover { color: #1e293b; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="container">
        <header class="header-panel">
            <h1>Certificación de Activo Digital</h1>
            <p style="color: #64748b; margin-bottom: 5px;">Documento: <strong><?= htmlspecialchars($doc['nombre_real']) ?></strong></p>
            <p style="color: #64748b; margin: 0;">Hash de Seguridad (SHA-256):</p>
            <code class="hash-badge"><?= $doc['hash_seguridad'] ?></code>
        </header>

        <div class="viewer-card">
            <iframe src="<?= $file_path ?>#toolbar=0"></iframe>
        </div>

        <div class="action-panel">
            <?php if ($doc['estado'] == 'pendiente'): ?>
                <form action="../src/php/sign_doc.php" method="POST">
                    <input type="hidden" name="id_doc" value="<?= $doc['id'] ?>">
                    <p style="color: #475569; line-height: 1.6; max-width: 600px; margin: 0 auto 30px auto;">
                        Al proceder con la firma, usted manifiesta su conformidad con el contenido íntegro del documento superior. 
                        Este acto generará una evidencia digital inmutable vinculada a su identidad técnica.
                    </p>
                    <button type="submit" class="btn-firmar">Confirmar Firma Electrónica</button>
                </form>
            <?php else: ?>
                <div class="status-valid">
                    <svg style="width:30px;height:30px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span style="font-size: 1.5rem;">Documento Certificado y Firmado</span>
                </div>
                <p style="color: #64748b; margin-top: 10px;">La integridad de este archivo ha sido validada contra el registro original.</p>
                <a href="dashboard.php" class="btn-back">← Volver al Panel de Gestión</a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>