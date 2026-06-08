<?php
require_once __DIR__ . '/configConexion.php';

class Amistad {

    // Enviar solicitud (retorna null si OK, string de error si no)
    public static function enviar(int $idSolicitante, int $idReceptor): ?string {
        if ($idSolicitante === $idReceptor) return 'No puedes enviarte una solicitud a ti mismo.';

        $db   = new Conexion();
        $conn = $db->getConexion();

        // Verificar si ya existe (en cualquier dirección)
        $chk = $conn->prepare(
            "SELECT Estado FROM Amistad
             WHERE (IdSolicitante = ? AND IdReceptor = ?)
                OR (IdSolicitante = ? AND IdReceptor = ?)
             LIMIT 1"
        );
        $chk->bind_param('iiii', $idSolicitante, $idReceptor, $idReceptor, $idSolicitante);
        $chk->execute();
        $estado = null;
        $chk->bind_result($estado);
        $chk->fetch();
        $chk->close();

        if ($estado === 'Aceptada') { $db->cerrarConexion(); return 'Ya son amigos.'; }
        if ($estado === 'Pendiente') { $db->cerrarConexion(); return 'Ya existe una solicitud pendiente.'; }

        $fecha = date('Y-m-d H:i:s');
        $stmt  = $conn->prepare(
            "INSERT INTO Amistad (IdSolicitante, IdReceptor, Estado, FechaSolicitud)
             VALUES (?, ?, 'Pendiente', ?)"
        );
        $stmt->bind_param('iis', $idSolicitante, $idReceptor, $fecha);
        $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return null;
    }

    // Aceptar solicitud (solo el receptor puede hacerlo)
    public static function aceptar(int $idAmistad, int $idReceptor): bool {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $fecha = date('Y-m-d H:i:s');
        $stmt = $conn->prepare(
            "UPDATE Amistad SET Estado = 'Aceptada', FechaRespuesta = ?
             WHERE IdAmistad = ? AND IdReceptor = ? AND Estado = 'Pendiente'"
        );
        $stmt->bind_param('sii', $fecha, $idAmistad, $idReceptor);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Rechazar solicitud o eliminar amistad
    public static function rechazar(int $idAmistad, int $idUsuario): bool {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "DELETE FROM Amistad
             WHERE IdAmistad = ?
               AND (IdSolicitante = ? OR IdReceptor = ?)"
        );
        $stmt->bind_param('iii', $idAmistad, $idUsuario, $idUsuario);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Solicitudes pendientes recibidas
    public static function getSolicitudesPendientes(int $idUsuario): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT a.IdAmistad, a.FechaSolicitud,
                    u.IdUsuario, u.NombreUsuario, u.TipoUsuario,
                    p.FotoPerfil
             FROM Amistad a
             JOIN Usuarios u ON u.IdUsuario = a.IdSolicitante
             LEFT JOIN Perfil p ON p.IdPerfil = u.IdUsuario
             WHERE a.IdReceptor = ? AND a.Estado = 'Pendiente'
             ORDER BY a.FechaSolicitud DESC"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Lista de amigos confirmados
    public static function getAmigos(int $idUsuario): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT u.IdUsuario, u.NombreUsuario, u.TipoUsuario, p.FotoPerfil,
                    a.IdAmistad
             FROM Amistad a
             JOIN Usuarios u ON u.IdUsuario = IF(a.IdSolicitante = ?, a.IdReceptor, a.IdSolicitante)
             LEFT JOIN Perfil p ON p.IdPerfil = u.IdUsuario
             WHERE (a.IdSolicitante = ? OR a.IdReceptor = ?)
               AND a.Estado = 'Aceptada'
             ORDER BY u.NombreUsuario"
        );
        $stmt->bind_param('iii', $idUsuario, $idUsuario, $idUsuario);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Estado de la relación entre dos usuarios: null / 'Pendiente' / 'Aceptada'
    // También retorna quién es el solicitante.
    public static function getRelacion(int $a, int $b): ?array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT IdAmistad, Estado, IdSolicitante
             FROM Amistad
             WHERE (IdSolicitante = ? AND IdReceptor = ?)
                OR (IdSolicitante = ? AND IdReceptor = ?)
             LIMIT 1"
        );
        $stmt->bind_param('iiii', $a, $b, $b, $a);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->cerrarConexion();
        return $row;
    }

    // Verifica si dos usuarios son amigos
    public static function sonAmigos(int $a, int $b): bool {
        $rel = self::getRelacion($a, $b);
        return $rel && $rel['Estado'] === 'Aceptada';
    }
}
