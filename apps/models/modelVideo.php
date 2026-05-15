<?php
// ============================================================
// modelVideo.php — Modelo de Video.
// Gestiona el ciclo completo: subida → revisión → publicación.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Video {
    private $idProfesor;
    private $archivoVideo;

    public function __construct($idProfesor, $archivoVideo) {
        $this->idProfesor   = $idProfesor;
        $this->archivoVideo = $archivoVideo;
    }

    // Registra el video en BD con estado 'Pendiente' (aún sin título ni descripción).
    public function subir() {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $query = "INSERT INTO Video (IdProfesor, ArchivoVideo, Estado, FechaSubida) VALUES (?, ?, 'Pendiente', ?)";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("iss", $this->idProfesor, $this->archivoVideo, $fecha);

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

    // El profesor asigna título, descripción y privacidad a un video ya aprobado.
    // Solo funciona si el video está en estado 'Aprobado' y pertenece al profesor.
    public static function publicar($idVideo, $idProfesor, $titulo, $descripcion, $privacidad) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $fecha = date('Y-m-d H:i:s');
        $query = "UPDATE Video
                  SET Titulo=?, Descripcion=?, Privacidad=?, Estado='Publicado', FechaPublicacion=?
                  WHERE IdVideo=? AND IdProfesor=? AND Estado='Aprobado'";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("ssssii", $titulo, $descripcion, $privacidad, $fecha, $idVideo, $idProfesor);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Todos los videos del profesor con su estado y resultado de revisión.
    public static function getMisVideos($idProfesor) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT v.IdVideo, v.Titulo, v.ArchivoVideo, v.Estado,
                         v.Privacidad, v.FechaSubida, v.FechaPublicacion,
                         r.Validado, r.MotivoRechazo
                  FROM Video v
                  LEFT JOIN RevisionVideo r ON r.IdVideo = v.IdVideo
                  WHERE v.IdProfesor = ?
                  ORDER BY v.FechaSubida DESC";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idProfesor);
        $stmt->execute();
        $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $videos;
    }

    // Videos visibles para el listado público, filtrados por tipo de usuario.
    // $ticketedProfs: array de IdProfesor que el Suscriptor ha desbloqueado este mes.
    public static function getListaVisible($tipoUsuario, $ticketedProfs = []) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        if ($tipoUsuario === 'EstudianteGratis' || !$tipoUsuario) {
            // Solo videos públicos
            $query = "SELECT v.IdVideo, v.Titulo, v.Descripcion, v.Privacidad,
                             v.FechaPublicacion, u.NombreUsuario AS Profesor, v.IdProfesor
                      FROM Video v JOIN Usuarios u ON u.IdUsuario = v.IdProfesor
                      WHERE v.Estado = 'Publicado' AND v.Privacidad = 'Publico'
                      ORDER BY v.FechaPublicacion DESC";
            $stmt = $conn->prepare($query);

        } else {
            // Suscriptor, Creador, Administrador, Moderador: ven públicos y de suscriptores.
            // El acceso real se controla en puedeVer() al abrir el video.
            $query = "SELECT v.IdVideo, v.Titulo, v.Descripcion, v.Privacidad,
                             v.FechaPublicacion, u.NombreUsuario AS Profesor, v.IdProfesor
                      FROM Video v JOIN Usuarios u ON u.IdUsuario = v.IdProfesor
                      WHERE v.Estado = 'Publicado' AND v.Privacidad != 'Privado'
                      ORDER BY v.FechaPublicacion DESC";
            $stmt  = $conn->prepare($query);
        }

        $stmt->execute();
        $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $videos;
    }

    // Video individual con datos del profesor.
    public static function getById($idVideo) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT v.*, u.NombreUsuario AS Profesor
                  FROM Video v JOIN Usuarios u ON u.IdUsuario = v.IdProfesor
                  WHERE v.IdVideo = ?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $idVideo);
        $stmt->execute();
        $video = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->cerrarConexion();
        return $video;
    }

    // Lista de videos pendientes de revisión para el panel de admin.
    public static function getPendientes() {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT v.IdVideo, v.ArchivoVideo, v.FechaSubida, u.NombreUsuario AS Profesor
                  FROM Video v JOIN Usuarios u ON u.IdUsuario = v.IdProfesor
                  WHERE v.Estado = 'Pendiente'
                  ORDER BY v.FechaSubida ASC";
        $stmt  = $conn->prepare($query);
        $stmt->execute();
        $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $videos;
    }

    // Lista de todos los profesores con al menos un video publicado (para tickets).
    public static function getProfesoresConVideos() {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT DISTINCT u.IdUsuario, u.NombreUsuario
                  FROM Usuarios u
                  JOIN Video v ON v.IdProfesor = u.IdUsuario
                  WHERE u.TipoUsuario = 'Creador' AND v.Estado = 'Publicado'
                  ORDER BY u.NombreUsuario";
        $stmt  = $conn->prepare($query);
        $stmt->execute();
        $profs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $profs;
    }

    // Lista de todos los profesores activos con su tiempo mínimo de anticipación.
    public static function getTodosProfesores() {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT IdUsuario, NombreUsuario, DiasAntMinimo
             FROM Usuarios
             WHERE TipoUsuario = 'Creador' AND EstadoCuenta = 'Activo'
             ORDER BY NombreUsuario"
        );
        $stmt->execute();
        $profs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $profs;
    }

    // El profesor cambia la privacidad de uno de sus videos publicados.
    public static function cambiarPrivacidad($idVideo, $idProfesor, $privacidad) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "UPDATE Video SET Privacidad = ?
             WHERE IdVideo = ? AND IdProfesor = ? AND Estado = 'Publicado'"
        );
        $stmt->bind_param("sii", $privacidad, $idVideo, $idProfesor);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // El profesor elimina (baja lógica) uno de sus propios videos.
    public static function eliminarMiVideo($idVideo, $idProfesor) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "UPDATE Video SET Estado = 'Eliminado'
             WHERE IdVideo = ? AND IdProfesor = ?"
        );
        $stmt->bind_param("ii", $idVideo, $idProfesor);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // Marca un video como eliminado (baja lógica). Retorna la ruta del archivo o false.
    public static function eliminarVideo($idVideo) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmtGet = $conn->prepare("SELECT ArchivoVideo FROM Video WHERE IdVideo = ? AND Estado != 'Eliminado'");
        $stmtGet->bind_param("i", $idVideo);
        $stmtGet->execute();
        $archivo = null;
        $stmtGet->bind_result($archivo);
        $found = $stmtGet->fetch();
        $stmtGet->close();

        if (!$found) {
            $db->cerrarConexion();
            return false;
        }

        $stmt = $conn->prepare("UPDATE Video SET Estado = 'Eliminado' WHERE IdVideo = ?");
        $stmt->bind_param("i", $idVideo);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok ? $archivo : false;
    }

    // Suspende la cuenta de un usuario y marca todos sus videos como eliminados.
    public static function eliminarCanal($idProfesor) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmtUser = $conn->prepare(
            "UPDATE Usuarios SET EstadoCuenta = 'Suspendido'
             WHERE IdUsuario = ? AND TipoUsuario != 'Administrador'"
        );
        $stmtUser->bind_param("i", $idProfesor);
        $stmtUser->execute();
        $ok = $stmtUser->affected_rows > 0;
        $stmtUser->close();

        if ($ok) {
            $stmtVid = $conn->prepare("UPDATE Video SET Estado = 'Eliminado' WHERE IdProfesor = ?");
            $stmtVid->bind_param("i", $idProfesor);
            $stmtVid->execute();
            $stmtVid->close();
        }

        $db->cerrarConexion();
        return $ok;
    }

    // Verifica si un usuario puede ver un video dado su tipo y tickets.
    // Reemplaza la versión anterior para incluir al Moderador.
    public static function puedeVer($video, $tipoUsuario, $idUsuario, $ticketedProfs = []) {
        if (!$video || $video['Estado'] === 'Eliminado') { return false; }
        if ($video['Estado'] !== 'Publicado') {
            return ($tipoUsuario === 'Administrador'
                 || $tipoUsuario === 'Moderador'
                 || (int)$video['IdProfesor'] === (int)$idUsuario);
        }
        if ($video['Privacidad'] === 'Publico')                    return true;
        if ($tipoUsuario === 'Administrador')                       return true;
        if ($tipoUsuario === 'Moderador')                           return true;
        if ((int)$video['IdProfesor'] === (int)$idUsuario)          return true;
        if ($video['Privacidad'] === 'Privado')                     return false;
        if ($tipoUsuario === 'Suscriptor') return in_array((int)$video['IdProfesor'], $ticketedProfs);
        if ($tipoUsuario === 'Creador')    return true;
        return false;
    }
}
