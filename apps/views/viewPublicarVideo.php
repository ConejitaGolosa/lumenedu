<?php
// Vista: formulario para asignar título/descripción/privacidad a un video aprobado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<div class="alert alert-error">Acceso denegado.</div>';
    return;
}

require_once __DIR__ . '/../models/modelVideo.php';

$idVideo = (int)($_GET['id'] ?? 0);
if (!$idVideo) {
    echo '<div class="alert alert-warn">Video no especificado. <a href="index.php?page=viewMisVideos">Volver</a></div>';
    return;
}

$video = Video::getById($idVideo);
if (!$video || $video['Estado'] !== 'Aprobado' || (int)$video['IdProfesor'] !== (int)$_SESSION['usuario_id']) {
    echo '<div class="alert alert-error">Este video no existe, no te pertenece, o aún no está aprobado.</div>';
    echo '<a href="index.php?page=viewMisVideos" class="btn btn-secondary mt-2">Volver a Mis Videos</a>';
    return;
}

$opciones = [
    'Publico'      => 'Público — cualquiera puede verlo',
    'Suscriptores' => 'Solo suscriptores con ticket',
    'Privado'      => 'Privado — solo yo',
];
$categoriasVideo = ['Matemáticas','Física','Geometría','Química','Biología','Historia','Lenguaje','Tecnología','Otros'];
?>

<div class="page-header">
    <a href="index.php?page=viewMisVideos" class="btn btn-ghost btn-sm" style="margin-bottom:.75rem;">← Mis Videos</a>
    <h2>Publicar Video #<?= $idVideo ?></h2>
    <p>Completa los datos. Una vez publicado será visible según la privacidad que elijas.</p>
</div>

<div class="form-card" style="max-width:560px; margin:0;">
    <form action="index.php" method="POST">
        <input type="hidden" name="action"   value="publicarVideo">
        <input type="hidden" name="id_video" value="<?= $idVideo ?>">

        <div class="form-group">
            <label for="titulo">Título <small>(obligatorio)</small></label>
            <input type="text" id="titulo" name="titulo" maxlength="128" required
                   value="<?= htmlspecialchars($video['Titulo'] ?? '') ?>"
                   placeholder="Título del video">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción <small>(opcional)</small></label>
            <textarea id="descripcion" name="descripcion" rows="5" maxlength="2048"
                      placeholder="Describe el contenido del video…"><?= htmlspecialchars($video['Descripcion'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="categoria">Categoría del contenido</label>
            <select id="categoria" name="categoria">
                <?php foreach ($categoriasVideo as $cat): ?>
                    <option value="<?= $cat ?>"
                        <?= ($video['Categoria'] ?? 'Otros') === $cat ? 'selected' : '' ?>>
                        <?= $cat ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Privacidad</label>
            <div class="radio-group">
                <?php foreach ($opciones as $val => $label): ?>
                    <label class="radio-item">
                        <input type="radio" name="privacidad" value="<?= $val ?>"
                               id="priv_<?= $val ?>"
                               <?= ($video['Privacidad'] ?? 'Publico') === $val ? 'checked' : '' ?> required>
                        <span><?= $label ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Publicar video</button>
            <a href="index.php?page=viewMisVideos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
