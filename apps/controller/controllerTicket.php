<?php
// ============================================================
// controllerTicket.php — Tickets y solicitudes de clase.
// Acciones: usarTicket, solicitarClase, responderSolicitud.
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/modelTicket.php';
require_once __DIR__ . '/../models/modelSolicitudClase.php';
require_once __DIR__ . '/../models/modelNotificacion.php';
require_once __DIR__ . '/../models/modelUser.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = 'Debes iniciar sesión.';
    header('Location: index.php?page=viewLogin');
    exit;
}

$idUsuario   = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'];
$action      = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

switch ($action) {

    // ── USAR TICKET ──────────────────────────────────────────
    // El alumno suscrito elige a un profesor para desbloquear su contenido este mes.
    case 'usarTicket':
        if ($tipoUsuario !== 'Suscriptor') {
            $_SESSION['error'] = 'Solo los alumnos suscritos pueden usar tickets.';
            header('Location: index.php?page=viewTickets');
            exit;
        }

        $idProfesor = (int)($_POST['id_profesor'] ?? 0);
        if (!$idProfesor) {
            $_SESSION['error'] = 'Selecciona un profesor válido.';
            header('Location: index.php?page=viewTickets');
            exit;
        }

        $resultado = Ticket::usar($idUsuario, $idProfesor);

        match($resultado) {
            'ok'        => $_SESSION['mensaje'] = 'Ticket usado. Ya tienes acceso al contenido de ese profesor este mes.',
            'duplicado' => $_SESSION['error']   = 'Ya tienes un ticket con ese profesor este mes.',
            'limite'    => $_SESSION['error']   = 'Alcanzaste el límite de 3 tickets para este mes.',
            default     => $_SESSION['error']   = 'Error al procesar el ticket. Intenta nuevamente.',
        };

        header('Location: index.php?page=viewTickets');
        exit;

    // ── SOLICITAR CLASE VIRTUAL ──────────────────────────────
    // El alumno propone una fecha/hora al profesor (requiere tener ticket con ese profesor).
    case 'solicitarClase':
        if ($tipoUsuario !== 'Suscriptor') {
            $_SESSION['error'] = 'Solo los alumnos suscritos pueden solicitar clases.';
            header('Location: index.php?page=viewTickets');
            exit;
        }

        $idProfesor     = (int)($_POST['id_profesor']    ?? 0);
        $fechaPropuesta = trim($_POST['fecha_propuesta']  ?? '');

        if (!$idProfesor || empty($fechaPropuesta)) {
            $_SESSION['error'] = 'Completa todos los campos de la solicitud.';
            header('Location: index.php?page=viewTickets');
            exit;
        }

        // Valida que la fecha no sea en el pasado
        if (strtotime($fechaPropuesta) <= time()) {
            $_SESSION['error'] = 'La fecha propuesta no puede ser en el pasado o la hora actual.';
            header('Location: index.php?page=viewTickets');
            exit;
        }

        // Valida que el alumno tenga ticket con ese profesor este mes
        $desbloqueados = Ticket::profesoresDesbloqueados($idUsuario);
        if (!in_array($idProfesor, $desbloqueados)) {
            $_SESSION['error'] = 'Necesitas un ticket con ese profesor para solicitar una clase.';
            header('Location: index.php?page=viewTickets');
            exit;
        }

        // Valida tiempo mínimo de anticipación del profesor
        $diasMin    = Usuario::getMinDias($idProfesor);
        $fechaMin   = date('Y-m-d H:i:s', strtotime("+{$diasMin} days"));
        if (strtotime($fechaPropuesta) < strtotime($fechaMin)) {
            $_SESSION['error'] = 'Este profesor requiere al menos ' . $diasMin . ' día(s) de anticipación. '
                               . 'La fecha mínima disponible es el ' . date('d/m/Y', strtotime($fechaMin)) . '.';
            header('Location: index.php?page=viewTickets');
            exit;
        }

        $idSolicitud = SolicitudClase::crear($idUsuario, $idProfesor, $fechaPropuesta);

        if ($idSolicitud) {
            $msg = 'El alumno ' . htmlspecialchars($_SESSION['usuario_nombre']) .
                   ' te solicita una clase virtual para el ' . $fechaPropuesta . '.';
            Notificacion::crear($idProfesor, 'SolicitudClase', $msg, $idSolicitud);
            $_SESSION['mensaje'] = 'Solicitud enviada al profesor. Recibirás una notificación con su respuesta.';
        } else {
            $_SESSION['error'] = 'Error al enviar la solicitud. Inténtalo de nuevo.';
        }

        header('Location: index.php?page=viewTickets');
        exit;

    // ── RESPONDER SOLICITUD ──────────────────────────────────
    // El profesor acepta, rechaza o acepta con condiciones una solicitud de clase.
    case 'responderSolicitud':
        if ($tipoUsuario !== 'Creador') {
            $_SESSION['error'] = 'Solo los profesores pueden responder solicitudes.';
            header('Location: index.php');
            exit;
        }

        $idSolicitud = (int)($_POST['id_solicitud'] ?? 0);
        $estado      = $_POST['estado']              ?? '';
        $respuesta   = trim($_POST['respuesta']       ?? '');

        $estadosValidos = ['Aceptada', 'Rechazada', 'AceptadaConCondiciones'];
        if (!$idSolicitud || !in_array($estado, $estadosValidos)) {
            $_SESSION['error'] = 'Datos de respuesta inválidos.';
            header('Location: index.php?page=viewSolicitudes');
            exit;
        }

        if ($estado === 'AceptadaConCondiciones' && empty($respuesta)) {
            $_SESSION['error'] = 'Debes describir las condiciones o el horario alternativo.';
            header('Location: index.php?page=viewSolicitudes');
            exit;
        }

        // Obtiene al alumno para notificarlo
        $idEstudiante = SolicitudClase::getEstudianteDeSolicitud($idSolicitud);

        if (SolicitudClase::responder($idSolicitud, $idUsuario, $estado, $respuesta)) {
            $etiquetas = [
                'Aceptada'               => 'ACEPTADA',
                'Rechazada'              => 'RECHAZADA',
                'AceptadaConCondiciones' => 'ACEPTADA CON CONDICIONES',
            ];
            $msg = 'El profesor ' . htmlspecialchars($_SESSION['usuario_nombre']) .
                   ' respondió tu solicitud de clase: ' . $etiquetas[$estado] . '.';
            if ($respuesta) { $msg .= ' Nota: ' . $respuesta; }

            Notificacion::crear($idEstudiante, 'RespuestaSolicitud', $msg, $idSolicitud);
            $_SESSION['mensaje'] = 'Respuesta enviada al estudiante.';
        } else {
            $_SESSION['error'] = 'No se pudo responder. La solicitud ya fue respondida o no te pertenece.';
        }

        header('Location: index.php?page=viewSolicitudes');
        exit;

    default:
        header('Location: index.php');
        exit;
}
