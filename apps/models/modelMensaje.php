<?php
require_once __DIR__ . '/configConexion.php';

class Mensaje {

    // Enviar mensaje directo
    public static function enviar(int $idEmisor, int $idReceptor, string $contenido): bool {
        $contenido = mb_substr(trim($contenido), 0, 1024);
        if (!$contenido) return false;

        $db    = new Conexion();
        $conn  = $db->getConexion();
        $fecha = date('Y-m-d H:i:s');
        $stmt  = $conn->prepare(
            "INSERT INTO Mensaje (IdEmisor, IdReceptor, ContenidoMensaje, FechaMensaje, EstadoMensaje)
             VALUES (?, ?, ?, ?, 'NoLeido')"
        );
        $stmt->bind_param('iiss', $idEmisor, $idReceptor, $contenido, $fecha);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Conversación entre dos usuarios (todos los mensajes, cronológico)
    public static function getConversacion(int $idA, int $idB): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT m.IdMensaje, m.IdEmisor, m.IdReceptor,
                    m.ContenidoMensaje, m.FechaMensaje, m.EstadoMensaje,
                    u.NombreUsuario AS NombreEmisor, u.TipoUsuario AS TipoEmisor,
                    p.FotoPerfil
             FROM Mensaje m
             JOIN Usuarios u ON u.IdUsuario = m.IdEmisor
             LEFT JOIN Perfil p ON p.IdPerfil = m.IdEmisor
             WHERE (m.IdEmisor = ? AND m.IdReceptor = ?)
                OR (m.IdEmisor = ? AND m.IdReceptor = ?)
             ORDER BY m.FechaMensaje ASC"
        );
        $stmt->bind_param('iiii', $idA, $idB, $idB, $idA);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Marcar como leídos los mensajes recibidos por idA
        $upd = $conn->prepare(
            "UPDATE Mensaje SET EstadoMensaje = 'Leido'
             WHERE IdEmisor = ? AND IdReceptor = ? AND EstadoMensaje = 'NoLeido'"
        );
        $upd->bind_param('ii', $idB, $idA);
        $upd->execute();
        $upd->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Lista de conversaciones del usuario (un registro por contacto, el último mensaje)
    public static function getConversaciones(int $idUsuario): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT
                u.IdUsuario, u.NombreUsuario, u.TipoUsuario,
                p.FotoPerfil,
                sub.ContenidoMensaje AS UltimoMensaje,
                sub.FechaMensaje,
                SUM(CASE WHEN m2.IdReceptor = ? AND m2.EstadoMensaje = 'NoLeido' THEN 1 ELSE 0 END) AS NoLeidos
             FROM (
                SELECT
                    IF(IdEmisor = ?, IdReceptor, IdEmisor) AS OtroUsuario,
                    ContenidoMensaje, FechaMensaje
                FROM Mensaje
                WHERE IdEmisor = ? OR IdReceptor = ?
                ORDER BY FechaMensaje DESC
             ) sub
             JOIN Usuarios u ON u.IdUsuario = sub.OtroUsuario
             LEFT JOIN Perfil p ON p.IdPerfil = u.IdUsuario
             JOIN Mensaje m2 ON (m2.IdEmisor = u.IdUsuario AND m2.IdReceptor = ?)
                             OR (m2.IdEmisor = ? AND m2.IdReceptor = u.IdUsuario)
             GROUP BY u.IdUsuario, u.NombreUsuario, u.TipoUsuario, p.FotoPerfil, sub.ContenidoMensaje, sub.FechaMensaje
             ORDER BY sub.FechaMensaje DESC"
        );
        $stmt->bind_param('iiiiii', $idUsuario, $idUsuario, $idUsuario, $idUsuario, $idUsuario, $idUsuario);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Cuenta mensajes no leídos para el badge del nav
    public static function countNoLeidos(int $idUsuario): int {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM Mensaje
             WHERE IdReceptor = ? AND EstadoMensaje = 'NoLeido'"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return (int)$count;
    }
}
