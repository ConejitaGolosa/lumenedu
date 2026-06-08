<?php
// ============================================================
// controllerVideo.php — Acciones relacionadas con videos:
// subirVideo, publicarVideo, comentarVideo.
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelComentario.php';
require_once __DIR__ . '/../models/modelNotificacion.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para continuar.';
    header('Location: index.php?page=viewLogin');
    exit;
}

$idUsuario   = (int)$_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'];
$action      = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

switch ($action) {

    // ── SUBIR VIDEO ──────────────────────────────────────────
    // Solo Creadores (profesores). El video queda en estado 'Pendiente'
    // hasta que el administrador lo revise.
    case 'subirVideo':
        if ($tipoUsuario !== 'Creador') {
            $_SESSION['error'] = 'Solo los profesores pueden subir videos.';
            header('Location: index.php');
            exit;
        }

        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Error al recibir el archivo. Verifica que hayas seleccionado un video válido.';
            header('Location: index.php?page=viewSubirVideo');
            exit;
        }

        $file         = $_FILES['video'];
        $tiposValidos = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-matroska', 'video/webm', 'video/x-msvideo'];

        if (!in_array($file['type'], $tiposValidos)) {
            $_SESSION['error'] = 'Formato no permitido. Acepta: mp4, avi, mov, mkv, webm.';
            header('Location: index.php?page=viewSubirVideo');
            exit;
        }

        // Límite de 500 MB
        if ($file['size'] > 500 * 1024 * 1024) {
            $_SESSION['error'] = 'El video supera el límite de 500 MB.';
            header('Location: index.php?page=viewSubirVideo');
            exit;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/videos/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('vid_', true) . '.' . $ext;
        $destino  = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            $_SESSION['error'] = 'No se pudo guardar el archivo. Verifica los permisos del directorio uploads/.';
            header('Location: index.php?page=viewSubirVideo');
            exit;
        }

        $rutaRelativa = 'public/uploads/videos/' . $filename;
        $video = new Video($idUsuario, $rutaRelativa);

        if ($video->subir()) {
            $_SESSION['mensaje'] = 'Video enviado a revisión. El administrador lo revisará pronto y recibirás una notificación.';
            header('Location: index.php?page=viewMisVideos');
        } else {
            unlink($destino); // Borra el archivo si el INSERT falla
            $_SESSION['error'] = 'Error al registrar el video en la base de datos.';
            header('Location: index.php?page=viewSubirVideo');
        }
        exit;

    // ── PUBLICAR VIDEO ───────────────────────────────────────
    // El profesor asigna título, descripción y privacidad a un video aprobado.
    case 'publicarVideo':
        if ($tipoUsuario !== 'Creador') {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php');
            exit;
        }

        $idVideo     = (int)($_POST['id_video']    ?? 0);
        $titulo      = trim($_POST['titulo']        ?? '');
        $descripcion = trim($_POST['descripcion']   ?? '');
        $privacidad  = $_POST['privacidad']          ?? '';
        $categoria   = $_POST['categoria']           ?? 'Otros';

        if (!$idVideo || empty($titulo)) {
            $_SESSION['error'] = 'El título es obligatorio.';
            header('Location: index.php?page=viewPublicarVideo&id=' . $idVideo);
            exit;
        }

        if (!in_array($privacidad, ['Publico', 'Suscriptores', 'Privado'])) {
            $_SESSION['error'] = 'Selecciona una opción de privacidad válida.';
            header('Location: index.php?page=viewPublicarVideo&id=' . $idVideo);
            exit;
        }

        if (Video::publicar($idVideo, $idUsuario, $titulo, $descripcion, $privacidad, $categoria)) {
            $_SESSION['mensaje'] = 'Video publicado correctamente.';
            header('Location: index.php?page=viewMisVideos');
        } else {
            $_SESSION['error'] = 'No se pudo publicar. Solo puedes publicar videos en estado "Aprobado".';
            header('Location: index.php?page=viewMisVideos');
        }
        exit;

    // ── EDITAR VIDEO ─────────────────────────────────────────
    // El profesor edita título, descripción, categoría, privacidad y miniatura.
    case 'editarVideo':
        if ($tipoUsuario !== 'Creador') {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?page=viewMisVideos');
            exit;
        }

        $idVideo     = (int)($_POST['id_video']   ?? 0);
        $titulo      = trim($_POST['titulo']       ?? '');
        $descripcion = trim($_POST['descripcion']  ?? '');
        $privacidad  = $_POST['privacidad']         ?? '';
        $categoria   = $_POST['categoria']          ?? 'Otros';
        $borrarMini  = !empty($_POST['borrar_miniatura']);

        if (!$idVideo || empty($titulo)) {
            $_SESSION['error'] = 'El título es obligatorio.';
            header('Location: index.php?page=viewEditarVideo&id=' . $idVideo);
            exit;
        }
        if (!in_array($privacidad, ['Publico', 'Suscriptores', 'Privado'])) {
            $_SESSION['error'] = 'Opción de privacidad inválida.';
            header('Location: index.php?page=viewEditarVideo&id=' . $idVideo);
            exit;
        }

        $video = Video::getById($idVideo);
        if (!$video || (int)$video['IdProfesor'] !== $idUsuario || $video['Estado'] !== 'Publicado') {
            $_SESSION['error'] = 'Video no encontrado o no autorizado.';
            header('Location: index.php?page=viewMisVideos');
            exit;
        }

        // Procesar miniatura
        $miniatura  = false; // false = no tocar
        $uploadDir  = __DIR__ . '/../../public/uploads/miniaturas/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (isset($_FILES['miniatura']) && $_FILES['miniatura']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['miniatura'];
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mime     = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ($file['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = 'La miniatura no puede superar 2 MB.';
                header('Location: index.php?page=viewEditarVideo&id=' . $idVideo);
                exit;
            }
            if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/png'])) {
                $_SESSION['error'] = 'Solo se aceptan imágenes PNG o JPG como miniatura.';
                header('Location: index.php?page=viewEditarVideo&id=' . $idVideo);
                exit;
            }

            // Borrar miniatura anterior
            if ($video['Miniatura']) {
                $ant = __DIR__ . '/../../' . $video['Miniatura'];
                if (file_exists($ant)) @unlink($ant);
            }

            $ext  = $mime === 'image/png' ? 'png' : 'jpg';
            $name = 'thumb_' . $idVideo . '_' . time() . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $name)) {
                $_SESSION['error'] = 'No se pudo guardar la miniatura.';
                header('Location: index.php?page=viewEditarVideo&id=' . $idVideo);
                exit;
            }
            $miniatura = 'public/uploads/miniaturas/' . $name;

        } elseif ($borrarMini) {
            if ($video['Miniatura']) {
                $ant = __DIR__ . '/../../' . $video['Miniatura'];
                if (file_exists($ant)) @unlink($ant);
            }
            $miniatura = null; // null = SET Miniatura = NULL en BD
        }

        if (Video::editar($idVideo, $idUsuario, $titulo, $descripcion, $privacidad, $categoria, $miniatura)) {
            $_SESSION['mensaje'] = 'Video actualizado correctamente.';
            header('Location: index.php?page=viewMisVideos');
        } else {
            $_SESSION['error'] = 'No se pudieron guardar los cambios.';
            header('Location: index.php?page=viewEditarVideo&id=' . $idVideo);
        }
        exit;

    // ── COMENTAR VIDEO ───────────────────────────────────────
    // Cualquier usuario autenticado puede comentar en videos que pueda ver.
    case 'comentarVideo':
        $idVideo   = (int)($_POST['id_video'] ?? 0);
        $contenido = trim($_POST['contenido']  ?? '');

        if (!$idVideo || empty($contenido)) {
            $_SESSION['error'] = 'El comentario no puede estar vacío.';
            header('Location: index.php?page=viewVideo&id=' . $idVideo);
            exit;
        }

        if (mb_strlen($contenido) > 1024) {
            $_SESSION['error'] = 'El comentario no puede superar 1024 caracteres.';
            header('Location: index.php?page=viewVideo&id=' . $idVideo);
            exit;
        }

        if (Comentario::agregar($idVideo, $idUsuario, $contenido)) {
            $_SESSION['mensaje'] = 'Comentario publicado.';
        } else {
            $_SESSION['error'] = 'Error al publicar el comentario.';
        }
        header('Location: index.php?page=viewVideo&id=' . $idVideo);
        exit;

    // ── CAMBIAR PRIVACIDAD ───────────────────────────────────
    case 'cambiarPrivacidad':
        if ($tipoUsuario !== 'Creador') {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?page=viewMisVideos');
            exit;
        }

        $idVideo    = (int)($_POST['id_video']   ?? 0);
        $privacidad = $_POST['privacidad']         ?? '';

        if (!$idVideo || !in_array($privacidad, ['Publico', 'Suscriptores', 'Privado'])) {
            $_SESSION['error'] = 'Datos inválidos.';
            header('Location: index.php?page=viewMisVideos');
            exit;
        }

        if (Video::cambiarPrivacidad($idVideo, $idUsuario, $privacidad)) {
            $_SESSION['mensaje'] = 'Privacidad actualizada correctamente.';
        } else {
            $_SESSION['error'] = 'No se pudo actualizar. Solo puedes editar tus videos publicados.';
        }
        header('Location: index.php?page=viewMisVideos');
        exit;

    // ── ELIMINAR MI VIDEO ────────────────────────────────────
    case 'eliminarMiVideo':
        if ($tipoUsuario !== 'Creador') {
            $_SESSION['error'] = 'Acción no permitida.';
            header('Location: index.php?page=viewMisVideos');
            exit;
        }

        $idVideo = (int)($_POST['id_video'] ?? 0);

        if (!$idVideo) {
            $_SESSION['error'] = 'ID de video inválido.';
            header('Location: index.php?page=viewMisVideos');
            exit;
        }

        if (Video::eliminarMiVideo($idVideo, $idUsuario)) {
            $_SESSION['mensaje'] = 'Video eliminado.';
        } else {
            $_SESSION['error'] = 'No se pudo eliminar. El video no existe o no te pertenece.';
        }
        header('Location: index.php?page=viewMisVideos');
        exit;

    // ── RESPONDER COMENTARIO ─────────────────────────────────
    // Responde a un comentario en video o foro; notifica al autor del comentario padre.
    case 'responderComentario':
        $idPadre   = (int)($_POST['id_comentario_padre'] ?? 0);
        $idVideo   = (int)($_POST['id_video'] ?? 0) ?: null;
        $idForo    = (int)($_POST['id_foro']  ?? 0) ?: null;
        $contenido = trim($_POST['contenido'] ?? '');

        $redirect = $idForo
            ? 'index.php?page=viewForo&id=' . $idForo
            : 'index.php?page=viewVideo&id=' . $idVideo;

        if (!$idPadre || empty($contenido)) {
            $_SESSION['error'] = 'La respuesta no puede estar vacía.';
            header('Location: ' . $redirect);
            exit;
        }

        if (mb_strlen($contenido) > 1024) {
            $_SESSION['error'] = 'La respuesta no puede superar 1024 caracteres.';
            header('Location: ' . $redirect);
            exit;
        }

        $idNuevo = Comentario::agregar($idVideo, $idUsuario, $contenido, $idForo, $idPadre);

        if ($idNuevo) {
            // Notifica al autor del comentario padre si es distinto al que responde
            $idAutorPadre = Comentario::getAutor($idPadre);
            if ($idAutorPadre && $idAutorPadre !== $idUsuario) {
                $preview = mb_substr($contenido, 0, 80) . (mb_strlen($contenido) > 80 ? '…' : '');
                $msg     = htmlspecialchars($_SESSION['usuario_nombre'])
                         . ' respondió a tu comentario: "' . $preview . '"';
                $ref     = $idPadre; // guardamos el IdComentario padre para enlazar directamente
                require_once __DIR__ . '/../models/modelNotificacion.php';
                Notificacion::crear($idAutorPadre, 'RespuestaComentario', $msg, $ref);
            }
            $_SESSION['mensaje'] = 'Respuesta publicada.';
        } else {
            $_SESSION['error'] = 'Error al publicar la respuesta.';
        }

        header('Location: ' . $redirect);
        exit;

    default:
        header('Location: index.php');
        exit;
}
