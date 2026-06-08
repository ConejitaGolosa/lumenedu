<?php
// ============================================================
// index.php — Front controller y enrutador principal.
// Todo el tráfico pasa por aquí: despacha acciones a los
// controladores e incluye la vista correspondiente.
// ============================================================

session_start();

// ── BAN CHECK ────────────────────────────────────────────────
// Si el usuario baneado recarga cualquier página se le cierra la sesión al instante.
if (isset($_SESSION['usuario_id'])) {
    require_once 'apps/models/modelUser.php';
    if (Usuario::isBaneado((int)$_SESSION['usuario_id'])) {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Tu cuenta ha sido baneada. Contacta al administrador si crees que es un error.';
        header('Location: index.php?page=viewLogin');
        exit;
    }
}

// ── DESPACHO DE ACCIONES ─────────────────────────────────────
// Mapeo acción → archivo de controlador
$controllerMap = [
    'login'                  => 'controllerUser',
    'logout'                 => 'controllerUser',
    'registrar'              => 'controllerUser',
    'subirVideo'             => 'controllerVideo',
    'publicarVideo'          => 'controllerVideo',
    'comentarVideo'          => 'controllerVideo',
    'responderComentario'    => 'controllerVideo',
    'cambiarPrivacidad'      => 'controllerVideo',
    'eliminarMiVideo'        => 'controllerVideo',
    'revisarVideo'           => 'controllerAdmin',
    'asignarModerador'       => 'controllerAdmin',
    'eliminarVideo'          => 'controllerAdmin',
    'eliminarCanal'          => 'controllerAdmin',
    'banearUsuario'          => 'controllerAdmin',
    'desbanearUsuario'       => 'controllerAdmin',
    'usarTicket'             => 'controllerTicket',
    'solicitarClase'         => 'controllerTicket',
    'responderSolicitud'     => 'controllerTicket',
    'crearForo'              => 'controllerForo',
    'comentarForo'           => 'controllerForo',
    'actualizarDiasMinimos'  => 'controllerProfesor',
    'capturarPago'           => 'controllerPago',
    // Perfil
    'actualizarPerfil'       => 'controllerPerfil',
    'actualizarCuenta'       => 'controllerPerfil',
    'cambiarPassword'        => 'controllerPerfil',
    'subirFotoPerfil'        => 'controllerPerfil',
    'eliminarFotoPerfil'     => 'controllerPerfil',
    // Amistad
    'enviarSolicitud'        => 'controllerAmistad',
    'aceptarSolicitud'       => 'controllerAmistad',
    'rechazarSolicitud'      => 'controllerAmistad',
    'cancelarSolicitud'      => 'controllerAmistad',
    'eliminarAmigo'          => 'controllerAmistad',
    // Mensajes y grupos
    'enviarMensaje'          => 'controllerMensaje',
    'enviarMensajeGrupo'     => 'controllerMensaje',
    'crearGrupo'             => 'controllerMensaje',
    'agregarAlGrupo'         => 'controllerMensaje',
    // Recuperación de contraseña
    'solicitarCodigo'        => 'controllerRecuperacion',
    'resetPassword'          => 'controllerRecuperacion',
];

// Si el POST llegó vacío pero hay Content-Length, el archivo excedió post_max_size
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) &&
    isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
    $_SESSION['error'] = 'El archivo es demasiado grande. Máximo permitido: 512 MB.';
    header('Location: index.php?page=viewSubirVideo');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!empty($action) && isset($controllerMap[$action])) {
    require_once 'apps/controller/' . $controllerMap[$action] . '.php';
    exit;
}

// ── ROUTING DE VISTAS ────────────────────────────────────────
$page = $_GET['page'] ?? 'viewHome';

$allowedPages = [
    'viewHome', 'viewLogin', 'viewRegistro', 'viewAbout', 'viewConfirmacion',
    'viewVideos', 'viewVideo', 'viewSubirVideo', 'viewMisVideos', 'viewPublicarVideo',
    'viewAdminPanel', 'viewNotificaciones', 'viewTickets', 'viewSolicitudes',
    'viewForos', 'viewForo', 'viewConfigProfesor', 'viewSuscribirse',
    'viewPerfil', 'viewEditarPerfil',
    'viewMensajes', 'viewGrupos', 'viewGrupo',
    'viewRecuperarPassword',
];

if (!in_array($page, $allowedPages)) {
    $page = 'viewHome';
}

// Redirige a home si el usuario ya tiene sesión e intenta entrar a login/registro
if (isset($_SESSION['usuario_id']) && in_array($page, ['viewLogin', 'viewRegistro'])) {
    header('Location: index.php?page=viewHome');
    exit;
}

// Redirige a login si el usuario no tiene sesión e intenta entrar a páginas protegidas
$paginasProtegidas = [
    'viewEditarPerfil', 'viewMensajes', 'viewGrupos', 'viewGrupo',
    'viewSubirVideo', 'viewMisVideos', 'viewPublicarVideo',
    'viewTickets', 'viewSolicitudes', 'viewConfigProfesor',
    'viewAdminPanel', 'viewNotificaciones', 'viewSuscribirse',
];
if (!isset($_SESSION['usuario_id']) && in_array($page, $paginasProtegidas)) {
    header('Location: index.php?page=viewLogin');
    exit;
}

// Contadores de badges del nav
$notifCount = 0;
$mensajesCount = 0;
if (isset($_SESSION['usuario_id'])) {
    require_once 'apps/models/modelNotificacion.php';
    require_once 'apps/models/modelMensaje.php';
    $notifCount    = Notificacion::countNoLeidas((int)$_SESSION['usuario_id']);
    $mensajesCount = Mensaje::countNoLeidos((int)$_SESSION['usuario_id']);
}

// Helper: medalla de rol para Moderadores y Administradores
function rolBadge(string $tipo): string {
    if ($tipo === 'Administrador') return '<span class="rol-badge rol-admin">Admin</span>';
    if ($tipo === 'Moderador')     return '<span class="rol-badge rol-mod">Mod</span>';
    return '';
}

// Helper: avatar circular (foto de perfil o inicial)
function avatar(string $nombre, ?string $foto, string $size = '32px'): string {
    $initial = htmlspecialchars(mb_strtoupper(mb_substr($nombre, 0, 1)));
    if ($foto) {
        return '<img src="' . htmlspecialchars($foto) . '" alt="" class="comment-avatar" style="width:' . $size . ';height:' . $size . ';">';
    }
    return '<div class="comment-avatar comment-avatar-default" style="width:' . $size . ';height:' . $size . ';">' . $initial . '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LumenEdu</title>
    <link rel="icon" type="image/png" href="public/img/LumenLogo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <a href="index.php?page=viewHome" class="nav-logo">
            <img src="public/img/LumenLogo.png" alt="" class="nav-logo-img">
            Lumen<span>Edu</span>
        </a>

        <button class="nav-toggle" aria-label="Menú" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <div class="nav-links-wrap">
        <nav class="nav-links">
            <a href="index.php?page=viewHome">Inicio</a>
            <a href="index.php?page=viewVideos">Videos</a>
            <a href="index.php?page=viewForos">Foros</a>
            <a href="index.php?page=viewAbout">Nosotros</a>

            <?php if (isset($_SESSION['usuario_id'])): ?>

                <?php if ($_SESSION['usuario_tipo'] === 'Creador'): ?>
                    <a href="index.php?page=viewSubirVideo">Subir video</a>
                    <a href="index.php?page=viewMisVideos">Mis videos</a>
                    <a href="index.php?page=viewSolicitudes">Solicitudes</a>
                    <a href="index.php?page=viewConfigProfesor">Disponibilidad</a>
                <?php endif; ?>

                <?php if ($_SESSION['usuario_tipo'] === 'Suscriptor'): ?>
                    <a href="index.php?page=viewTickets">Mis tickets</a>
                <?php endif; ?>

                <?php if (in_array($_SESSION['usuario_tipo'], ['Administrador', 'Moderador'])): ?>
                    <a href="index.php?page=viewAdminPanel">
                        <?= $_SESSION['usuario_tipo'] === 'Administrador' ? 'Administración' : 'Moderación' ?>
                    </a>
                <?php endif; ?>

                <span class="nav-notif-wrap">
                    <a href="index.php?page=viewNotificaciones">Notificaciones</a>
                    <?php if ($notifCount > 0): ?>
                        <span class="nav-badge"><?= $notifCount ?></span>
                    <?php endif; ?>
                </span>

                <span class="nav-notif-wrap">
                    <a href="index.php?page=viewMensajes">Mensajes</a>
                    <?php if ($mensajesCount > 0): ?>
                        <span class="nav-badge"><?= $mensajesCount ?></span>
                    <?php endif; ?>
                </span>

                <a href="index.php?page=viewGrupos">Grupos</a>

                <a href="index.php?page=viewPerfil&id=<?= $_SESSION['usuario_id'] ?>">Mi perfil</a>

                <!-- Mobile: user info + logout inside nav dropdown -->
                <div class="nav-user-mobile">
                    <span style="font-size:.82rem; font-weight:600; color:var(--text);">
                        <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                    </span>
                    <span style="font-size:.72rem; color:var(--text-light);">
                        <?= htmlspecialchars($_SESSION['usuario_tipo']) ?>
                    </span>
                    <a href="index.php?action=logout" class="btn btn-secondary btn-sm mt-1" style="width:fit-content;">Cerrar sesión</a>
                </div>

            <?php else: ?>
                <a href="index.php?page=viewLogin">Iniciar sesión</a>
                <a href="index.php?page=viewRegistro">Registrarse</a>
            <?php endif; ?>
        </nav>
        <button class="nav-scroll-btn" id="navScrollBtn" aria-label="Más opciones" title="Más opciones">›</button>
        </div><!-- /.nav-links-wrap -->

        <?php if (isset($_SESSION['usuario_id'])): ?>
        <div class="nav-user">
            <span class="nav-user-name"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
            <span class="nav-user-role"><?= htmlspecialchars($_SESSION['usuario_tipo']) ?></span>
            <a href="index.php?action=logout" class="nav-logout">Salir</a>
        </div>
        <?php else: ?>
        <div class="nav-user">
            <a href="index.php?page=viewLogin" class="btn btn-secondary btn-sm">Entrar</a>
            <a href="index.php?page=viewRegistro" class="btn btn-primary btn-sm">Registrarse</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<main>
    <?php
    if (!empty($_SESSION['error'])) {
        echo '<p class="msg-error">' . htmlspecialchars($_SESSION['error']) . '</p>';
        unset($_SESSION['error']);
    }
    if (!empty($_SESSION['mensaje'])) {
        echo '<p class="msg-ok">' . htmlspecialchars($_SESSION['mensaje']) . '</p>';
        unset($_SESSION['mensaje']);
    }

    include "apps/views/$page.php";
    ?>
</main>

<footer>
    <p>
        <img src="public/img/LumenLogo.png" alt="" class="footer-logo-img">
        &copy; <?= date("Y") ?> LumenEdu &mdash; Plataforma educativa
    </p>
</footer>

<script src="public/js/main.js"></script>
</body>
</html>
