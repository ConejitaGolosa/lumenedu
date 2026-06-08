<?php
require_once __DIR__ . '/configConexion.php';

class Grupo {

    // Crear grupo e insertar al creador como primer miembro
    public static function crear(int $idCreador, string $nombre): int {
        $nombre = mb_substr(trim($nombre), 0, 64);
        if (!$nombre) return 0;

        $db    = new Conexion();
        $conn  = $db->getConexion();
        $fecha = date('Y-m-d H:i:s');

        $stmt = $conn->prepare(
            "INSERT INTO Grupo (Nombre, IdCreador, FechaCreacion) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('sis', $nombre, $idCreador, $fecha);
        $stmt->execute();
        $idGrupo = (int)$conn->insert_id;
        $stmt->close();

        if ($idGrupo) {
            $ins = $conn->prepare(
                "INSERT IGNORE INTO MiembroGrupo (IdGrupo, IdUsuario, FechaUnion) VALUES (?, ?, ?)"
            );
            $ins->bind_param('iis', $idGrupo, $idCreador, $fecha);
            $ins->execute();
            $ins->close();
        }

        $db->cerrarConexion();
        return $idGrupo;
    }

    // Agregar miembro
    public static function agregarMiembro(int $idGrupo, int $idUsuario): bool {
        $db    = new Conexion();
        $conn  = $db->getConexion();
        $fecha = date('Y-m-d H:i:s');
        $stmt  = $conn->prepare(
            "INSERT IGNORE INTO MiembroGrupo (IdGrupo, IdUsuario, FechaUnion) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iis', $idGrupo, $idUsuario, $fecha);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Grupos en los que participa un usuario
    public static function getMisGrupos(int $idUsuario): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT g.IdGrupo, g.Nombre, g.IdCreador, g.FechaCreacion,
                    (SELECT COUNT(*) FROM MiembroGrupo mg WHERE mg.IdGrupo = g.IdGrupo) AS TotalMiembros,
                    (SELECT MAX(FechaEnvio) FROM MensajeGrupo msg WHERE msg.IdGrupo = g.IdGrupo) AS UltimoMensaje
             FROM Grupo g
             JOIN MiembroGrupo m ON m.IdGrupo = g.IdGrupo
             WHERE m.IdUsuario = ?
             ORDER BY UltimoMensaje DESC, g.FechaCreacion DESC"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Datos de un grupo (solo si el usuario es miembro)
    public static function getById(int $idGrupo, int $idUsuario): ?array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT g.IdGrupo, g.Nombre, g.IdCreador, g.FechaCreacion
             FROM Grupo g
             JOIN MiembroGrupo m ON m.IdGrupo = g.IdGrupo AND m.IdUsuario = ?
             WHERE g.IdGrupo = ?
             LIMIT 1"
        );
        $stmt->bind_param('ii', $idUsuario, $idGrupo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->cerrarConexion();
        return $row ?: null;
    }

    // Miembros de un grupo
    public static function getMiembros(int $idGrupo): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT u.IdUsuario, u.NombreUsuario, u.TipoUsuario, p.FotoPerfil, mg.FechaUnion
             FROM MiembroGrupo mg
             JOIN Usuarios u ON u.IdUsuario = mg.IdUsuario
             LEFT JOIN Perfil p ON p.IdPerfil = u.IdUsuario
             WHERE mg.IdGrupo = ?
             ORDER BY mg.FechaUnion ASC"
        );
        $stmt->bind_param('i', $idGrupo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Mensajes de un grupo
    public static function getMensajes(int $idGrupo): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT mg.IdMensaje, mg.IdEmisor, mg.Contenido, mg.FechaEnvio,
                    u.NombreUsuario, u.TipoUsuario, p.FotoPerfil
             FROM MensajeGrupo mg
             JOIN Usuarios u ON u.IdUsuario = mg.IdEmisor
             LEFT JOIN Perfil p ON p.IdPerfil = mg.IdEmisor
             WHERE mg.IdGrupo = ?
             ORDER BY mg.FechaEnvio ASC"
        );
        $stmt->bind_param('i', $idGrupo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Enviar mensaje en grupo
    public static function enviarMensaje(int $idGrupo, int $idEmisor, string $contenido): bool {
        $contenido = mb_substr(trim($contenido), 0, 1024);
        if (!$contenido) return false;

        $db    = new Conexion();
        $conn  = $db->getConexion();
        $fecha = date('Y-m-d H:i:s');
        $stmt  = $conn->prepare(
            "INSERT INTO MensajeGrupo (IdGrupo, IdEmisor, Contenido, FechaEnvio)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('iiss', $idGrupo, $idEmisor, $contenido, $fecha);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }
}
