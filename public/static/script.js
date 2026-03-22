/**
 * HASHIP PROJECT - Lógica de Interfaz y UX (Frontend)
 * Autor: Nico (Lead Developer)
 * Versión: 1.1
 * -------------------------------------------------------------------------
 * DESCRIPCIÓN:
 * Este script gestiona la experiencia de usuario mediante la interceptación
 * de eventos del DOM. Implementa validaciones preventivas antes de que 
 * los datos lleguen al servidor y proporciona feedback visual durante
 * los procesos criptográficos.
 * -------------------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', () => {
    
    /**
     * 1. CONFIRMACIÓN DE FIRMA (Protocolo de No Repudio)
     * Antes de ejecutar sign_doc.php, obligamos al usuario a realizar una
     * acción consciente. Esto refuerza el valor legal de la evidencia.
     */
    const formFirma = document.querySelector('form[action*="sign_doc.php"]');
    if (formFirma) {
        formFirma.addEventListener('submit', (e) => {
            const mensajeConfirmacion = 
                '¿DECLARACIÓN DE CONFORMIDAD?\n\n' +
                'Al aceptar, usted certifica que ha revisado el documento y acepta vincular su identidad (IP y metadatos) ' +
                'al hash SHA-256 del archivo de forma permanente e inmutable.';
            
            if (!confirm(mensajeConfirmacion)) {
                e.preventDefault(); // Abortamos el envío del formulario
            }
        });
    }

    /**
     * 2. FILTRO DE INTEGRIDAD DE ARCHIVOS (Client-Side)
     * Validamos la extensión antes de la subida para ahorrar recursos del
     * servidor y evitar errores de procesamiento en el motor de Python.
     */
    const inputFile = document.querySelector('input[type="file"]');
    if (inputFile) {
        inputFile.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const extension = fileName.split('.').pop().toLowerCase();
                
                if (extension !== 'pdf') {
                    alert('ERROR DE SEGURIDAD: Haship solo procesa activos digitales en formato PDF para garantizar la integridad del hash.');
                    this.value = ''; // Limpiamos el selector para forzar una subida válida
                    return false;
                }
            }
        });
    }

    /**
     * 3. FEEDBACK DINÁMICO "THE PYTHON EFFECT"
     * El cálculo del hash SHA-256 es una operación de CPU. Para evitar
     * que el usuario piense que la web se ha colgado, transformamos el 
     * botón de subida en un indicador de progreso técnico.
     */
    const uploadForm = document.querySelector('form[action*="upload.php"]');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            
            // Inyectamos estados visuales de procesamiento
            if (btn) {
                btn.innerHTML = `
                    <span class="loader-spinner"></span> 
                    Invocando Motor Python: Calculando SHA-256...
                `;
                btn.style.background = '#4a5568';
                btn.style.cursor = 'wait';
                btn.disabled = true; // Evitamos duplicidad de envíos (Double Submit)
            }
            
            // Activamos el loader global del sistema
            const loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = 'block';
            }
        });
    }
});