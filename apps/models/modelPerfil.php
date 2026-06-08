<?php
require_once __DIR__ . '/configConexion.php';

class Perfil {

    // Devuelve los datos públicos de un usuario + su perfil (LEFT JOIN).
    public static function getByUsuario(int $id): ?array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT u.IdUsuario, u.NombreUsuario, u.Correo, u.TipoUsuario,
                    u.FechaRegistro, u.PreferenciasPrivacidad,
                    p.FotoPerfil, p.Biografia, p.EnlacePersonal
             FROM Usuarios u
             LEFT JOIN Perfil p ON p.IdPerfil = u.IdUsuario
             WHERE u.IdUsuario = ? AND u.EstadoCuenta = 'Activo'
             LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->cerrarConexion();
        return $row ?: null;
    }

    // Crea la fila de Perfil vacía al registrar un usuario (IdPerfil = IdUsuario).
    public static function crear(int $idUsuario): void {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO Perfil (IdPerfil) VALUES (?)"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
    }

    // Actualiza bio, enlace personal y privacidad.
    public static function actualizarInfo(int $idUsuario, string $bio, string $enlace, string $privacidad): bool {
        $privacidadesValidas = ['Publico', 'Amigos', 'Privado'];
        if (!in_array($privacidad, $privacidadesValidas)) $privacidad = 'Publico';
        $bio    = mb_substr(trim($bio), 0, 512);
        $enlace = mb_substr(trim($enlace), 0, 256);

        $db   = new Conexion();
        $conn = $db->getConexion();

        // Asegura que existe la fila
        $ins = $conn->prepare("INSERT IGNORE INTO Perfil (IdPerfil) VALUES (?)");
        $ins->bind_param('i', $idUsuario);
        $ins->execute();
        $ins->close();

        $stmt = $conn->prepare(
            "UPDATE Perfil SET Biografia = ?, EnlacePersonal = ?
             WHERE IdPerfil = ?"
        );
        $stmt->bind_param('ssi', $bio, $enlace, $idUsuario);
        $ok1 = $stmt->execute();
        $stmt->close();

        $stmt2 = $conn->prepare(
            "UPDATE Usuarios SET PreferenciasPrivacidad = ? WHERE IdUsuario = ?"
        );
        $stmt2->bind_param('si', $privacidad, $idUsuario);
        $ok2 = $stmt2->execute();
        $stmt2->close();

        $db->cerrarConexion();
        return $ok1 && $ok2;
    }

    // Guarda la ruta de la foto en la BD.
    public static function actualizarFoto(int $idUsuario, string $ruta): bool {
        $db   = new Conexion();
        $conn = $db->getConexion();
        // Garantiza que la fila exista (usuarios registrados antes del sistema de perfiles)
        $ins = $conn->prepare("INSERT IGNORE INTO Perfil (IdPerfil) VALUES (?)");
        $ins->bind_param('i', $idUsuario);
        $ins->execute();
        $ins->close();
        $stmt = $conn->prepare("UPDATE Perfil SET FotoPerfil = ? WHERE IdPerfil = ?");
        $stmt->bind_param('si', $ruta, $idUsuario);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Elimina la foto (pone NULL).
    public static function eliminarFoto(int $idUsuario): bool {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "UPDATE Perfil SET FotoPerfil = NULL WHERE IdPerfil = ?"
        );
        $stmt->bind_param('i', $idUsuario);
        $ok = $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Actualiza usuario/correo en Usuarios.
    // Retorna null si OK, o un string con el error.
    public static function actualizarCuenta(int $idUsuario, string $nuevoNombre, string $nuevoCorreo): ?string {
        $db   = new Conexion();
        $conn = $db->getConexion();

        // Verificar duplicados (excluyendo al propio usuario)
        $chk = $conn->prepare(
            "SELECT COUNT(*) FROM Usuarios
             WHERE (NombreUsuario = ? OR Correo = ?) AND IdUsuario != ?"
        );
        $chk->bind_param('ssi', $nuevoNombre, $nuevoCorreo, $idUsuario);
        $chk->execute();
        $count = 0;
        $chk->bind_result($count);
        $chk->fetch();
        $chk->close();

        if ($count > 0) {
            $db->cerrarConexion();
            return 'El nombre de usuario o correo ya está en uso.';
        }

        $stmt = $conn->prepare(
            "UPDATE Usuarios SET NombreUsuario = ?, Correo = ? WHERE IdUsuario = ?"
        );
        $stmt->bind_param('ssi', $nuevoNombre, $nuevoCorreo, $idUsuario);
        $stmt->execute();
        $stmt->close();
        $db->cerrarConexion();
        return null;
    }

    // Cambia la contraseña verificando la actual.
    public static function cambiarPassword(int $idUsuario, string $passActual, string $passNueva): ?string {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT `HashContraseña` FROM Usuarios WHERE IdUsuario = ? LIMIT 1"
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $hash = null;
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();

        if (!$hash || !password_verify($passActual, $hash)) {
            $db->cerrarConexion();
            return 'La contraseña actual no es correcta.';
        }

        $nuevoHash = password_hash($passNueva, PASSWORD_BCRYPT);
        $upd = $conn->prepare(
            "UPDATE Usuarios SET `HashContraseña` = ? WHERE IdUsuario = ?"
        );
        $upd->bind_param('si', $nuevoHash, $idUsuario);
        $upd->execute();
        $upd->close();
        $db->cerrarConexion();
        return null;
    }

    // Comprueba si el visitante puede ver el perfil según privacidad.
    // Staff (Creador, Moderador, Administrador) siempre puede ver.
    public static function puedeVer(?array $perfil, ?int $idVisitante, ?string $tipoVisitante, bool $esAmigo): bool {
        if (!$perfil) return false;
        $priv = $perfil['PreferenciasPrivacidad'] ?? 'Publico';

        // El propio usuario siempre ve su perfil
        if ($idVisitante && $idVisitante === (int)$perfil['IdUsuario']) return true;

        // Staff siempre puede ver
        if (in_array($tipoVisitante, ['Creador', 'Moderador', 'Administrador'])) return true;

        if ($priv === 'Publico')  return true;
        if ($priv === 'Amigos')   return $esAmigo;
        // Privado → solo el propio usuario (ya cubierto arriba)
        return false;
    }

    // Lista de usuarios buscados por nombre (para autocomplete al crear grupo/DM).
    public static function buscarUsuarios(string $q, int $excluir, int $limite = 10): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $like = '%' . $q . '%';
        $stmt = $conn->prepare(
            "SELECT IdUsuario, NombreUsuario, TipoUsuario
             FROM Usuarios
             WHERE NombreUsuario LIKE ? AND IdUsuario != ? AND EstadoCuenta = 'Activo'
             LIMIT ?"
        );
        $stmt->bind_param('sii', $like, $excluir, $limite);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Lista pública de todos los usuarios activos (para nuevas conversaciones).
    public static function getUsuariosActivos(int $excluir): array {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT IdUsuario, NombreUsuario, TipoUsuario
             FROM Usuarios
             WHERE IdUsuario != ? AND EstadoCuenta = 'Activo'
             ORDER BY NombreUsuario LIMIT 200"
        );
        $stmt->bind_param('i', $excluir);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }
}
