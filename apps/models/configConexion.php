<?php
// ============================================================
// configConexion.php — Clase que gestiona la conexión a MySQL.
// Usa las constantes definidas en config.php.
// ============================================================

require_once __DIR__ . '/../config/config.php';

class Conexion {
    private $conexion;

    public function __construct() {
        // Fuerza que los errores de MySQLi se lancen como excepciones
        // en lugar de fallar silenciosamente.
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // UTF-8 completo para soportar caracteres especiales (ñ, acentos, emojis).
        $this->conexion->set_charset('utf8mb4');
    }

    // Devuelve el objeto mysqli activo.
    public function getConexion() {
        return $this->conexion;
    }

    // Cierra la conexión cuando ya no se necesita.
    public function cerrarConexion() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}
