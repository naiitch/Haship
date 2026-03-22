<?php
/**
 * HASHIP PROJECT - Portal de Acceso (Login)
 * Autor: Nico (Lead Developer)
 * Versión: 1.1
 * * DESCRIPCIÓN:
 * Punto de entrada principal de la aplicación. Implementa la lógica de 
 * redirección inteligente (si hay sesión activa, salta al dashboard) y 
 * la gestión de estados de error mediante parámetros GET.
 */

session_start();

/**
 * REDIRECCIÓN INTELIGENTE:
 * Si el motor de sesiones detecta un 'usuario_id', evitamos el re-login
 * para mejorar la experiencia de usuario (UX).
 */
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
    <title>Haship - Gestión de Evidencias Digitales</title>
    
    <link rel="stylesheet" href="static/estilo.css">
    
    <style>
        /**
         * UI DESIGN SYSTEM:
         * Estilos críticos para garantizar la estabilidad visual durante la demo.
         * Se utiliza una paleta de colores profesional (Slate & Blue) acorde 
         * a una herramienta de seguridad.
         */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background: #f4f7f6; /* Tono suave para reducir fatiga visual */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid #e1e4e8;
        }
        .login-card h1 { 
            margin: 0 0 5px 0; 
            color: #1a202c; 
            font-size: 2.2rem; 
            font-weight: 800;
        }
        .login-card p { 
            color: #718096; 
            margin-bottom: 30px; 
            font-size: 0.95rem;
        }
        
        .form-group { text-align: left; margin-bottom: 18px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #4a5568; 
            font-size: 0.85rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .form-control:focus { 
            border-color: #3182ce; 
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
            outline: none; 
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #3182ce;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 10px;
        }
        .btn-login:hover { background: #2c5282; }

        /* Componente de Alerta de Error */
        .error-msg {
            color: #c53030;
            background: #fff5f5;
            border: 1px solid #feb2b2;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <main class="login-card">
        <header>
            <h1>Haship</h1>
            <p>Certificación de Integridad Documental</p>
        </header>

        <?php 
        /**
         * LÓGICA DE NOTIFICACIONES:
         * Interceptamos códigos de error enviados por el controlador auth.php
         * para dar feedback inmediato al usuario.
         */
        if (isset($_GET['error'])): ?>
            <div class="error-msg" role="alert">
                <svg style="width:18px; height:18px; margin-right:8px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php 
                    echo ($_GET['error'] == 1) 
                         ? "Credenciales incorrectas. Inténtalo de nuevo." 
                         : "Acceso restringido. Por favor, inicia sesión.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../src/php/auth.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" 
                       placeholder="ejemplo@haship.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" 
                       placeholder="••••••••" required>
            </div>

            <button type="submit" name="login" class="btn-login">
                Entrar al sistema
            </button>
        </form>

        <footer style="margin-top: 30px; font-size: 0.7rem; color: #a0aec0; text-transform: uppercase; letter-spacing: 1.5px;">
            &copy; 2026 HASHIP PROJECT | Infraestructura de Confianza
        </footer>
    </main>

</body>
</html>