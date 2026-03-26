<?php
/**
 * HASHIP PROJECT - Clase de Persistencia de Auditoría
 * RA4: Desarrollo orientado a objetos (Clases)
 * RA8: Persistencia en sistemas no relacionales (JSONL)
 */
class AuditLogger {
    private $path;

    // Constructor: Define dónde se guardará el archivo log
    public function __construct($path = '../../storage/audit_log.jsonl') {
        $this->path = $path;
    }

    /**
     * Método para registrar eventos
     * RA6: Uso de arrays asociativos y conversión a JSON
     */
    public function loguear($evento, $usuarioId, $detalles = []) {
        $logEntry = [
            "timestamp" => date("Y-m-d H:i:s"),
            "evento"    => $evento,
            "user_id"   => $usuarioId,
            "detalles"  => $detalles,
            "ip"        => $_SERVER['REMOTE_ADDR']
        ];

        // Convertimos el array a una línea JSON y la añadimos al archivo
        // FILE_APPEND: Si no existe, lo crea. Si existe, añade al final.
        // LOCK_EX: Evita que dos personas escriban a la vez (Integridad RA9).
        return file_put_contents($this->path, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}