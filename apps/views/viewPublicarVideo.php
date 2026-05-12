<?php
// Vista: formulario para que el profesor asigne título/descripción/privacidad
// a un video ya aprobado por el admin.
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<p>Acceso denegado.</p>';
    return;
}

require_once __DIR__ . '/../models/modelVideo.php';

$idVideo = (int)($_GET['id'] ?? 0);
if (!$idVideo) {
    echo '<p>Video no especificado. <a href="index.php?page=viewMisVideos">Volver</a></p>';
    return;
}

$video = Video::getById($idVideo);
if (!$video || $video['Estado'] !== 'Aprobado' || (int)$video['IdProfesor'] !== (int)$_SESSION['usuario_id']) {
    echo '<p>Este video no existe, no te pertenece, o aún no está aprobado.</p>';
    echo '<p><a href="index.php?page=viewMisVideos">Volver a Mis Videos</a></p>';
    return;
}
?>
<h2>Publicar Video #<?= $idVideo ?></h2>
<p>Completa los datos del video. Una vez publicado será visible según la privacidad que elijas.</p>

<form action="index.php" method="POST">
    <input type="hidden" name="action"   value="publicarVideo">
    <input type="hidden" name="id_video" value="<?= $idVideo ?>">

    <label for="titulo">Título <small>(obligatorio)</small>:</label><br>
    <input type="text" id="titulo" name="titulo" maxlength="128" required
           value="<?= htmlspecialchars($video['Titulo'] ?? '') ?>"><br><br>

    <label for="descripcion">Descripción:</label><br>
    <textarea id="descripcion" name="descripcion" rows="5" cols="60"
              maxlength="2048"><?= htmlspecialchars($video['Descripcion'] ?? '') ?></textarea><br><br>

    <label>Privacidad:</label><br>
    <?php foreach (['Publico' => 'Público (cualquiera puede verlo)',
                    'Suscriptores' => 'Solo suscriptores con ticket',
                    'Privado' => 'Privado (solo yo)'] as $val => $label): ?>
        <input type="radio" name="privacidad" value="<?= $val ?>"
               id="priv_<?= $val ?>"
               <?= ($video['Privacidad'] ?? 'Publico') === $val ? 'checked' : '' ?> required>
        <label for="priv_<?= $val ?>"><?= $label ?></label><br>
    <?php endforeach; ?><br>

    <input type="submit" value="Publicar video">
</form>

<p><a href="index.php?page=viewMisVideos">&larr; Cancelar</a></p>
