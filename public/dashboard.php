<?php
/**
 * HASHIP PROJECT - Infraestructura de Auditoría Digital
 * Autor: Nico (Lead Developer)
 * Versión: 3.6.1 (Fusion: Soft Red UI + Full Upload Logic)
 */

require_once '../src/php/auth.php';
checkAuth(); 

require_once '../src/php/db.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['rol']; 
$usuario_nombre = $_SESSION['usuario_nombre'];

/**
 * LÓGICA DE FILTRADO DE ACTIVOS
 */
if ($usuario_rol === 'administrador') {
    $sql = "SELECT d.*, u_p.nombre as remitente, u_d.nombre as destinatario 
            FROM documentos d 
            LEFT JOIN usuarios u_p ON d.id_propietario = u_p.id 
            LEFT JOIN usuarios u_d ON d.id_destinatario = u_d.id 
            ORDER BY d.fecha_subida DESC";
    $stmt = $pdo->query($sql);
} else {
    $sql = "SELECT d.*, u_p.nombre as remitente, u_d.nombre as destinatario 
            FROM documentos d 
            LEFT JOIN usuarios u_p ON d.id_propietario = u_p.id 
            LEFT JOIN usuarios u_d ON d.id_destinatario = u_d.id 
            WHERE d.id_propietario = ? OR d.id_destinatario = ? 
            ORDER BY d.fecha_subida DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $usuario_id]);
}
$documentos = $stmt->fetchAll();

$total_docs = count($documentos);
$validados = count(array_filter($documentos, function($d) { return $d['estado'] == 'validado'; }));

// Información para el selector de destinatarios (v3.2 logic)
$stmt_users = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol != 'administrador' AND activo = 1");
$lista_destinatarios = $stmt_users->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHIP - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --apple-red: #ff3b30;
            --apple-bg: #f5f5f7;
            --apple-card: rgba(255, 255, 255, 0.85);
            --text-primary: #1d1d1f;
            --text-secondary: #86868b;
            --glass-border: rgba(255, 255, 255, 0.7);
        }

        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background-color: var(--apple-bg); 
            margin: 0; 
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            background-image: 
                radial-gradient(at 0% 0%, rgba(255, 59, 48, 0.08) 0px, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(255, 59, 48, 0.05) 0px, transparent 50%);
            min-height: 100vh;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }

        nav { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }

        .lang-switch { background: rgba(0,0,0,0.05); padding: 4px; border-radius: 12px; display: inline-flex; }
        .btn-lang { border: none; background: transparent; padding: 6px 12px; border-radius: 9px; font-size: 11px; font-weight: 700; cursor: pointer; color: var(--text-secondary); }
        .btn-lang.active { background: white; color: var(--text-primary); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .glass-card { 
            background: var(--apple-card); 
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-radius: 35px; 
            border: 1px solid var(--glass-border);
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            padding: 45px;
            margin-top: 10px;
        }

        .brand-box h1 { font-size: 42px; font-weight: 800; letter-spacing: -1.5px; margin: 0; }
        .brand-box span { color: var(--apple-red); }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 40px 0; }
        .stat-card {
            background: white; padding: 25px; border-radius: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.02);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08); border-color: rgba(255, 59, 48, 0.2); }
        .stat-card .label { font-size: 12px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.8px; }
        .stat-card .value { font-size: 34px; font-weight: 800; display: block; margin-top: 5px; }

        .apple-btn {
            background: var(--text-primary); color: white; padding: 12px 26px;
            border-radius: 980px; font-weight: 600; font-size: 14px;
            text-decoration: none; transition: all 0.3s ease; border: none;
            display: inline-flex; align-items: center; gap: 10px; cursor: pointer;
        }
        .apple-btn:hover { transform: scale(1.02); opacity: 0.9; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .apple-btn.secondary { background: rgba(0,0,0,0.05); color: var(--text-primary); }

        .table-area { margin-top: 45px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        td { padding: 22px 15px; border-bottom: 1px solid rgba(0,0,0,0.03); font-size: 15px; transition: background-color 0.2s ease; }
        tbody tr:hover td { background-color: rgba(0, 0, 0, 0.02); cursor: pointer; }

        .hash-tag { 
            font-family: 'SF Mono', monospace; background: rgba(255, 59, 48, 0.05); 
            padding: 5px 10px; border-radius: 8px; font-size: 12px; 
            color: var(--apple-red); font-weight: 600;
        }

        .badge { padding: 5px 14px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .badge.validado { background: #e8f5e9; color: #2e7d32; }
        .badge.pendiente { background: #fff3e0; color: #ef6c00; }

        /* MODAL DE SUBIDA */
        #upload-modal {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.3); backdrop-filter:blur(15px); -webkit-backdrop-filter:blur(15px); z-index:1000;
        }

        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="container">
        <nav>
            <div class="lang-switch">
                <button onclick="changeLang('es')" id="btn-es" class="btn-lang active">ES</button>
                <button onclick="changeLang('en')" id="btn-en" class="btn-lang">EN</button>
            </div>
            <div style="text-align: right;">
                <span data-key="user_label" style="font-size: 10px; font-weight: 800; color: var(--text-secondary);">USUARIO</span>
                <div style="font-weight: 700;"><?= htmlspecialchars($usuario_nombre) ?> <span style="color: var(--apple-red); font-size: 11px;">• <?= strtoupper($usuario_rol) ?></span></div>
            </div>
        </nav>

        <div class="glass-card">
            <header style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="brand-box">
                    <h1>Haship<span>.</span></h1>
                    <p class="tagline" data-key="tagline">Integridad & Seguridad</p>
                </div>
                <a href="../src/php/auth.php?action=logout" style="text-decoration: none; color: var(--apple-red); font-weight: 700; font-size: 13px;" data-key="btn_logout">DESCONECTAR</a>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="label" data-key="stat_total">Activos Totales</span>
                    <span class="value"><?= $total_docs ?></span>
                </div>
                <div class="stat-card">
                    <span class="label" data-key="stat_integrity">Verificados</span>
                    <span class="value" style="color: #34c759;"><?= $validados ?></span>
                </div>
                <div class="stat-card">
                    <span class="label" data-key="stat_crypto">Estándar Criptográfico</span>
                    <span class="value" style="color: var(--apple-red);">SHA-256</span>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button class="apple-btn" onclick="document.getElementById('upload-modal').style.display='block'">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                    <span data-key="btn_register">Nuevo Documento</span>
                </button>

                <?php if ($usuario_rol === 'administrador'): ?>
                    <a href="admin_usuarios.php" class="apple-btn secondary" data-key="btn_admin">Gestión de Usuarios</a>
                <?php endif; ?>
            </div>

            <div class="table-area">
                <table>
                    <thead>
                        <tr>
                            <th data-key="th_asset">Activo Digital</th>
                            <th data-key="th_fingerprint">Fingerprint (SHA-256)</th>
                            <th data-key="th_route">Ruta de Datos</th>
                            <th data-key="th_status">Estado</th>
                            <th data-key="th_audit">Auditoría</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documentos)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:50px; color:var(--text-secondary);">No hay evidencias registradas.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($documentos as $doc): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700;"><?= htmlspecialchars($doc['nombre_real']) ?></div>
                                <div style="font-size: 11px; color: var(--text-secondary);"><?= date("d/m/Y H:i", strtotime($doc['fecha_subida'])) ?></div>
                            </td>
                            <td><span class="hash-tag" title="<?= $doc['hash_seguridad'] ?>"><?= substr($doc['hash_seguridad'], 0, 10) ?>...</span></td>
                            <td>
                                <div style="font-size: 13px;">
                                    <span data-key="from" style="color: var(--text-secondary);">De:</span> <strong><?= htmlspecialchars($doc['remitente']) ?></strong><br>
                                    <span data-key="to" style="color: var(--text-secondary);">A:</span> <strong><?= htmlspecialchars($doc['destinatario'] ?? 'ROOT') ?></strong>
                                </div>
                            </td>
                            <td><span class="badge <?= strtolower($doc['estado']) ?>"><?= $doc['estado'] ?></span></td>
                            <td>
                                <a href="vista_doc.php?id=<?= $doc['id'] ?>" style="text-decoration: none; color: var(--apple-red); font-weight: 800; font-size: 12px; letter-spacing: 0.5px;" data-key="btn_inspect">ABRIR</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="upload-modal">
        <div class="glass-card" style="max-width:500px; margin: 80px auto; position:relative; padding: 35px;">
            <h2 style="margin-top:0; letter-spacing: -1px;">Registrar Evidencia</h2>
            <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 25px;">El archivo será procesado por el motor criptográfico para generar su huella digital única.</p>
            
            <form action="../src/php/upload.php" method="POST" enctype="multipart/form-data">
                <label style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase;">Seleccionar PDF</label>
                
                <input type="file" name="documento" accept=".pdf" required style="margin:10px 0 25px 0; display:block; width:100%; font-size: 13px;">
                
                <label style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase;">Destinatario de la Auditoría</label>
                <select name="id_destinatario" style="width:100%; padding:14px; border-radius:15px; margin:10px 0 20px 0; border:1px solid rgba(0,0,0,0.08); background:white; font-family:inherit; font-size: 14px;">
                    <option value="">Enviar a: ROOT (Administrador)</option>
                    <?php foreach ($lista_destinatarios as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase;">Mensaje de Contexto (Opcional)</label>
                <textarea name="mensaje_contexto" placeholder="Ej: Factura Q3 para auditoría interna..." style="width:100%; padding:14px; border-radius:15px; margin:10px 0 25px 0; border:1px solid rgba(0,0,0,0.08); background:white; font-family:inherit; font-size: 14px; resize: none; height: 80px;"></textarea>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="apple-btn" style="flex:1; justify-content: center; background: var(--apple-red);">
                        Certificar con Python
                    </button>
                    <button type="button" class="apple-btn secondary" onclick="document.getElementById('upload-modal').style.display='none'">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const langData = {
            es: {
                tagline: "Integridad & Seguridad",
                user_label: "USUARIO",
                stat_total: "Activos Totales",
                stat_integrity: "Verificados",
                stat_crypto: "Estándar",
                btn_register: "Nuevo Documento",
                btn_admin: "Gestión de Usuarios",
                btn_logout: "DESCONECTAR",
                th_asset: "Activo Digital",
                th_fingerprint: "Fingerprint (SHA-256)",
                th_route: "Ruta de Datos",
                th_status: "Estado",
                th_audit: "Auditoría",
                btn_inspect: "ABRIR",
                from: "De", to: "A"
            },
            en: {
                tagline: "Integrity & Security",
                user_label: "ACCOUNT",
                stat_total: "Total Assets",
                stat_integrity: "Verified",
                stat_crypto: "Cryptographic Standard",
                btn_register: "New Document",
                btn_admin: "User Management",
                btn_logout: "LOGOUT",
                th_asset: "Digital Asset",
                th_fingerprint: "Fingerprint (SHA-256)",
                th_route: "Data Route",
                th_status: "Status",
                th_audit: "Audit",
                btn_inspect: "OPEN",
                from: "From", to: "To"
            }
        };

        function changeLang(lang) {
            localStorage.setItem('haship_lang', lang);
            document.querySelectorAll('[data-key]').forEach(el => {
                const key = el.getAttribute('data-key');
                el.innerText = langData[lang][key] || el.innerText;
            });
            document.getElementById('btn-es').classList.toggle('active', lang === 'es');
            document.getElementById('btn-en').classList.toggle('active', lang === 'en');
        }

        window.onload = () => {
            const savedLang = localStorage.getItem('haship_lang') || 'es';
            changeLang(savedLang);
        }
    </script>
</body>
</html>