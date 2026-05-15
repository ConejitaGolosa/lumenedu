<?php
// ============================================================
// modelForo.php — Hilos / foros de la comunidad.
// Cualquier usuario autenticado puede crear y comentar.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Foro {

    // Crea un nuevo hilo. Retorna el IdForo generado o false.
    public static function crear($idAutor, $titulo, $contenido, $categoria) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $stmt  = $conn->prepare(
            "INSERT INTO Foro (IdAutor, Titulo, Contenido, Categoria, FechaPublicacion)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issss", $idAutor, $titulo, $contenido, $categoria, $fecha);
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

    // Lista de hilos ordenados del más reciente al más antiguo.
    public static function getLista($limit = 100) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT f.IdForo, f.Titulo, f.Categoria, f.FechaPublicacion,
                    u.NombreUsuario AS Autor, u.TipoUsuario,
                    (SELECT COUNT(*) FROM Comentario c
                     WHERE c.IdForo = f.IdForo AND c.IdComentarioPadre IS NULL) AS TotalComentarios
             FROM Foro f
             JOIN Usuarios u ON u.IdUsuario = f.IdAutor
             ORDER BY f.FechaPublicacion DESC
             LIMIT ?"
        );
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Datos completos de un hilo.
    public static function getById($idForo) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT f.*, u.NombreUsuario AS Autor, u.TipoUsuario
             FROM Foro f
             JOIN Usuarios u ON u.IdUsuario = f.IdAutor
             WHERE f.IdForo = ?"
        );
        $stmt->bind_param("i", $idForo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->cerrarConexion();
        return $row;
    }

    public static function eliminar($idForo) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare("DELETE FROM Foro WHERE IdForo = ?");
        $stmt->bind_param("i", $idForo);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }
}
