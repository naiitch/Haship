/haship
│
├── /public <-- Única carpeta accesible desde el navegador
│ ├── index.php <-- Punto de entrada (Login)
│ ├── dashboard.php <-- Panel principal (Admin/Cliente)
│ ├── view_doc.php <-- Visualizador de PDF y botón de firma
│ ├── /assets <-- Archivos estáticos
│ │ ├── /css <-- style.css (Diseño moderno)
│ │ ├── /js <-- script.js (Validaciones y alertas)
│ │ └── /img <-- Logo de Haship e iconos
│ └── .htaccess <-- Seguridad para ocultar extensiones .php
│
├── /src <-- Lógica interna (No accesible desde URL)
│ ├── /php <-- Procesos de backend
│ │ ├── db.php <-- Conexión PDO a MySQL
│ │ ├── auth.php <-- Gestión de sesiones y Login/Logout
│ │ ├── upload.php <-- Lógica de subida y movimiento de archivos
│ │ └── sign_doc.php <-- Lógica para registrar la firma y evidencias
│ └── /python <-- El motor de integridad
│ └── hasher.py <-- Script que genera el SHA-256
│
├── /storage <-- Almacenamiento de archivos
│ └── /uploads <-- Los PDFs reales (Protegidos)
│ └── .htaccess <-- "Deny from all" (Para que nadie entre por URL)
│
├── /sql <-- Cimientos de datos
│ └── database.sql <-- Esquema de tablas y datos de prueba
│
├── .gitignore <-- Para no subir basura a GitHub
├── LICENSE <-- Licencia MIT
└── README.md <-- Documentación y guía de instalación
