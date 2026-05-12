<?php
// ============================================================
// modelComentario.php — Comentarios en videos.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Comentario {

    // Agrega un comentario de un usuario en un video.
    public static function agregar($idVideo, $idUsuario, $contenido) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $query = "INSERT INTO Comentario (IdVideo, IdUsuario, Contenido, FechaComentario)
                  VALUES (?, ?, ?, ?)";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("iiss", $idVideo, $idUsuario, $contenido, $fecha);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Todos los comentarios de un video con nombre y tipo del autor.
    public static function getByVideo($idVideo) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT c.IdComentario, c.Contenido, c.FechaComentario,
                         u.NombreUsuario, u.TipoUsuario
                  FROM Comentario c
                  JOIN Usuarios u ON u.IdUsuario = c.IdUsuario
                  WHERE c.IdVideo = ?
                  ORDER BY c.FechaComentario ASC";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idVideo);
        $stmt->execute();
        $comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $comentarios;
    }
}
