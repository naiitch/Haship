<?php
// RA4: Aplicando principios de POO (Encapsulamiento)
class Documento {
    private $id;
    private $nombre;
    private $hash;

    public function __construct($id, $nombre, $hash) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->hash = $hash;
    }

    // RA2: Métodos de clase
    public function getInfoResumida() {
        return "Doc #{$this->id}: {$this->nombre} [Hash: " . substr($this->hash, 0, 8) . "...]";
    }
}