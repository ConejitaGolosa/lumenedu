<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../models/modelRecuperacion.php';

$action = $_POST['action'] ?? '';

switch ($action) {

    // ── Paso 1: Solicitar código ─────────────────────────────────
    case 'solicitarCodigo':
        $correo = trim($_POST['email'] ?? '');

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Ingresa un correo válido.';
            header('Location: index.php?page=viewRecuperarPassword');
            break;
        }

        $resultado = Recuperacion::generarCodigo($correo);

        // Por seguridad: no revelar si el correo existe o no
        if ($resultado) {
            $codigo = $resultado['codigo'];

            // Intentar enviar por email
            $asunto  = 'LumenEdu — Código de recuperación';
            $cuerpo  = "Hola,\n\nTu código de recuperación de contraseña es:\n\n  $codigo\n\nExpira en 15 minutos.\n\nSi no solicitaste esto, ignora este mensaje.";
            $headers = 'From: noreply@lumenedu.com';
            $mailOk  = @mail($correo, $asunto, $cuerpo, $headers);

            // Guardar en sesión para el paso 2
            $_SESSION['reset_correo'] = $correo;

            // En desarrollo (sin servidor de correo) mostrar el código en pantalla
            if (!$mailOk) {
                $_SESSION['reset_codigo_dev'] = $codigo;
            }
        }

        // Siempre mostrar el mismo mensaje (no revelar si el correo existe)
        $_SESSION['mensaje'] = 'Si el correo está registrado, recibirás el código.';
        header('Location: index.php?page=viewRecuperarPassword&step=2');
        break;

    // ── Paso 2: Verificar código y cambiar contraseña ────────────
    case 'resetPassword':
        $correo    = $_SESSION['reset_correo'] ?? '';
        $codigo    = strtoupper(trim($_POST['codigo'] ?? ''));
        $passNueva = $_POST['pass_nueva'] ?? '';
        $passConf  = $_POST['pass_conf']  ?? '';

        if (!$correo) {
            header('Location: index.php?page=viewRecuperarPassword');
            break;
        }
        if (strlen($passNueva) < 8) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres.';
            header('Location: index.php?page=viewRecuperarPassword&step=2');
            break;
        }
        if ($passNueva !== $passConf) {
            $_SESSION['error'] = 'Las contraseñas no coinciden.';
            header('Location: index.php?page=viewRecuperarPassword&step=2');
            break;
        }

        if (Recuperacion::resetPassword($correo, $codigo, $passNueva)) {
            unset($_SESSION['reset_correo'], $_SESSION['reset_codigo_dev']);
            $_SESSION['mensaje'] = '¡Contraseña actualizada! Ya puedes iniciar sesión.';
            header('Location: index.php?page=viewLogin');
        } else {
            $_SESSION['error'] = 'Código incorrecto o expirado. Intenta de nuevo.';
            header('Location: index.php?page=viewRecuperarPassword&step=2');
        }
        break;

    default:
        header('Location: index.php?page=viewRecuperarPassword');
}
exit;
