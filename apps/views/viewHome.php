<?php
// Vista parcial — dashboard de inicio según tipo de usuario
require_once __DIR__ . '/../models/modelNotificacion.php';

$tipo      = $_SESSION['usuario_tipo']   ?? null;
$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
?>

<h2>Bienvenido a LumenEdu</h2>

<?php if (!$tipo): ?>
    <p>La plataforma donde profesores comparten conocimiento y alumnos aprenden a su ritmo.</p>
    <p>
        <a href="index.php?page=viewRegistro">Crear cuenta</a> &nbsp;|&nbsp;
        <a href="index.php?page=viewLogin">Iniciar sesión</a> &nbsp;|&nbsp;
        <a href="index.php?page=viewVideos">Ver videos públicos</a>
    </p>

<?php elseif ($tipo === 'Administrador'): ?>
    <p>Panel de administración.</p>
    <ul>
        <li><a href="index.php?page=viewAdminPanel">Revisar videos pendientes</a></li>
        <li><a href="index.php?page=viewNotificaciones">Mis notificaciones</a></li>
    </ul>

<?php elseif ($tipo === 'Creador'): ?>
    <p>Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>. Panel del profesor.</p>
    <ul>
        <li><a href="index.php?page=viewSubirVideo">Subir nuevo video</a></li>
        <li><a href="index.php?page=viewMisVideos">Mis videos</a></li>
        <li><a href="index.php?page=viewSolicitudes">Solicitudes de clase recibidas</a></li>
        <li><a href="index.php?page=viewVideos">Ver videos de la plataforma</a></li>
        <li><a href="index.php?page=viewNotificaciones">Mis notificaciones</a></li>
    </ul>

<?php elseif ($tipo === 'Suscriptor'): ?>
    <p>Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>. Panel del alumno.</p>
    <ul>
        <li><a href="index.php?page=viewVideos">Ver videos</a></li>
        <li><a href="index.php?page=viewTickets">Mis tickets y solicitudes de clase</a></li>
        <li><a href="index.php?page=viewNotificaciones">Mis notificaciones</a></li>
    </ul>

<?php elseif ($tipo === 'EstudianteGratis'): ?>
    <p>Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>. Bienvenido.</p>
    <ul>
        <li><a href="index.php?page=viewVideos">Ver videos públicos</a></li>
        <li><a href="index.php?page=viewNotificaciones">Mis notificaciones</a></li>
    </ul>
    <p><small>Con una cuenta Suscriptor tendrías acceso a más contenido y clases en vivo.</small></p>
<?php endif; ?>
