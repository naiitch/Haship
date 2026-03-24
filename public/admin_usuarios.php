<?php
/**
 * HASHIP PROJECT - Centro de Control de Identidades
 * Autor: Nico (Lead Developer)
 * Versión: 2.4 (Logic & High Fidelity UI)
 * * DESCRIPCIÓN:
 * Interfaz administrativa de alta fidelidad. 
 * Implementa borrado lógico con feedback visual refinado.
 */

require_once '../src/php/db.php';
require_once '../src/php/auth.php';

checkAuth();

if ($_SESSION['rol'] !== 'administrador') {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

$stmt = $pdo->query("SELECT id, nombre, email, rol, fecha_registro, activo FROM usuarios ORDER BY activo DESC, fecha_registro DESC");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHIP - Usuarios</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --apple-blue: #007aff;
            --apple-red: #ff3b30;
            --apple-green: #34c759;
            --apple-bg: #f5f5f7;
            --text-primary: #1d1d1f;
            --text-secondary: #86868b;
            --glass-card: rgba(255, 255, 255, 0.7);
        }

        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background-color: var(--apple-bg); 
            margin: 0; 
            padding: 40px 20px;
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 1000px; margin: 0 auto; }

        /* --- NAVIGATION --- */
        .back-nav { margin-bottom: 30px; }
        .back-link { 
            text-decoration: none; 
            color: var(--apple-red); 
            font-size: 14px; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
            transition: opacity 0.2s;
        }
        .back-link:hover { opacity: 0.7; }

        /* --- HEADER --- */
        .admin-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end; 
            margin-bottom: 40px; 
        }
        .admin-header h1 { font-size: 34px; font-weight: 800; letter-spacing: -1.2px; margin: 0; }
        .admin-header p { color: var(--text-secondary); margin: 5px 0 0 0; font-size: 16px; font-weight: 500; }

        .btn-add {
            background: var(--text-primary);
            color: white;
            padding: 10px 20px;
            border-radius: 980px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-add:hover { opacity: 0.8; transform: scale(0.98); }

        /* --- TABLE CARD --- */
        .glass-panel { 
            background: var(--glass-card); 
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 24px; 
            border: 1px solid rgba(255,255,255,0.7);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            padding: 20px 24px; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            color: var(--text-secondary); 
            font-weight: 700;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        td { padding: 20px 24px; font-size: 14px; border-bottom: 1px solid rgba(0,0,0,0.03); vertical-align: middle; }
        
        tr.user-suspended { background: rgba(0,0,0,0.02); }
        tr.user-suspended td { color: var(--text-secondary); }

        /* --- COMPONENTS --- */
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 700; color: var(--text-primary); }
        .user-email { font-size: 12px; color: var(--text-secondary); font-weight: 400; }

        .badge {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-admin { background: #f2f2f7; color: #1d1d1f; border: 1px solid rgba(0,0,0,0.05); }
        .badge-user { background: white; color: var(--apple-blue); border: 1px solid rgba(0,122,255,0.1); }

        .status-pill {
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dot { width: 7px; height: 7px; border-radius: 50%; }
        .dot-active { background: var(--apple-green); box-shadow: 0 0 8px var(--apple-green); }
        .dot-inactive { background: var(--text-secondary); }

        /* --- ACTIONS --- */
        .action-group { display: flex; gap: 8px; justify-content: flex-end; }
        .btn-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .btn-circle:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-suspend { color: var(--apple-red); }
        .btn-restore { color: var(--apple-green); }

        @media (max-width: 768px) {
            .admin-header { flex-direction: column; align-items: flex-start; gap: 20px; }
            th:nth-child(4), td:nth-child(4) { display: none; }
        }
    </style>
</head>
<body>

    <div class="container">
        <nav class="back-nav">
            <a href="dashboard.php" class="back-link">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Dashboard
            </a>
        </nav>

        <header class="admin-header">
            <div>
                <h1>Usuarios</h1>
                <p>Nodos de acceso a la infraestructura.</p>
            </div>
            <a href="registro_usuario.php" class="btn-add">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-width="2.5" stroke-linecap="round"/></svg>
                Nuevo Usuario
            </a>
        </header>

        <div class="glass-panel">
            <table>
                <thead>
                    <tr>
                        <th>Identidad</th>
                        <th>Estado</th>
                        <th>Privilegios</th>
                        <th>Registro</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr class="<?= !$u['activo'] ? 'user-suspended' : '' ?>">
                        <td>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($u['nombre']) ?></span>
                                <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="status-pill">
                                <span class="dot <?= $u['activo'] ? 'dot-active' : 'dot-inactive' ?>"></span>
                                <span style="color: <?= $u['activo'] ? 'var(--text-primary)' : 'var(--text-secondary)' ?>">
                                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $u['rol'] === 'administrador' ? 'badge-admin' : 'badge-user' ?>">
                                <?= strtoupper($u['rol']) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 13px; font-weight: 500; opacity: 0.6;">
                                <?= date('M Y', strtotime($u['fecha_registro'])) ?>
                            </span>
                        </td>
                        <td class="action-group">
                            <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                <?php if ($u['activo']): ?>
                                    <button onclick="gestionarUsuario(<?= $u['id'] ?>, 'suspend')" class="btn-circle btn-suspend" title="Suspender">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" stroke-width="2" stroke-linecap="round"/></svg>
                                    </button>
                                <?php else: ?>
                                    <button onclick="gestionarUsuario(<?= $u['id'] ?>, 'restore')" class="btn-circle btn-restore" title="Activar">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 11px; font-weight: 700; color: var(--apple-blue); letter-spacing: 0.5px;">PROPIO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function gestionarUsuario(id, accion) {
            const msg = (accion === 'suspend') 
                ? 'El acceso de este usuario será revocado. ¿Continuar?' 
                : 'Se restaurará el acceso al sistema. ¿Confirmar?';
            
            if (confirm(msg)) {
                const script = (accion === 'suspend') ? 'delete_user.php' : 'restore_user.php';
                window.location.href = '../src/php/' + script + '?id=' + id;
            }
        }
    </script>
</body>
</html>