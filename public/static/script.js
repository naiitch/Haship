/**
 * HASHIP PROJECT - Lógica de Interfaz y UX (Frontend)
 * Autor: Nico (Lead Developer)
 * Versión: 1.7 (Performance Optimized)
 */

document.addEventListener('DOMContentLoaded', () => {
    
    /**
     * 1. PROTOCOLO DE NO REPUDIO
     */
    const formFirma = document.querySelector('form[action*="sign_doc.php"]');
    if (formFirma) {
        formFirma.addEventListener('submit', (e) => {
            if (!confirm('¿DECLARACIÓN DE CONFORMIDAD DIGITAL?\n\nAl proceder, usted vincula su identidad al hash SHA-256.')) {
                e.preventDefault(); 
            }
        });
    }

    /**
     * 2. VALIDACIÓN Y AUTO-SUBIDA
     */
    const inputFile = document.querySelector('input[type="file"]');
    if (inputFile) {
        inputFile.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const ext = this.files[0].name.split('.').pop().toLowerCase();
                if (ext !== 'pdf') {
                    alert('ERROR: Solo se admiten archivos PDF.');
                    this.value = ''; 
                    return;
                }
                this.closest('form')?.submit();
            }
        });
    }

    /**
     * 3. FEEDBACK DE CARGA
     */
    const uploadForm = document.querySelector('form[action*="upload.php"]');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            const btn = document.querySelector('.apple-btn');
            if (btn) {
                btn.innerHTML = `<span class="loader-spinner"></span> Certificando...`;
                btn.style.cssText = "background: #475569; pointer-events: none; opacity: 0.8;";
            }
        });
    }
});

/**
 * 4. ESTILOS DINÁMICOS (Centralizados para evitar saturación)
 */
const style = document.createElement('style');
style.innerHTML = `
    @keyframes spin { to { transform: rotate(360deg); } }

    .loader-spinner {
        border: 2px solid rgba(255,255,255,.3);
        border-top: 2px solid #fff;
        border-radius: 50%;
        width: 14px; height: 14px;
        animation: spin 0.8s linear infinite;
        display: inline-block; margin-right: 10px; vertical-align: middle;
    }

    /* EFECTOS DE INTERACCIÓN (Manejados por GPU, no por JS) */
    tbody tr { transition: all 0.2s ease; cursor: pointer; }
    tbody tr:hover td { background-color: rgba(0, 0, 0, 0.025) !important; }

    .stat-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.07) !important;
    }

    .apple-btn { transition: all 0.2s ease; }
    .apple-btn:hover { transform: scale(1.05); }
    .apple-btn:active { transform: scale(0.96); }
`;
document.head.appendChild(style);