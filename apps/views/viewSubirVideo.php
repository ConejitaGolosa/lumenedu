<?php
// Vista: formulario de subida de video (solo Creadores)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<div class="alert alert-error">Acceso denegado. Solo los profesores pueden subir videos.</div>';
    return;
}
?>

<div class="page-header">
    <a href="index.php?page=viewMisVideos" class="btn btn-ghost btn-sm" style="margin-bottom:.75rem;">← Mis Videos</a>
    <h2>Subir Video</h2>
    <p>El video será revisado por un moderador antes de que puedas publicarlo.</p>
</div>

<div class="form-card" style="max-width:500px; margin:0;">
    <form action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="subirVideo">

        <div class="form-group">
            <label for="video">Archivo de video</label>
            <input type="file" id="video" name="video" accept="video/*" required
                   style="padding:.45rem .85rem;">
            <span class="form-hint">Formatos: mp4, avi, mov, mkv, webm — Máx. 500 MB</span>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enviar a revisión</button>
            <a href="index.php?page=viewMisVideos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
