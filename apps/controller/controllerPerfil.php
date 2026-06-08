<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../models/modelPerfil.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?page=viewLogin');
    exit;
}

$action    = $_POST['action'] ?? '';
$idUsuario = (int)$_SESSION['usuario_id'];

switch ($action) {

    // ── Actualizar bio, enlace y privacidad ──────────────────────
    case 'actualizarPerfil':
        $bio       = $_POST['bio']       ?? '';
        $enlace    = $_POST['enlace']    ?? '';
        $privacidad = $_POST['privacidad'] ?? 'Publico';

        Perfil::actualizarInfo($idUsuario, $bio, $enlace, $privacidad);
        $_SESSION['mensaje'] = 'Perfil actualizado correctamente.';
        header('Location: index.php?page=viewEditarPerfil');
        break;

    // ── Actualizar usuario y correo ──────────────────────────────
    case 'actualizarCuenta':
        $nuevoNombre = trim($_POST['nombre'] ?? '');
        $nuevoCorreo = trim($_POST['email']  ?? '');

        if (strlen($nuevoNombre) < 3 || strlen($nuevoNombre) > 16) {
            $_SESSION['error'] = 'El nombre debe tener entre 3 y 16 caracteres.';
            header('Location: index.php?page=viewEditarPerfil');
            break;
        }
        if (!filter_var($nuevoCorreo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El correo no es válido.';
            header('Location: index.php?page=viewEditarPerfil');
            break;
        }

        $error = Perfil::actualizarCuenta($idUsuario, $nuevoNombre, $nuevoCorreo);
        if ($error) {
            $_SESSION['error'] = $error;
        } else {
            $_SESSION['usuario_nombre'] = $nuevoNombre;
            $_SESSION['mensaje'] = 'Datos de cuenta actualizados.';
        }
        header('Location: index.php?page=viewEditarPerfil');
        break;

    // ── Cambiar contraseña ───────────────────────────────────────
    case 'cambiarPassword':
        $passActual = $_POST['pass_actual'] ?? '';
        $passNueva  = $_POST['pass_nueva']  ?? '';
        $passConf   = $_POST['pass_conf']   ?? '';

        if (strlen($passNueva) < 8) {
            $_SESSION['error'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
            header('Location: index.php?page=viewEditarPerfil');
            break;
        }
        if ($passNueva !== $passConf) {
            $_SESSION['error'] = 'Las contraseñas nuevas no coinciden.';
            header('Location: index.php?page=viewEditarPerfil');
            break;
        }

        $error = Perfil::cambiarPassword($idUsuario, $passActual, $passNueva);
        if ($error) {
            $_SESSION['error'] = $error;
        } else {
            $_SESSION['mensaje'] = 'Contraseña cambiada correctamente.';
        }
        header('Location: index.php?page=viewEditarPerfil');
        break;

    // ── Subir foto de perfil ─────────────────────────────────────
    case 'subirFotoPerfil':
        $file = $_FILES['foto'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Error al subir el archivo.';
            header('Location: index.php?page=viewEditarPerfil');
            break;
        }

        $maxBytes  = 2 * 1024 * 1024; // 2 MB
        $tiposOk   = ['image/jpeg', 'image/jpg', 'image/png'];
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($file['size'] > $maxBytes) {
            $_SESSION['error'] = 'La imagen supera los 2 MB.';
            header('Location: index.php?page=viewEditarPerfil');
            break;
        }
        if (!in_array($mimeReal, $tiposOk)) {
            $_SESSION['error'] = 'Solo se aceptan imágenes PNG o JPG.';
            header('Location: index.php?page=viewEditarPerfil');
            break;
        }

        $ext      = $mimeReal === 'image/png' ? 'png' : 'jpg';
        $dir      = __DIR__ . '/../../public/uploads/fotos/';
        $filename = $idUsuario . '_' . time() . '.' . $ext;
        $ruta     = 'public/uploads/fotos/' . $filename;

        // Borrar foto anterior si existe
        $perfil = Perfil::getByUsuario($idUsuario);
        if ($perfil && $perfil['FotoPerfil']) {
            $anterior = __DIR__ . '/../../' . $perfil['FotoPerfil'];
            if (file_exists($anterior)) @unlink($anterior);
        }

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            Perfil::actualizarFoto($idUsuario, $ruta);
            $_SESSION['mensaje'] = 'Foto de perfil actualizada.';
        } else {
            $_SESSION['error'] = 'No se pudo guardar la imagen.';
        }
        header('Location: index.php?page=viewEditarPerfil');
        break;

    // ── Eliminar foto ────────────────────────────────────────────
    case 'eliminarFotoPerfil':
        $perfil = Perfil::getByUsuario($idUsuario);
        if ($perfil && $perfil['FotoPerfil']) {
            $ruta = __DIR__ . '/../../' . $perfil['FotoPerfil'];
            if (file_exists($ruta)) @unlink($ruta);
        }
        Perfil::eliminarFoto($idUsuario);
        $_SESSION['mensaje'] = 'Foto eliminada. Se usará la imagen por defecto.';
        header('Location: index.php?page=viewEditarPerfil');
        break;

    default:
        header('Location: index.php?page=viewHome');
}
exit;
