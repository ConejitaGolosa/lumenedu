<?php
// Vista: editar metadatos de un video publicado (solo Creador propietario)
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
if (!$video || (int)$video['IdProfesor'] !== (int)$_SESSION['usuario_id'] || $video['Estado'] !== 'Publicado') {
    echo '<div class="alert alert-error">Video no encontrado, no te pertenece o no está publicado.</div>';
    echo '<a href="index.php?page=viewMisVideos" class="btn btn-secondary mt-2">Volver a Mis Videos</a>';
    return;
}

$categoriasVideo = ['Matemáticas','Física','Geometría','Química','Biología','Historia','Lenguaje','Tecnología','Otros'];
$opciones = [
    'Publico'      => 'Público — cualquiera puede verlo',
    'Suscriptores' => 'Solo suscriptores con ticket',
    'Privado'      => 'Privado — solo yo',
];
?>

<div class="page-header">
    <a href="index.php?page=viewMisVideos" class="btn btn-ghost btn-sm" style="margin-bottom:.75rem;">← Mis Videos</a>
    <h2>Editar video</h2>
    <p>Modifica el título, descripción, categoría, miniatura y privacidad.</p>
</div>

<div class="edit-video-grid">

    <!-- ══ COLUMNA IZQUIERDA: miniatura ═══════════════════════ -->
    <div class="edit-col-photo">
        <div class="card">
            <h3 style="margin-bottom:1rem;">Miniatura</h3>

            <?php if ($video['Miniatura']): ?>
                <div style="margin-bottom:.75rem;">
                    <img src="<?= htmlspecialchars($video['Miniatura']) ?>"
                         alt="Miniatura actual"
                         style="width:100%; aspect-ratio:16/9; object-fit:cover; border-radius:var(--radius);">
                </div>
            <?php else: ?>
                <div class="thumb-placeholder">
                    <span>Sin miniatura</span>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action"   value="editarVideo">
                <input type="hidden" name="id_video" value="<?= $idVideo ?>">
                <!-- Campos ocultos necesarios para que el submit parcial no los borre -->
                <input type="hidden" name="titulo"       value="<?= htmlspecialchars($video['Titulo'] ?? '') ?>">
                <input type="hidden" name="descripcion"  value="<?= htmlspecialchars($video['Descripcion'] ?? '') ?>">
                <input type="hidden" name="privacidad"   value="<?= htmlspecialchars($video['Privacidad'] ?? 'Publico') ?>">
                <input type="hidden" name="categoria"    value="<?= htmlspecialchars($video['Categoria'] ?? 'Otros') ?>">

                <div class="form-group mt-1">
                    <label for="miniatura">Nueva miniatura <small>(PNG o JPG, máx 2 MB)</small></label>
                    <input type="file" id="miniatura" name="miniatura" accept="image/png,image/jpeg">
                </div>

                <?php if ($video['Miniatura']): ?>
                    <label class="check-item" style="margin-bottom:.75rem;">
                        <input type="checkbox" name="borrar_miniatura" value="1">
                        <span>Eliminar miniatura actual</span>
                    </label>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary" style="width:100%;">Guardar miniatura</button>
            </form>
        </div>
    </div>

    <!-- ══ COLUMNA DERECHA: metadatos ═════════════════════════ -->
    <div class="edit-col-forms">
        <div class="card">
            <form action="index.php" method="POST">
                <input type="hidden" name="action"   value="editarVideo">
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
                    <label for="categoria">Categoría</label>
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
                                       <?= ($video['Privacidad'] ?? 'Publico') === $val ? 'checked' : '' ?> required>
                                <span><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="index.php?page=viewMisVideos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

</div><!-- /.edit-video-grid -->
