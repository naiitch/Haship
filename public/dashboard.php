<?php
/**
 * HASHIP PROJECT - Panel de Control Administrativo
 * Autor: Nico (Lead Developer)
 * Versión: 1.1
 * * DESCRIPCIÓN:
 * Vista principal de gestión documental. Proporciona una visión global del 
 * estado de los activos digitales, permitiendo la carga de nuevos archivos 
 * y el acceso a la auditoría individual de cada documento.
 */

// 1. SEGURIDAD DE ACCESO: Middleware de autenticación
require_once '../src/php/auth.php';
checkAuth(); // Centraliza la protección de la vista privada

require_once '../src/php/db.php';

/**
 * CONSULTA ESTRATÉGICA:
 * Recuperamos los documentos ordenados cronológicamente (LIFO) para mostrar 
 * la actividad más reciente en la parte superior del tablero.
 */
$stmt = $pdo->query("SELECT * FROM documentos ORDER BY fecha_subida DESC");
$documentos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Infraestructura Haship</title>
    
    <link rel="stylesheet" href="static/estilo.css"> 
    
    <style>
        /**
         * DASHBOARD UI DESIGN:
         * Diseño limpio con énfasis en la lectura de estados y hashes.
         */
        body { 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            background: #f8fafc; 
            padding: 40px 20px; 
            color: #1e293b; 
        }
        
        .main-card { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); 
            max-width: 1100px; 
            margin: auto; 
            border: 1px solid #e2e8f0;
        }

        header { margin-bottom: 30px; }
        h1 { color: #0f172a; margin: 0; font-weight: 800; font-size: 2rem; }
        .subtitle { color: #64748b; margin: 5px 0 0 0; }
        
        /* Botones y Herramientas */
        .toolbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-top: 30px; 
            background: #f1f5f9; 
            padding: 15px 25px; 
            border-radius: 12px;
        }

        .btn-primary { 
            background: #3182ce; 
            color: white; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 10px; 
            font-weight: 600; 
            transition: all 0.2s;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover { background: #2c5282; transform: translateY(-1px); }

        /* Estilos de Tabla de Datos */
        .table-container { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { 
            background: #f8fafc; 
            color: #64748b; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 0.05em; 
            padding: 15px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        
        /* Semántica de Estados */
        .badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .pendiente { background: #fef3c7; color: #92400e; }
        .validado { background: #dcfce7; color: #166534; }
        .rechazado { background: #fee2e2; color: #991b1b; }
        
        /* Representación del Hash */
        .hash-preview { 
            font-family: 'Fira Code', 'Courier New', monospace; 
            font-size: 0.85rem; 
            color: #0d9488; 
            background: #f0fdfa; 
            padding: 4px 8px; 
            border-radius: 6px; 
            border: 1px solid #ccfbf1;
        }
    </style>
</head>
<body>
    <div class="main-card">
        <header>
            <h1>Dashboard de Haship</h1>
            <p class="subtitle">Ecosistema de Verificación y Certificación Digital</p>
        </header>
        
        <div class="toolbar">
            <button class="btn-primary" onclick="document.getElementById('upload-section').style.display='block'">
                <svg style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Documento
            </button>
            <span style="font-size: 0.9rem; color: #475569;">
                Sesión iniciada: <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>
            </span>
        </div>

        <section id="upload-section" style="display:none; margin-top:20px; padding:25px; border:2px dashed #cbd5e1; border-radius:15px; background: #f8fafc;">
            <form action="../src/php/upload.php" method="POST" enctype="multipart/form-data">
                <p style="margin-top:0; color: #475569; font-size: 0.9rem;">Seleccione un archivo PDF para procesar su huella SHA-256:</p>
                <div style="display:flex; gap:15px; align-items:center;">
                    <input type="file" name="documento" accept=".pdf" required style="font-size: 0.9rem;">
                    <button type="submit" class="btn-primary" style="background: #10b981; cursor:pointer;">
                        Certificar con Python
                    </button>
                </div>
            </form>
        </section>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Activo Digital</th>
                        <th>Hash SHA-256 (Motor Criptográfico)</th>
                        <th>Estado</th>
                        <th>Fecha de Registro</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentos)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                                <p>No se han encontrado evidencias registradas en el sistema.</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($documentos as $doc): ?>
                    <tr>
                        <td style="font-weight: 600; color: #1e293b;">
                            <?= htmlspecialchars($doc['nombre_real']) ?>
                        </td>
                        <td>
                            <span class="hash-preview" title="<?= $doc['hash_seguridad'] ?>">
                                <?= substr($doc['hash_seguridad'], 0, 12) ?>...
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= strtolower($doc['estado']) ?>">
                                <?= $doc['estado'] ?>
                            </span>
                        </td>
                        <td style="color: #64748b;">
                            <?= date("d/m/Y H:i", strtotime($doc['fecha_subida'])) ?>
                        </td>
                        <td>
                            <a href="vista_doc.php?id=<?= $doc['id'] ?>" class="btn-primary" style="padding: 6px 14px; font-size: 0.8rem; background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0;">
                                Gestionar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: right; margin-top: 25px;">
             <a href="../src/php/auth.php?logout=1" style="color: #e53e3e; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Cerrar Sesión Segura</a>
        </div>
    </div>
</body>
</html>