<?php
// ============================================================
// controllerAdmin.php — Acciones del administrador.
// Por ahora: revisarVideo (aprobar o rechazar videos pendientes).
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/configConexion.php';
require_once __DIR__ . '/../models/modelNotificacion.php';

// Acceso exclusivo para administradores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Administrador') {
    $_SESSION['error'] = 'Acceso restringido.';
    header('Location: index.php');
    exit;
}

$idAdmin = (int)$_SESSION['usuario_id'];
$action  = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=viewAdminPanel');
    exit;
}

switch ($action) {

    // ── REVISAR VIDEO ────────────────────────────────────────
    // El admin marca el video como Aprobado o Rechazado y notifica al profesor.
    case 'revisarVideo':
        $idVideo  = (int)($_POST['id_video'] ?? 0);
        $validado = isset($_POST['validado']) ? 1 : 0;
        $motivo   = trim($_POST['motivo'] ?? '');

        if (!$idVideo) {
            $_SESSION['error'] = 'ID de video inválido.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        // El motivo es obligatorio si se rechaza
        if (!$validado && empty($motivo)) {
            $_SESSION['error'] = 'Debes indicar el motivo del rechazo.';
            header('Location: index.php?page=viewAdminPanel');
            exit;
        }

        $db   = new Conexion();
        $conn = $db->getConexion();

        // Obtiene el profesor antes de actualizar
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

        // Registra la revisión
        $fecha       = date('Y-m-d H:i:s');
        $motivoNull  = $validado ? null : $motivo;
        $stmtRev = $conn->prepare(
            "INSERT INTO RevisionVideo (IdVideo, IdAdmin, Validado, MotivoRechazo, FechaRevision) VALUES (?, ?, ?, ?, ?)"
        );
        $stmtRev->bind_param("iiiss", $idVideo, $idAdmin, $validado, $motivoNull, $fecha);
        $stmtRev->execute();
        $stmtRev->close();

        // Actualiza el estado del video
        $nuevoEstado = $validado ? 'Aprobado' : 'Rechazado';
        $stmtUpd = $conn->prepare("UPDATE Video SET Estado=? WHERE IdVideo=?");
        $stmtUpd->bind_param("si", $nuevoEstado, $idVideo);
        $stmtUpd->execute();
        $stmtUpd->close();
        $db->cerrarConexion();

        // Notifica al profesor
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

    default:
        header('Location: index.php?page=viewAdminPanel');
        exit;
}
