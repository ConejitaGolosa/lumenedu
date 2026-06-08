<?php
// ============================================================
// modelPago.php — Modelo de pagos y suscripciones.
// Registra pagos en la tabla Pago y gestiona upgrades de cuenta.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Pago {

    // Retorna el IdUsuario del primer Administrador activo.
    // Se usa como "receptor" del pago de suscripción a la plataforma.
    private static function getAdminId() {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT IdUsuario FROM Usuarios
             WHERE TipoUsuario = 'Administrador' AND EstadoCuenta = 'Activo'
             LIMIT 1"
        );
        $stmt->execute();
        $id = null;
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return $id;
    }

    // Inserta un registro en la tabla Pago.
    // $metodoPago puede ser 'PayPal' o 'PayPal_sandbox'.
    public static function registrar($idPagador, $monto, $metodoPago = 'PayPal') {
        $idReceptor = self::getAdminId() ?? $idPagador;

        $db    = new Conexion();
        $conn  = $db->getConexion();
        $fecha = date('Y-m-d H:i:s');
        $estado = 'Completado';

        $stmt = $conn->prepare(
            "INSERT INTO Pago (IdPagador, IdReceptor, Monto, FechaPago, EstadoPago, MetodoPago)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iidsss", $idPagador, $idReceptor, $monto, $fecha, $estado, $metodoPago);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Actualiza TipoUsuario a 'Suscriptor' para un EstudianteGratis.
    // No aplica a Creadores ni Administradores.
    public static function upgradeASuscriptor($idUsuario) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "UPDATE Usuarios SET TipoUsuario = 'Suscriptor'
             WHERE IdUsuario = ? AND TipoUsuario = 'EstudianteGratis'"
        );
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $ok = $stmt->errno === 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }
}
