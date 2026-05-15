<?php
// ============================================================
// controllerAdmin.php — Acciones de administración y moderación.
// Moderador: revisarVideo.
// Administrador (exclusivo): asignarModerador, eliminarVideo, eliminarCanal.
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/configConexion.php';
require_once __DIR__ . '/../models/modelNotificacion.php';
require_once __DIR__ . '/../models/modelUser.php';
require_once __DIR__ . '/../models/modelVideo.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = 'Debes iniciar sesión.';
    header('Location: index.php?page=viewLogin');
    exit;
}

$tipoUsuario = $_SESSION['usuario_tipo'];
$esAdmin     = $tipoUsuario === 'Administrador';
$esMod       = $tipoUsuario === 'Moderador';

if (!$esAdmin && !$esMod) {
    $_SESSION['error'] = 'Acceso restringido.';
    header('Location: index.php');
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$action    = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=viewAdminPanel');
    exit;
}

switch ($action) {

    // ── REVISAR VIDEO ────────────────────────────────────────
    // Disponible para Administrador y Moderador.
    case 'revisarVideo':
        $idVideo  = (int)($_POST['id_video'] ?? 0);
        $validado = isset($_POST['validado']) ? 1 : 0;
        $motivo   = trim($_POST['motivo'] ?? '');

        if (!$idVideo) {
            $_SESSION['error'] = 'ID de video inválido.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        if (!$validado && empty($motivo)) {
            $_SESSION['error'] = 'Debes indicar el motivo del rechazo.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmtGet = $conn->prepare("SELECT IdProfesor FROM Video WHERE IdVideo=? AND Estado='Pendiente'");
        $stmtGet->bind_param("i", $idVideo);
        $stmtGet->execute();
        $idProfesor = null;
        $stmtGet->bind_result($idProfesor);
        $found = $stmtGet->fetch();
        $stmtGet->close();

        if (!$found || !$idProfesor) {
            $db->cerrarConexion();
            $_SESSION['error'] = 'Video no encontrado o ya fue revisado anteriormente.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        $fecha      = date('Y-m-d H:i:s');
        $motivoNull = $validado ? null : $motivo;
        $stmtRev    = $conn->prepare(
            "INSERT INTO RevisionVideo (IdVideo, IdAdmin, Validado, MotivoRechazo, FechaRevision) VALUES (?, ?, ?, ?, ?)"
        );
        $stmtRev->bind_param("iiiss", $idVideo, $idUsuario, $validado, $motivoNull, $fecha);
        $stmtRev->execute();
        $stmtRev->close();

        $nuevoEstado = $validado ? 'Aprobado' : 'Rechazado';
        $stmtUpd     = $conn->prepare("UPDATE Video SET Estado=? WHERE IdVideo=?");
        $stmtUpd->bind_param("si", $nuevoEstado, $idVideo);
        $stmtUpd->execute();
        $stmtUpd->close();
        $db->cerrarConexion();

        if ($validado) {
            $msg = 'Tu video (#' . $idVideo . ') fue APROBADO. Ya puedes entrar a "Mis Videos" para publicarlo con su título, descripción y privacidad.';
            Notificacion::crear($idProfesor, 'VideoAprobado', $msg, $idVideo);
            $_SESSION['mensaje'] = 'Video aprobado. El profesor fue notificado.';
        } else {
            $msg = 'Tu video (#' . $idVideo . ') fue RECHAZADO. Motivo: ' . $motivo;
            Notificacion::crear($idProfesor, 'VideoRechazado', $msg, $idVideo);
            $_SESSION['mensaje'] = 'Video rechazado. El profesor fue notificado con el motivo.';
        }

        header('Location: index.php?page=viewAdminPanel');
        exit;

    // ── ASIGNAR ROL ──────────────────────────────────────────
    // Solo Administrador puede cambiar roles (incluido dar/quitar Moderador).
    case 'asignarModerador':
        if (!$esAdmin) {
            $_SESSION['error'] = 'Solo el administrador puede asignar roles.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        $idTarget = (int)($_POST['id_usuario'] ?? 0);
        $nuevoRol = $_POST['nuevo_rol']         ?? '';

        if (!$idTarget || empty($nuevoRol)) {
            $_SESSION['error'] = 'Selecciona un usuario y un rol.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        if (Usuario::asignarRol($idTarget, $nuevoRol)) {
            $_SESSION['mensaje'] = 'Rol actualizado correctamente.';
        } else {
            $_SESSION['error'] = 'No se pudo actualizar el rol. El usuario no existe o es administrador.';
        }

        header('Location: index.php?page=viewAdminPanel');
        exit;

    // ── ELIMINAR VIDEO ───────────────────────────────────────
    // Solo Administrador puede eliminar videos que violen políticas.
    case 'eliminarVideo':
        if (!$esAdmin) {
            $_SESSION['error'] = 'Solo el administrador puede eliminar videos.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        $idVideo = (int)($_POST['id_video'] ?? 0);

        if (!$idVideo) {
            $_SESSION['error'] = 'ID de video inválido.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        if (Video::eliminarVideo($idVideo) !== false) {
            $_SESSION['mensaje'] = 'Video #' . $idVideo . ' eliminado correctamente.';
        } else {
            $_SESSION['error'] = 'No se encontró el video o ya estaba eliminado.';
        }

        header('Location: index.php?page=viewAdminPanel');
        exit;

    // ── ELIMINAR CANAL ───────────────────────────────────────
    // Solo Administrador puede suspender un canal de usuario.
    case 'eliminarCanal':
        if (!$esAdmin) {
            $_SESSION['error'] = 'Solo el administrador puede suspender canales.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        $idProfesor = (int)($_POST['id_usuario'] ?? 0);

        if (!$idProfesor) {
            $_SESSION['error'] = 'ID de usuario inválido.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        if (Video::eliminarCanal($idProfesor)) {
            $_SESSION['mensaje'] = 'Canal suspendido y contenido ocultado.';
        } else {
            $_SESSION['error'] = 'No se pudo suspender el canal. El usuario no existe o es administrador.';
        }

        header('Location: index.php?page=viewAdminPanel');
        exit;

    default:
        header('Location: index.php?page=viewAdminPanel');
        exit;
}
