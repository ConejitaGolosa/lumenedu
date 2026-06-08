<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../models/modelAmistad.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?page=viewLogin');
    exit;
}

$action    = $_POST['action'] ?? '';
$idUsuario = (int)$_SESSION['usuario_id'];
$redirect  = $_POST['redirect'] ?? 'index.php?page=viewPerfil&id=' . ($idUsuario);

switch ($action) {

    case 'enviarSolicitud':
        $idReceptor = (int)($_POST['id_receptor'] ?? 0);
        if ($idReceptor) {
            $error = Amistad::enviar($idUsuario, $idReceptor);
            if ($error) $_SESSION['error'] = $error;
            else        $_SESSION['mensaje'] = 'Solicitud enviada.';
        }
        header('Location: ' . $redirect);
        break;

    case 'aceptarSolicitud':
        $idAmistad = (int)($_POST['id_amistad'] ?? 0);
        if (Amistad::aceptar($idAmistad, $idUsuario)) {
            $_SESSION['mensaje'] = 'Solicitud aceptada.';
        }
        header('Location: ' . $redirect);
        break;

    case 'rechazarSolicitud':
    case 'cancelarSolicitud':
    case 'eliminarAmigo':
        $idAmistad = (int)($_POST['id_amistad'] ?? 0);
        Amistad::rechazar($idAmistad, $idUsuario);
        header('Location: ' . $redirect);
        break;

    default:
        header('Location: index.php?page=viewHome');
}
exit;
