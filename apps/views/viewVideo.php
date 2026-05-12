<?php
// Vista: reproductor de video individual + sección de comentarios
require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelComentario.php';
require_once __DIR__ . '/../models/modelTicket.php';

$idVideo     = (int)($_GET['id'] ?? 0);
$tipoUsuario = $_SESSION['usuario_tipo'] ?? null;
$idUsuario   = (int)($_SESSION['usuario_id'] ?? 0);

if (!$idVideo) {
    echo '<p>Video no especificado. <a href="index.php?page=viewVideos">Ver todos los videos</a></p>';
    return;
}

$video = Video::getById($idVideo);

if (!$video) {
    echo '<p>Video no encontrado.</p>';
    return;
}

// Control de acceso
$ticketedProfs = [];
if ($tipoUsuario === 'Suscriptor' && $idUsuario) {
    $ticketedProfs = Ticket::profesoresDesbloqueados($idUsuario);
}

if (!Video::puedeVer($video, $tipoUsuario, $idUsuario, $ticketedProfs)) {
    echo '<div style="border:1px solid #f5c6cb; background:#f8d7da; padding:12px; border-radius:4px;">';
    echo '<strong>Acceso restringido.</strong><br>';
    if ($tipoUsuario === 'EstudianteGratis') {
        echo 'Este video es exclusivo para suscriptores. <a href="index.php?page=viewRegistro">Regístrate</a>.';
    } elseif ($tipoUsuario === 'Suscriptor') {
        echo 'Necesitas un ticket con este profesor para ver su contenido. <a href="index.php?page=viewTickets">Usar un ticket</a>.';
    } else {
        echo 'No tienes permiso para ver este video.';
    }
    echo '</div>';
    return;
}

$comentarios = Comentario::getByVideo($idVideo);
?>

<h2><?= htmlspecialchars($video['Titulo'] ?? 'Video sin título') ?></h2>
<p>
    <strong>Profesor:</strong> <?= htmlspecialchars($video['Profesor']) ?> &nbsp;|&nbsp;
    <strong>Privacidad:</strong> <?= htmlspecialchars($video['Privacidad'] ?? '—') ?> &nbsp;|&nbsp;
    <strong>Publicado:</strong> <?= htmlspecialchars($video['FechaPublicacion'] ?? '—') ?>
</p>

<?php if ($video['Descripcion']): ?>
    <p><?= nl2br(htmlspecialchars($video['Descripcion'])) ?></p>
<?php endif; ?>

<!-- Reproductor HTML5 -->
<video controls width="720" style="display:block; max-width:100%; margin-bottom:20px;">
    <source src="<?= htmlspecialchars($video['ArchivoVideo']) ?>"
            type="video/<?= pathinfo($video['ArchivoVideo'], PATHINFO_EXTENSION) ?>">
    Tu navegador no soporta el reproductor de video.
</video>

<hr>

<!-- COMENTARIOS -->
<h3>Comentarios (<?= count($comentarios) ?>)</h3>

<?php foreach ($comentarios as $c): ?>
    <div style="border-left:3px solid #007BFF; padding:6px 12px; margin-bottom:10px;">
        <strong><?= htmlspecialchars($c['NombreUsuario']) ?></strong>
        <small style="color:#666;">(<?= htmlspecialchars($c['TipoUsuario']) ?>) — <?= htmlspecialchars($c['FechaComentario']) ?></small><br>
        <?= nl2br(htmlspecialchars($c['Contenido'])) ?>
    </div>
<?php endforeach; ?>

<?php if ($idUsuario): ?>
    <h4>Deja un comentario</h4>
    <form action="index.php" method="POST">
        <input type="hidden" name="action"   value="comentarVideo">
        <input type="hidden" name="id_video" value="<?= $idVideo ?>">
        <textarea name="contenido" rows="4" cols="60" maxlength="1024"
                  placeholder="Escribe tu comentario..." required></textarea><br><br>
        <input type="submit" value="Publicar comentario">
    </form>
<?php else: ?>
    <p><a href="index.php?page=viewLogin">Inicia sesión</a> para comentar.</p>
<?php endif; ?>

<p><a href="index.php?page=viewVideos">&larr; Volver a la lista de videos</a></p>
