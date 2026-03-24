<?php
/**
 * HASHIP PROJECT - Nodo de Auditoría de Alta Disponibilidad
 * Autor: Nico (Lead Developer)
 * Versión: 3.0 (Integrity Node Interface)
 */

require_once '../src/php/db.php';
require_once '../src/php/auth.php';

checkAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];

// 1. EXTRACCIÓN DE METADATOS
$stmt = $pdo->prepare("SELECT d.*, u_p.nombre as remitente 
                       FROM documentos d 
                       LEFT JOIN usuarios u_p ON d.id_propietario = u_p.id 
                       WHERE d.id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    die("ERROR_CRITICAL: Activo no encontrado.");
}

// 2. MOTOR DE AUDITORÍA (Lógica optimizada)
$relative_path = "../almacenamiento/uploads/" . $doc['nombre_almacenado'];
$absolute_file_path = realpath($relative_path);
$python_script = realpath("../src/python/hasher.py");

$hash_actual = "LECTURA_FALLIDA";
if ($absolute_file_path && file_exists($absolute_file_path)) {
    $os_command = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
    $command = "$os_command " . escapeshellarg($python_script) . " " . escapeshellarg($absolute_file_path);
    $hash_actual = trim(shell_exec($command));
}

$es_integro = ($hash_actual === $doc['hash_seguridad']);
$ip_auditor = $_SERVER['REMOTE_ADDR'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUDITORÍA - <?= htmlspecialchars($doc['nombre_real']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --apple-blue: #007aff;
            --apple-red: #ff3b30;
            --apple-green: #34c759;
            --apple-bg: #f5f5f7;
            --text-main: #1d1d1f;
            --text-sec: #86868b;
        }

        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: var(--apple-bg); 
            margin: 0; 
            padding: 40px 0; 
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 1000px; margin: auto; padding: 0 20px; }

        /* Status Banner */
        .integrity-card {
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
        }

        .integrity-ok { background: rgba(52, 199, 89, 0.1); color: #1a7f37; border-color: rgba(52, 199, 89, 0.2); }
        .integrity-fail { background: rgba(255, 59, 48, 0.1); color: #d70015; border-color: rgba(255, 59, 48, 0.2); animation: shake 0.4s ease; }

        /* Info Grid */
        .audit-header {
            background: white;
            padding: 35px;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 40px;
        }

        h1 { font-size: 24px; font-weight: 700; letter-spacing: -0.5px; margin: 0 0 15px 0; }

        .meta-list { font-size: 13px; color: var(--text-sec); list-style: none; padding: 0; margin: 0; }
        .meta-list li { margin-bottom: 8px; display: flex; justify-content: space-between; border-bottom: 1px solid #f2f2f7; padding-bottom: 4px; }
        .meta-list b { color: var(--text-main); }

        /* Hash Display */
        .hash-box {
            background: #fbfbfd;
            padding: 15px;
            border-radius: 14px;
            border: 1px solid #d2d2d7;
            margin-top: 10px;
        }
        .hash-label { font-size: 10px; font-weight: 700; color: var(--text-sec); text-transform: uppercase; margin-bottom: 5px; display: block; }
        .hash-value { font-family: 'Fira Code', monospace; font-size: 11px; word-break: break-all; color: #444; }

        /* Viewer Area */
        .viewer-frame {
            width: 100%;
            height: 600px;
            background: #1c1c1e;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        iframe { width: 100%; height: 100%; border: none; }

        /* Footer Actions */
        .action-area {
            margin-top: 30px;
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 24px;
        }

        .btn-main {
            background: var(--apple-blue);
            color: white;
            padding: 16px 40px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-main:hover:not(:disabled) { transform: scale(1.02); opacity: 0.9; }
        .btn-main:disabled { background: #d2d2d7; cursor: not-allowed; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            60% { transform: translateX(10px); }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="integrity-card <?= $es_integro ? 'integrity-ok' : 'integrity-fail' ?>">
        <div style="font-size: 32px;"><?= $es_integro ? '✓' : '⚠' ?></div>
        <div>
            <div style="font-weight: 700; font-size: 15px;">
                <?= $es_integro ? 'ACTIVO ÍNTEGRO' : 'ERROR DE CONSISTENCIA' ?>
            </div>
            <div style="font-size: 13px; opacity: 0.9;">
                <?= $es_integro 
                    ? "La huella criptográfica SHA-256 coincide con el registro original de la base de datos." 
                    : "Se ha detectado una alteración en el binario físico. El protocolo de firma ha sido bloqueado." ?>
            </div>
        </div>
    </div>

    <main class="audit-header">
        <div>
            <span style="font-size: 11px; font-weight: 700; color: var(--apple-blue); text-transform: uppercase;">Expediente Digital</span>
            <h1><?= htmlspecialchars($doc['nombre_real']) ?></h1>
            
            <ul class="meta-list">
                <li><span>Originador:</span> <b><?= htmlspecialchars($doc['remitente']) ?></b></li>
                <li><span>ID Nodo:</span> <b>#<?= str_pad($doc['id'], 6, "0", STR_PAD_LEFT) ?></b></li>
                <li><span>Fecha Ingreso:</span> <b><?= date("d M Y, H:i", strtotime($doc['fecha_subida'])) ?></b></li>
                <li><span>IP Auditor:</span> <b><?= $ip_auditor ?></b></li>
            </ul>
        </div>

        <div>
            <div class="hash-box">
                <span class="hash-label">Referencia en Base de Datos</span>
                <div class="hash-value"><?= $doc['hash_seguridad'] ?></div>
            </div>
            <div class="hash-box" style="margin-top: 15px; border-color: <?= $es_integro ? 'var(--apple-green)' : 'var(--apple-red)' ?>">
                <span class="hash-label">Resultado Escaneo en Tiempo Real</span>
                <div class="hash-value" style="color: <?= $es_integro ? 'var(--apple-green)' : 'var(--apple-red)' ?>;">
                    <?= $hash_actual ?>
                </div>
            </div>
        </div>
    </main>

    <div class="viewer-frame">
        <iframe src="../src/php/view_pdf.php?id=<?= $doc['id'] ?>"></iframe>
    </div>

    <div class="action-area">
        <?php if ($doc['estado'] == 'pendiente'): ?>
            <form action="../src/php/sign_doc.php" method="POST">
                <input type="hidden" name="id_doc" value="<?= $doc['id'] ?>">
                <button type="submit" class="btn-main" <?= !$es_integro ? 'disabled' : '' ?>>
                    Estampar Firma Digital Inmutable
                </button>
                <?php if (!$es_integro): ?>
                    <p style="color: var(--apple-red); font-size: 12px; margin-top: 15px; font-weight: 600;">
                        Acción bloqueada por fallo de seguridad SHA-256.
                    </p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div style="color: var(--apple-green); font-weight: 700; font-size: 18px; margin-bottom: 20px;">
                ● DOCUMENTO CERTIFICADO Y SELLADO
            </div>
            <a href="export_audit.php?id=<?= $doc['id'] ?>" class="btn-main" style="background: var(--text-main);">
                Descargar Reporte de Evidencia
            </a>
        <?php endif; ?>

        <a href="dashboard.php" style="display: block; margin-top: 30px; color: var(--text-sec); font-size: 12px; text-decoration: none; font-weight: 500;">
            ← Regresar al Panel Principal
        </a>
    </div>
</div>

</body>
</html>