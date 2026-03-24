<?php
/**
 * HASHIP PROJECT - Registro de Nodos Autorizados
 * Autor: Nico (Lead Developer)
 * Versión: 3.0 (Security Dashboard UI)
 */

require_once '../src/php/db.php';
require_once '../src/php/auth.php';

checkAuth();
if ($_SESSION['rol'] !== 'administrador') {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

$mensaje = "";
$tipo_alerta = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email  = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $pass   = $_POST['password'];
    $rol    = $_POST['rol'];

    if (!empty($nombre) && !empty($email) && !empty($pass)) {
        try {
            $password_hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $email, $password_hash, $rol]);
            
            $mensaje = "Nodo autorizado y registrado correctamente.";
            $tipo_alerta = "success";
        } catch (PDOException $e) {
            $mensaje = "Error crítico: El identificador (email) ya existe en la red.";
            $tipo_alerta = "error";
        }
    } else {
        $mensaje = "Todos los parámetros son obligatorios.";
        $tipo_alerta = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haship - Gestión de Nodos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --accent: #ed524a;
            --bg: #f5f5f7;
            --card-bg: rgba(255, 255, 255, 0.9);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg);
            background-image: radial-gradient(circle at 2px 2px, #e2e8f0 1px, transparent 0);
            background-size: 40px 40px;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0; 
            color: var(--primary);
        }
        
        .container { 
            background: var(--card-bg); 
            padding: 45px; 
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.06); 
            width: 100%; 
            max-width: 420px; 
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.5);
            position: relative;
        }

        .container::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(90deg, var(--accent), #ff3b30);
            border-radius: 24px 24px 0 0;
        }
        
        h2 { margin: 0 0 10px 0; font-size: 1.75rem; font-weight: 800; letter-spacing: -1px; }
        p.subtitle { color: #86868b; font-size: 0.95rem; margin-bottom: 30px; line-height: 1.4; }

        .form-group { margin-bottom: 20px; }
        
        label { 
            display: block; 
            font-size: 0.7rem; 
            font-weight: 700; 
            color: #64748b; 
            text-transform: uppercase; 
            margin-bottom: 8px; 
            letter-spacing: 0.05em;
        }
        
        input, select { 
            width: 100%; 
            padding: 14px 16px; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            font-family: inherit; 
            font-size: 0.95rem; 
            box-sizing: border-box;
            background: #fcfcfd;
            transition: all 0.2s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
        }

        .btn-submit { 
            width: 100%; 
            padding: 16px; 
            background: var(--primary); 
            color: white; 
            border: none; 
            border-radius: 14px; 
            font-weight: 700; 
            font-size: 0.95rem;
            cursor: pointer; 
            margin-top: 15px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
        }

        .btn-submit:hover { 
            background: #1e293b; 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
        }

        .alert { 
            padding: 14px; 
            border-radius: 12px; 
            font-size: 0.9rem; 
            font-weight: 600; 
            margin-bottom: 25px; 
            text-align: center;
            border: 1px solid transparent;
        }
        .alert-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        
        .back-btn { 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 25px; 
            color: #94a3b8; 
            text-decoration: none; 
            font-size: 0.85rem; 
            font-weight: 600; 
            transition: 0.2s;
        }
        .back-btn:hover { color: var(--accent); }

        .role-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #e2e8f0;
            border-radius: 4px;
            font-size: 0.7rem;
            vertical-align: middle;
            margin-left: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Autorizar Usuario</h2>
    <p class="subtitle">Genere nuevas credenciales de acceso para la infraestructura Haship.</p>

    <?php if($mensaje): ?>
        <div class="alert alert-<?= $tipo_alerta ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Identidad del Usuario</label>
            <input type="text" name="nombre" placeholder="Ej. Juan Pérez" required>
        </div>

        <div class="form-group">
            <label>Punto de Enlace (Email)</label>
            <input type="email" name="email" placeholder="usuario@haship.com" required>
        </div>

        <div class="form-group">
            <label>Contraseña de Red</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
            <label>Privilegios de Acceso</label>
            <select name="rol">
                <option value="usuario">Usuario Estándar</option>
                <option value="administrador">Admin</option>
            </select>
        </div>

        <button type="submit" class="btn-submit">DESPLEGAR CREDENCIALES</button>
        <a href="admin_usuarios.php" class="back-btn">
            <svg style="width:16px;height:16px;margin-right:8px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Volver al Panel de Control
        </a>
    </form>
</div>

</body>
</html>