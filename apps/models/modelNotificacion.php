<?php
// ============================================================
// modelNotificacion.php — Notificaciones para usuarios.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Notificacion {

    // Crea una notificación para un usuario.
    // $tipo: VideoAprobado | VideoRechazado | SolicitudClase | RespuestaSolicitud
    public static function crear($idUsuario, $tipo, $mensaje, $idReferencia = null) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $query = "INSERT INTO Notificacion (IdUsuario, Tipo, Mensaje, FechaNotificacion, IdReferencia)
                  VALUES (?, ?, ?, ?, ?)";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("isssi", $idUsuario, $tipo, $mensaje, $fecha, $idReferencia);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Todas las notificaciones de un usuario, de más nueva a más antigua.
    public static function getByUsuario($idUsuario) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT * FROM Notificacion WHERE IdUsuario = ? ORDER BY FechaNotificacion DESC";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $notifs;
    }

    // Marca una notificación específica como leída (valida que pertenece al usuario).
    public static function marcarLeida($idNotificacion, $idUsuario) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "UPDATE Notificacion SET Leida=1 WHERE IdNotificacion=? AND IdUsuario=?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("ii", $idNotificacion, $idUsuario);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Marca todas las notificaciones del usuario como leídas.
    public static function marcarTodasLeidas($idUsuario) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "UPDATE Notificacion SET Leida=1 WHERE IdUsuario=? AND Leida=0";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idUsuario);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Cantidad de notificaciones no leídas (para el badge del nav).
    public static function countNoLeidas($idUsuario) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT COUNT(*) FROM Notificacion WHERE IdUsuario=? AND Leida=0";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return $count;
    }
}
