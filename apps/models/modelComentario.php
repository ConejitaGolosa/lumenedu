<?php
// ============================================================
// modelComentario.php — Comentarios en videos y foros.
// Soporta respuestas anidadas (un nivel) via IdComentarioPadre.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Comentario {

    // Agrega un comentario en un video o foro con soporte de respuestas.
    // $idVideo o $idForo deben proveerse (el otro queda null).
    // $idPadre: id del comentario al que se responde, o null para comentario raíz.
    // Retorna el IdComentario generado, o false en error.
    public static function agregar($idVideo, $idUsuario, $contenido, $idForo = null, $idPadre = null) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $stmt  = $conn->prepare(
            "INSERT INTO Comentario (IdVideo, IdForo, IdComentarioPadre, IdUsuario, Contenido, FechaComentario)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiiiss", $idVideo, $idForo, $idPadre, $idUsuario, $contenido, $fecha);
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

    // Comentarios raíz de un video (sin contar respuestas anidadas en este nivel).
    public static function getByVideo($idVideo) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT c.IdComentario, c.Contenido, c.FechaComentario, c.IdUsuario,
                    u.NombreUsuario, u.TipoUsuario, p.FotoPerfil
             FROM Comentario c
             JOIN Usuarios u ON u.IdUsuario = c.IdUsuario
             LEFT JOIN Perfil p ON p.IdPerfil = c.IdUsuario
             WHERE c.IdVideo = ? AND c.IdComentarioPadre IS NULL
             ORDER BY c.FechaComentario ASC"
        );
        $stmt->bind_param("i", $idVideo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Comentarios raíz de un foro.
    public static function getByForo($idForo) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT c.IdComentario, c.Contenido, c.FechaComentario, c.IdUsuario,
                    u.NombreUsuario, u.TipoUsuario, p.FotoPerfil
             FROM Comentario c
             JOIN Usuarios u ON u.IdUsuario = c.IdUsuario
             LEFT JOIN Perfil p ON p.IdPerfil = c.IdUsuario
             WHERE c.IdForo = ? AND c.IdComentarioPadre IS NULL
             ORDER BY c.FechaComentario ASC"
        );
        $stmt->bind_param("i", $idForo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Respuestas directas a un comentario.
    public static function getRespuestas($idPadre) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT c.IdComentario, c.Contenido, c.FechaComentario, c.IdUsuario,
                    u.NombreUsuario, u.TipoUsuario, p.FotoPerfil
             FROM Comentario c
             JOIN Usuarios u ON u.IdUsuario = c.IdUsuario
             LEFT JOIN Perfil p ON p.IdPerfil = c.IdUsuario
             WHERE c.IdComentarioPadre = ?
             ORDER BY c.FechaComentario ASC"
        );
        $stmt->bind_param("i", $idPadre);
        $stmt->execute();
        $respuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $respuestas;
    }

    // Devuelve IdVideo e IdForo del comentario (para construir el enlace desde notificaciones).
    public static function getContexto($idComentario) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare("SELECT IdVideo, IdForo FROM Comentario WHERE IdComentario = ?");
        $stmt->bind_param("i", $idComentario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->cerrarConexion();
        return $row; // ['IdVideo' => X|null, 'IdForo' => X|null]
    }

    // Comentarios y respuestas nuevos desde cierto IdComentario (para polling AJAX).
    // $tipo: 'video' | 'foro'
    public static function getDesde(string $tipo, int $idContenido, int $desdeId): array {
        $db   = new Conexion();
        $conn = $db->getConexion();

        if ($tipo === 'video') {
            $stmt = $conn->prepare(
                "SELECT c.IdComentario, c.IdComentarioPadre, c.Contenido, c.FechaComentario,
                        u.NombreUsuario, u.TipoUsuario, p.FotoPerfil
                 FROM Comentario c
                 JOIN Usuarios u ON u.IdUsuario = c.IdUsuario
                 LEFT JOIN Perfil p ON p.IdPerfil = c.IdUsuario
                 WHERE c.IdVideo = ? AND c.IdComentario > ?
                 ORDER BY c.FechaComentario ASC"
            );
        } else {
            $stmt = $conn->prepare(
                "SELECT c.IdComentario, c.IdComentarioPadre, c.Contenido, c.FechaComentario,
                        u.NombreUsuario, u.TipoUsuario, p.FotoPerfil
                 FROM Comentario c
                 JOIN Usuarios u ON u.IdUsuario = c.IdUsuario
                 LEFT JOIN Perfil p ON p.IdPerfil = c.IdUsuario
                 WHERE c.IdForo = ? AND c.IdComentario > ?
                 ORDER BY c.FechaComentario ASC"
            );
        }
        $stmt->bind_param("ii", $idContenido, $desdeId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // ID máximo de comentario en un video o foro (para inicializar el cursor de polling).
    public static function getMaxId(string $tipo, int $idContenido): int {
        $db   = new Conexion();
        $conn = $db->getConexion();

        if ($tipo === 'video') {
            $stmt = $conn->prepare("SELECT COALESCE(MAX(IdComentario),0) FROM Comentario WHERE IdVideo = ?");
        } else {
            $stmt = $conn->prepare("SELECT COALESCE(MAX(IdComentario),0) FROM Comentario WHERE IdForo = ?");
        }
        $stmt->bind_param("i", $idContenido);
        $stmt->execute();
        $id = 0;
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return (int)$id;
    }

    // Devuelve el IdUsuario autor de un comentario (para notificarle cuando le responden).
    public static function getAutor($idComentario) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare("SELECT IdUsuario FROM Comentario WHERE IdComentario = ?");
        $stmt->bind_param("i", $idComentario);
        $stmt->execute();
        $id = null;
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return $id;
    }
}
