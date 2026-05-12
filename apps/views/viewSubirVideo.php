<?php
// Vista: formulario de subida de video (solo Creadores)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<p>Acceso denegado. Solo los profesores pueden subir videos.</p>';
    return;
}
?>
<h2>Subir Video</h2>
<p>El video será revisado por un administrador antes de que puedas publicarlo.</p>

<form action="index.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="subirVideo">

    <label for="video">Seleccionar video (mp4, avi, mov, mkv, webm — máx. 500 MB):</label><br>
    <input type="file" id="video" name="video" accept="video/*" required><br><br>

    <input type="submit" value="Enviar a revisión">
</form>

<p><a href="index.php?page=viewMisVideos">&larr; Volver a Mis Videos</a></p>
