<?php
/**
 * HASHIP PROJECT - Gestión de Nodos de Red
 * Autor: Nico (Lead Developer)
 * Versión: 2.0 (UI Refresh)
 */

require_once '../src/php/auth.php';
checkAuth(); 

if ($_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: dashboard.php?msg=error");
    exit();
}

require_once '../src/php/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_usuario'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $rol = $_POST['rol'];

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
    $msg = $stmt->execute([$nombre, $email, $pass, $rol]) ? "success" : "error";
}

$usuarios = $pdo->query("SELECT id, nombre, email, rol, fecha_registro FROM usuarios ORDER BY rol ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHIP - Gestión de Nodos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --apple-blue: #007aff;
            --apple-bg: #f5f5f7;
            --text-primary: #1d1d1f;
            --text-secondary: #86868b;
            --glass-card: rgba(255, 255, 255, 0.8);
        }

        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: var(--apple-bg); 
            margin: 0; 
            padding: 40px 20px; 
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 900px; margin: auto; }

        .back-nav { margin-bottom: 25px; }
        .back-link { 
            text-decoration: none; 
            color: var(--apple-blue); 
            font-size: 14px; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center; 
            gap: 5px; 
        }

        .main-card { 
            background: var(--glass-card); 
            backdrop-filter: blur(20px) saturate(180%);
            padding: 40px; 
            border-radius: 30px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.03); 
            border: 1px solid rgba(255,255,255,0.7); 
        }

        .header-section { margin-bottom: 35px; }
        .header-section h2 { font-size: 28px; font-weight: 800; letter-spacing: -1px; margin: 0; }
        .header-section p { color: var(--text-secondary); margin-top: 5px; font-weight: 500; }

        /* Formulario Estilo Settings de iOS */
        .admin-form { 
            background: rgba(0,0,0,0.02); 
            padding: 25px; 
            border-radius: 20px; 
            margin-bottom: 45px; 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 20px; 
        }
        
        .form-group label { 
            display: block; 
            font-size: 11px; 
            font-weight: 700; 
            color: var(--text-secondary); 
            text-transform: uppercase; 
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .form-control { 
            width: 100%; 
            padding: 12px 15px; 
            border-radius: 12px; 
            border: 1px solid rgba(0,0,0,0.1); 
            background: white;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus { border-color: var(--apple-blue); }

        .btn-add { 
            grid-column: span 2; 
            background: var(--text-primary); 
            color: white; 
            padding: 14px; 
            border: none; 
            border-radius: 12px; 
            font-weight: 600; 
            font-size: 15px;
            cursor: pointer; 
            transition: all 0.2s; 
            margin-top: 5px;
        }

        .btn-add:hover { opacity: 0.9; transform: scale(0.99); }

        /* Tabla Refinada */
        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            font-size: 11px; 
            color: var(--text-secondary); 
            text-transform: uppercase; 
            padding: 15px 10px; 
            border-bottom: 1px solid rgba(0,0,0,0.05); 
            letter-spacing: 0.5px;
        }
        td { padding: 18px 10px; border-bottom: 1px solid rgba(0,0,0,0.03); font-size: 14px; }
        
        .role-badge { 
            padding: 4px 10px; 
            border-radius: 8px; 
            font-size: 10px; 
            font-weight: 700; 
            letter-spacing: 0.3px;
        }
        .role-admin { background: #ffefee; color: #ff3b30; }
        .role-user { background: #eef6ff; color: #007aff; }
        
        .user-name { font-weight: 700; display: block; }
        .user-email { font-size: 12px; color: var(--text-secondary); font-weight: 400; }
    </style>
</head>
<body>

    <div class="container">
        <nav class="back-nav">
            <a href="dashboard.php" class="back-link">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver al Terminal
            </a>
        </nav>

        <div class="main-card">
            <header class="header-section">
                <h2>Administración de Nodos</h2>
                <p>Configuración de identidades en la red Haship.</p>
            </header>

            <form action="" method="POST" class="admin-form">
                <div class="form-group">
                    <label>Nombre del Nodo</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre completo" required>
                </div>
                <div class="form-group">
                    <label>Identidad de Correo</label>
                    <input type="email" name="email" class="form-control" placeholder="email@dominio.com" required>
                </div>
                <div class="form-group">
                    <label>Clave de Acceso</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="form-group">
                    <label>Privilegios</label>
                    <select name="rol" class="form-control">
                        <option value="destinatario">Destinatario</option>
                        <option value="remitente">Remitente</option>
                        <option value="administrador">Administrador</option>
                        
                    </select>
                </div>
                <button type="submit" name="nuevo_usuario" class="btn-add">Autorizar Nodo en Red</button>
            </form>

            <div class="table-container">
                <h3 style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 20px;">INFRAESTRUCTURA ACTUAL</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nivel</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <span class="user-name"><?= htmlspecialchars($u['nombre']) ?></span>
                                <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                            </td>
                            <td>
                                <span class="role-badge <?= $u['rol'] === 'administrador' ? 'role-admin' : 'role-user' ?>">
                                    <?= strtoupper($u['rol']) ?>
                                </span>
                            </td>
                            <td style="font-size: 12px; color: var(--text-secondary); font-weight: 500;">
                                <?= date('d M, Y', strtotime($u['fecha_registro'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>