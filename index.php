<?php
// ============================================================
// index.php — Front controller y enrutador principal.
// Todo el tráfico pasa por aquí: despacha acciones a los
// controladores e incluye la vista correspondiente.
// ============================================================

session_start();

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
    'usarTicket'             => 'controllerTicket',
    'solicitarClase'         => 'controllerTicket',
    'responderSolicitud'     => 'controllerTicket',
    'crearForo'              => 'controllerForo',
    'comentarForo'           => 'controllerForo',
    'actualizarDiasMinimos'  => 'controllerProfesor',
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
    'viewForos', 'viewForo', 'viewConfigProfesor',
];

if (!in_array($page, $allowedPages)) {
    $page = 'viewHome';
}

// Redirige a home si el usuario ya tiene sesión e intenta entrar a login/registro
if (isset($_SESSION['usuario_id']) && in_array($page, ['viewLogin', 'viewRegistro'])) {
    header('Location: index.php?page=viewHome');
    exit;
}

// Contador de notificaciones no leídas para el badge del nav
$notifCount = 0;
if (isset($_SESSION['usuario_id'])) {
    require_once 'apps/models/modelNotificacion.php';
    $notifCount = Notificacion::countNoLeidas((int)$_SESSION['usuario_id']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LumenEdu</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>

<header>
    <h1>LumenEdu</h1>
    <nav>
        <a href="index.php?page=viewHome">Inicio</a>
        <a href="index.php?page=viewVideos">Videos</a>
        <a href="index.php?page=viewForos">Foros</a>

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
                    <?= $_SESSION['usuario_tipo'] === 'Administrador' ? 'Admin' : 'Moderación' ?>
                </a>
            <?php endif; ?>

            <!-- Badge de notificaciones -->
            <a href="index.php?page=viewNotificaciones">
                Notificaciones<?= $notifCount > 0 ? " ($notifCount)" : '' ?>
            </a>

            <span>
                <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>
                (<?= htmlspecialchars($_SESSION['usuario_tipo']) ?>)
            </span>
            <a href="index.php?action=logout">Salir</a>

        <?php else: ?>
            <a href="index.php?page=viewLogin">Iniciar sesión</a>
            <a href="index.php?page=viewRegistro">Registrarse</a>
        <?php endif; ?>

        <a href="index.php?page=viewAbout">Sobre nosotros</a>
    </nav>
</header>

<main>
    <?php
    // Flash messages (se muestran una vez y se borran)
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
    <p>&copy; <?= date("Y") ?> LumenEdu</p>
</footer>

</body>
</html>
