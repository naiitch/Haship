<?php
/**
 * HASHIP PROJECT - Terminal de Acceso de Seguridad
 * Autor: Nico (Lead Developer)
 * Versión: 3.4 (Apple Aesthetics Refresh)
 * * DESCRIPCIÓN:
 * Punto de entrada único con diseño minimalista, tipografía refinada
 * y feedback visual de protocolos mediante acentos en rojo Apple.
 */

session_start();

// Si ya hay sesión, saltamos directamente al nodo central
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHIP - Secure Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --apple-red: #ff3b30; /* Rojo oficial Apple */
            --apple-bg: #f5f5f7;
            --text-primary: #1d1d1f;
            --text-secondary: #86868b;
            --input-bg: rgba(0, 0, 0, 0.04);
            --glass-border: rgba(255, 255, 255, 0.7);
        }

        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background-color: var(--apple-bg); 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0; 
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            /* Gradiente sutil para dar profundidad */
            background-image: radial-gradient(circle at center, #ffffff 0%, #f5f5f7 100%);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 50px 40px;
            border-radius: 32px;
            border: 1px solid var(--glass-border);
            width: 100%;
            max-width: 360px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            text-align: center;
        }

        /* --- BRANDING --- */
        .brand { margin-bottom: 35px; }
        .brand img { width: 55px; margin-bottom: 15px; }
        .brand h1 { font-size: 28px; font-weight: 800; letter-spacing: -1px; margin: 0; }
        .brand p { color: var(--text-secondary); font-size: 14px; margin-top: 5px; font-weight: 500; }

        /* --- FORM ELEMENTS --- */
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { 
            display: block; 
            font-size: 12px; 
            font-weight: 700; 
            color: var(--text-secondary); 
            margin-bottom: 8px; 
            margin-left: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            background: var(--input-bg);
            border: 1px solid transparent;
            border-radius: 14px;
            color: var(--text-primary);
            font-size: 15px;
            font-weight: 500;
            box-sizing: border-box;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            outline: none;
            background: white;
            border-color: rgba(0,0,0,0.1);
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.03);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--apple-red);
            color: white;
            border: none;
            border-radius: 980px; /* Pill shape */
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 15px;
        }

        .btn-login:hover { 
            opacity: 0.9; 
            transform: scale(0.98); 
        }

        /* --- FEEDBACK MESSAGES --- */
        .alert {
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
            border: 1px solid transparent;
        }
        .alert-error { 
            background: rgba(255, 59, 48, 0.1); 
            color: var(--apple-red); 
            border-color: rgba(255, 59, 48, 0.1); 
        }
        .alert-success { 
            background: rgba(52, 199, 89, 0.1); 
            color: #248a3d; 
            border-color: rgba(52, 199, 89, 0.1); 
        }

        .footer-tag { 
            margin-top: 40px; 
            font-size: 11px; 
            color: var(--text-secondary); 
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand">
            <img src="../assets/img/logo_rojo.png" alt="Haship Logo">
            <h1>Haship</h1>
            <p>Acceso Seguro a Evidencias</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    if($_GET['error'] == 1) echo "CREDENCIALES INVÁLIDAS";
                    if($_GET['error'] == 2) echo "SESIÓN EXPIRADA";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success">
                SESIÓN FINALIZADA
            </div>
        <?php endif; ?>

        <form action="../src/php/auth.php" method="POST">
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="nombre@dominio.com" required autofocus>
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" name="login" class="btn-login">
                Iniciar Protocolo
            </button>
        </form>

        <div class="footer-tag">
            Nico v3.4 // Secure Infrastructure
        </div>
    </div>

</body>
</html>