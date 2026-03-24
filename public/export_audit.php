<?php
/**
 * HASHIP PROJECT - Generador de Certificado de Integridad
 * Autor: Nico (Lead Developer)
 * Versión: 3.1 (Sincronizado con DB v2.3)
 */

require_once '../src/php/db.php';
require_once '../src/php/auth.php';

checkAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    die("ERROR_ID: Referencia de activo nula.");
}

/**
 * 1. RECOLECCIÓN DE PRUEBAS CRIPTOGRÁFICAS
 * Sincronizado con ENUM 'FIRMA' de la tabla evidencias.
 */
$stmt = $pdo->prepare("
    SELECT d.*, 
           u_p.nombre as remitente,
           e.fecha_evento as fecha_firma,
           e.ip_origen,
           u_f.nombre as firmante
    FROM documentos d
    LEFT JOIN usuarios u_p ON d.id_propietario = u_p.id
    -- Ajustado para buscar el evento 'FIRMA' según tu esquema SQL
    LEFT JOIN evidencias e ON d.id = e.id_documento AND e.evento = 'FIRMA'
    LEFT JOIN usuarios u_f ON e.id_usuario = u_f.id
    WHERE d.id = ?
    ORDER BY e.fecha_evento DESC LIMIT 1
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data || $data['estado'] !== 'validado') {
    die("ERROR_STATUS: El certificado solo está disponible para activos VALIDADOS.");
}

// LÓGICA DE VISUALIZACIÓN DE FIRMA (Evita el "Pendiente de firma" si el estado es validado)
$esta_firmado = ($data['estado'] === 'validado');
$firmante_final = $data['firmante'] ?? ($esta_firmado ? "SISTEMA HASHIP (Auto-Certificado)" : "Pendiente");
$fecha_final = $data['fecha_firma'] ?? ($esta_firmado ? $data['fecha_subida'] : 'Pendiente');
$ip_final = $data['ip_origen'] ?? ($esta_firmado ? 'Registro Local' : '0.0.0.0');

// Generamos un ID de certificado único
$cert_id = strtoupper(substr(hash('sha256', $data['id'] . $fecha_final), 0, 12));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado_Haship_<?= $cert_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        :root {
            --apple-blue: #007aff;
            --apple-bg: #f5f5f7;
            --slate-900: #1d1d1f;
            --slate-500: #86868b;
            --success: #34c759;
        }

        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: var(--apple-bg); 
            margin: 0; 
            padding: 40px; 
            color: var(--slate-900); 
            -webkit-font-smoothing: antialiased;
        }
        
        .ui-controls { 
            max-width: 800px; 
            margin: 0 auto 30px auto; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }

        .btn-action {
            background: var(--apple-blue);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-action:hover { opacity: 0.9; transform: translateY(-1px); }

        #certificado { 
            max-width: 800px; 
            margin: auto; 
            background: white; 
            padding: 70px; 
            border-radius: 2px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }

        .watermark {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 110px;
            font-weight: 900;
            color: rgba(0,0,0,0.02);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
        }

        header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            border-bottom: 1px solid #eee;
            padding-bottom: 30px;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .brand h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -1px; }
        .brand span { color: var(--apple-blue); }
        .cert-meta { font-size: 11px; color: var(--slate-500); font-family: 'Fira Code', monospace; margin-top: 5px; display: block; }

        .badge-status { 
            background: rgba(52, 199, 89, 0.1); 
            color: var(--success); 
            padding: 6px 14px; 
            border-radius: 6px; 
            font-weight: 700; 
            font-size: 10px; 
            text-transform: uppercase;
        }

        h2 { font-size: 24px; font-weight: 700; margin-bottom: 10px; }
        .description { font-size: 14px; color: var(--slate-500); line-height: 1.6; margin-bottom: 40px; }

        .evidence-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 30px; 
            margin-bottom: 40px; 
            position: relative;
            z-index: 1;
        }

        .evidence-item label { 
            display: block; 
            font-size: 10px; 
            font-weight: 700; 
            color: var(--slate-500); 
            text-transform: uppercase; 
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .evidence-item p { margin: 0; font-size: 14px; font-weight: 600; color: var(--slate-900); }

        .hash-vault { 
            background: #f9f9fb; 
            padding: 20px; 
            border-radius: 12px; 
            border: 1px solid #efeff4;
            position: relative;
            z-index: 1;
        }
        .hash-vault label { font-size: 10px; font-weight: 800; color: var(--apple-blue); display: block; margin-bottom: 10px; }
        .hash-text { 
            font-family: 'Fira Code', monospace; 
            font-size: 12px; 
            color: var(--slate-900); 
            word-break: break-all; 
            line-height: 1.5;
        }

        footer { 
            margin-top: 60px; 
            padding-top: 20px; 
            border-top: 1px solid #eee; 
            display: flex; 
            justify-content: space-between; 
            font-size: 10px; 
            color: var(--slate-500);
            position: relative;
            z-index: 1;
        }

        @media print {
            .ui-controls { display: none; }
            body { padding: 0; background: white; }
            #certificado { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

    <div class="ui-controls">
        <a href="dashboard.php" style="color: var(--slate-500); text-decoration: none; font-size: 13px; font-weight: 600;">← Regresar</a>
        <button onclick="exportarActa()" class="btn-action">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Exportar Acta Pericial
        </button>
    </div>

    <div id="certificado">
        <div class="watermark">HASHIP</div>
        
        <header>
            <div class="brand">
                <h1>HASHIP <span>SECURITY</span></h1>
                <span class="cert-meta">REF-ID: <?= $cert_id ?></span>
            </div>
            <div class="badge-status">Activo Verificado</div>
        </header>

        <section>
            <h2>Acta de Integridad Digital</h2>
            <p class="description">
                El nodo de auditoría de HASHIP certifica que el activo digital detallado a continuación ha sido procesado mediante el motor de integridad, confirmando que su huella SHA-256 es idéntica al registro original.
            </p>
            
            <div class="evidence-grid">
                <div class="evidence-item">
                    <label>Archivo Auditado</label>
                    <p><?= htmlspecialchars($data['nombre_real']) ?></p>
                </div>
                <div class="evidence-item">
                    <label>Identificador Interno</label>
                    <p>HS-<?= str_pad($data['id'], 8, '0', STR_PAD_LEFT) ?></p>
                </div>
                <div class="evidence-item">
                    <label>Originador del Activo</label>
                    <p><?= htmlspecialchars($data['remitente']) ?></p>
                </div>
                <div class="evidence-item">
                    <label>Autoridad de Firma</label>
                    <p><?= htmlspecialchars($firmante_final) ?></p>
                </div>
                <div class="evidence-item">
                    <label>Sello de Tiempo</label>
                    <p><?= date("d/m/Y H:i:s", strtotime($fecha_final)) ?></p>
                </div>
                <div class="evidence-item">
                    <label>Nodo / IP Origen</label>
                    <p><?= $ip_final ?></p>
                </div>
            </div>

            <div class="hash-vault">
                <label>HUELLA DIGITAL CRIPTOGRÁFICA (SHA-256)</label>
                <div class="hash-text"><?= $data['hash_seguridad'] ?></div>
            </div>
        </section>

        <footer>
            <div>Emitido bajo protocolo HASHIP v3.0 | Módulo de Evidencias Legales</div>
            <div>Fecha de Emisión: <?= date('d-m-Y') ?></div>
        </footer>
    </div>

    <script>
        function exportarActa() {
            const element = document.getElementById('certificado');
            const options = {
                margin: 0,
                filename: 'Certificado_Haship_<?= $cert_id ?>.pdf',
                image: { type: 'jpeg', quality: 1 },
                html2canvas: { scale: 3, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(options).from(element).save();
        }
    </script>

</body>
</html>