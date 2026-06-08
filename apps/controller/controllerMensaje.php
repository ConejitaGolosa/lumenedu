<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../models/modelMensaje.php';
require_once __DIR__ . '/../models/modelGrupo.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?page=viewLogin');
    exit;
}

$action    = $_POST['action'] ?? '';
$idUsuario = (int)$_SESSION['usuario_id'];

switch ($action) {

    case 'enviarMensaje':
        $idReceptor = (int)($_POST['id_receptor'] ?? 0);
        $contenido  = $_POST['contenido'] ?? '';
        if ($idReceptor && $contenido) {
            Mensaje::enviar($idUsuario, $idReceptor, $contenido);
        }
        header('Location: index.php?page=viewMensajes&usuario=' . $idReceptor);
        break;

    case 'enviarMensajeGrupo':
        $idGrupo   = (int)($_POST['id_grupo'] ?? 0);
        $contenido = $_POST['contenido'] ?? '';
        if ($idGrupo && $contenido) {
            Grupo::enviarMensaje($idGrupo, $idUsuario, $contenido);
        }
        header('Location: index.php?page=viewGrupo&id=' . $idGrupo);
        break;

    case 'crearGrupo':
        $nombre   = trim($_POST['nombre'] ?? '');
        $miembros = $_POST['miembros'] ?? [];  // array de IdUsuario

        if (!$nombre) {
            $_SESSION['error'] = 'El grupo necesita un nombre.';
            header('Location: index.php?page=viewGrupos');
            break;
        }

        $idGrupo = Grupo::crear($idUsuario, $nombre);

        if ($idGrupo && is_array($miembros)) {
            foreach ($miembros as $idM) {
                $idM = (int)$idM;
                if ($idM && $idM !== $idUsuario) {
                    Grupo::agregarMiembro($idGrupo, $idM);
                }
            }
        }

        $_SESSION['mensaje'] = 'Grupo "' . htmlspecialchars($nombre) . '" creado.';
        header('Location: index.php?page=viewGrupo&id=' . $idGrupo);
        break;

    case 'agregarAlGrupo':
        $idGrupo    = (int)($_POST['id_grupo'] ?? 0);
        $idNuevoMiembro = (int)($_POST['id_usuario'] ?? 0);

        // Solo el creador puede agregar miembros
        $grupo = Grupo::getById($idGrupo, $idUsuario);
        if ($grupo && (int)$grupo['IdCreador'] === $idUsuario && $idNuevoMiembro) {
            Grupo::agregarMiembro($idGrupo, $idNuevoMiembro);
            $_SESSION['mensaje'] = 'Miembro agregado.';
        }
        header('Location: index.php?page=viewGrupo&id=' . $idGrupo);
        break;

    default:
        header('Location: index.php?page=viewMensajes');
}
exit;
