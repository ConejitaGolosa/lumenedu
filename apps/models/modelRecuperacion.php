<?php
require_once __DIR__ . '/configConexion.php';

class Recuperacion {

    // Genera un código, lo guarda en BD y retorna [codigo, idUsuario] o null si el correo no existe.
    public static function generarCodigo(string $correo): ?array {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT IdUsuario FROM Usuarios WHERE Correo = ? AND EstadoCuenta = 'Activo' LIMIT 1"
        );
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $idUsuario = null;
        $stmt->bind_result($idUsuario);
        $stmt->fetch();
        $stmt->close();

        if (!$idUsuario) {
            $db->cerrarConexion();
            return null;
        }

        // Invalidar códigos anteriores del mismo usuario
        $del = $conn->prepare("UPDATE RecuperacionPassword SET Usado = 1 WHERE IdUsuario = ?");
        $del->bind_param('i', $idUsuario);
        $del->execute();
        $del->close();

        // Código alfanumérico de 6 dígitos
        $codigo    = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $expira    = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $ins = $conn->prepare(
            "INSERT INTO RecuperacionPassword (IdUsuario, Codigo, FechaExpiracion, Usado)
             VALUES (?, ?, ?, 0)"
        );
        $ins->bind_param('iss', $idUsuario, $codigo, $expira);
        $ins->execute();
        $ins->close();
        $db->cerrarConexion();

        return ['codigo' => $codigo, 'idUsuario' => $idUsuario];
    }

    // Verifica el código y retorna el IdUsuario si es válido.
    public static function verificar(string $correo, string $codigo): ?int {
        $db   = new Conexion();
        $conn = $db->getConexion();
        $stmt = $conn->prepare(
            "SELECT r.IdUsuario FROM RecuperacionPassword r
             JOIN Usuarios u ON u.IdUsuario = r.IdUsuario
             WHERE u.Correo = ?
               AND r.Codigo = ?
               AND r.Usado = 0
               AND r.FechaExpiracion > NOW()
             ORDER BY r.IdRecuperacion DESC
             LIMIT 1"
        );
        $stmt->bind_param('ss', $correo, $codigo);
        $stmt->execute();
        $id = null;
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return $id;
    }

    // Cambia la contraseña e invalida el código.
    public static function resetPassword(string $correo, string $codigo, string $nuevaPass): bool {
        $idUsuario = self::verificar($correo, $codigo);
        if (!$idUsuario) return false;

        $db   = new Conexion();
        $conn = $db->getConexion();
        $hash = password_hash($nuevaPass, PASSWORD_BCRYPT);

        $upd = $conn->prepare(
            "UPDATE Usuarios SET `HashContraseña` = ? WHERE IdUsuario = ?"
        );
        $upd->bind_param('si', $hash, $idUsuario);
        $upd->execute();
        $upd->close();

        $inv = $conn->prepare(
            "UPDATE RecuperacionPassword SET Usado = 1 WHERE IdUsuario = ? AND Codigo = ?"
        );
        $inv->bind_param('is', $idUsuario, $codigo);
        $inv->execute();
        $inv->close();
        $db->cerrarConexion();
        return true;
    }
}
