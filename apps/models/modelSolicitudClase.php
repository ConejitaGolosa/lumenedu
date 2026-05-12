<?php
// ============================================================
// modelSolicitudClase.php — Solicitudes de clase virtual.
// El alumno suscrito propone fecha/hora al profesor.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class SolicitudClase {

    // El alumno crea una solicitud de clase con un profesor.
    // Retorna el IdSolicitud generado, o false en caso de error.
    public static function crear($idEstudiante, $idProfesor, $fechaPropuesta) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $query = "INSERT INTO SolicitudClase (IdEstudiante, IdProfesor, FechaPropuesta, Estado, FechaSolicitud)
                  VALUES (?, ?, ?, 'Pendiente', ?)";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("iiss", $idEstudiante, $idProfesor, $fechaPropuesta, $fecha);

        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            $db->cerrarConexion();
            return $id;
        }
        $stmt->close();
        $db->cerrarConexion();
        return false;
    }

    // El profesor responde una solicitud pendiente.
    // $estado: Aceptada | Rechazada | AceptadaConCondiciones
    public static function responder($idSolicitud, $idProfesor, $estado, $respuesta) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "UPDATE SolicitudClase
                  SET Estado=?, RespuestaProfesor=?
                  WHERE IdSolicitud=? AND IdProfesor=? AND Estado='Pendiente'";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("ssii", $estado, $respuesta, $idSolicitud, $idProfesor);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Solicitudes enviadas por el alumno (con nombre del profesor).
    public static function getDeEstudiante($idEstudiante) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT s.*, u.NombreUsuario AS Profesor
                  FROM SolicitudClase s JOIN Usuarios u ON u.IdUsuario = s.IdProfesor
                  WHERE s.IdEstudiante = ?
                  ORDER BY s.FechaSolicitud DESC";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idEstudiante);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Solicitudes recibidas por el profesor (con nombre del alumno).
    public static function getDeProfesor($idProfesor) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT s.*, u.NombreUsuario AS Estudiante
                  FROM SolicitudClase s JOIN Usuarios u ON u.IdUsuario = s.IdEstudiante
                  WHERE s.IdProfesor = ?
                  ORDER BY s.FechaSolicitud DESC";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idProfesor);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Obtiene el IdEstudiante de una solicitud (para enviarle la notificación de respuesta).
    public static function getEstudianteDeSolicitud($idSolicitud) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT IdEstudiante FROM SolicitudClase WHERE IdSolicitud=?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idSolicitud);
        $stmt->execute();
        $id = null;
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return $id;
    }
}
