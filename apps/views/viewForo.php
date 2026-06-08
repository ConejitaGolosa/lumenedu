<?php
// Vista: hilo individual con comentarios y respuestas anidadas
require_once __DIR__ . '/../models/modelForo.php';
require_once __DIR__ . '/../models/modelComentario.php';

$idForo    = (int)($_GET['id'] ?? 0);
$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);

if (!$idForo) {
    echo '<div class="alert alert-warn">Foro no especificado. <a href="index.php?page=viewForos">Ver todos los foros</a></div>';
    return;
}

$foro = Foro::getById($idForo);
if (!$foro) {
    echo '<div class="alert alert-error">Hilo no encontrado. <a href="index.php?page=viewForos">Ver todos los foros</a></div>';
    return;
}

$comentarios = Comentario::getByForo($idForo);
?>

<div class="page-header">
    <a href="index.php?page=viewForos" class="btn btn-ghost btn-sm" style="margin-bottom:.75rem;">← Volver a foros</a>
    <h2><?= htmlspecialchars($foro['Titulo']) ?></h2>
    <div class="card-meta" style="margin-top:.4rem;">
        <span class="badge badge-gold"><?= htmlspecialchars($foro['Categoria']) ?></span>
        <span>Por <strong><?= htmlspecialchars($foro['Autor']) ?></strong>
            (<?= htmlspecialchars($foro['TipoUsuario']) ?>)</span>
        <span>·</span>
        <span><?= htmlspecialchars($foro['FechaPublicacion']) ?></span>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body"><?= nl2br(htmlspecialchars($foro['Contenido'])) ?></div>
</div>

<!-- RESPUESTAS -->
<div class="comments-section">
    <h3 class="section-title">Respuestas (<?= count($comentarios) ?>)</h3>

    <?php if (empty($comentarios)): ?>
        <p class="text-muted">Aún no hay respuestas. ¡Sé el primero en participar!</p>
    <?php else: ?>
        <?php foreach ($comentarios as $c): ?>
            <?php $respuestas = Comentario::getRespuestas($c['IdComentario']); ?>
            <div class="comment" id="comentario-<?= $c['IdComentario'] ?>">
                <div class="comment-header">
                    <span class="comment-author"><?= htmlspecialchars($c['NombreUsuario']) ?></span>
                    <span class="badge badge-muted comment-role"><?= htmlspecialchars($c['TipoUsuario']) ?></span>
                    <span class="comment-date"><?= htmlspecialchars($c['FechaComentario']) ?></span>
                </div>
                <div class="comment-body"><?= nl2br(htmlspecialchars($c['Contenido'])) ?></div>

                <?php if ($idUsuario): ?>
                    <div>
                        <button class="reply-toggle btn btn-ghost btn-sm">Responder</button>
                        <div class="reply-form">
                            <form action="index.php" method="POST" style="margin-top:.5rem;">
                                <input type="hidden" name="action"              value="responderComentario">
                                <input type="hidden" name="id_comentario_padre" value="<?= $c['IdComentario'] ?>">
                                <input type="hidden" name="id_foro"             value="<?= $idForo ?>">
                                <div class="form-group">
                                    <textarea name="contenido" rows="2" maxlength="1024"
                                              placeholder="Escribe tu respuesta…" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Publicar respuesta</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($respuestas)): ?>
                    <div class="replies">
                        <?php foreach ($respuestas as $r): ?>
                            <div class="reply">
                                <div class="comment-header">
                                    <span class="comment-author"><?= htmlspecialchars($r['NombreUsuario']) ?></span>
                                    <span class="badge badge-muted comment-role"><?= htmlspecialchars($r['TipoUsuario']) ?></span>
                                    <span class="comment-date"><?= htmlspecialchars($r['FechaComentario']) ?></span>
                                </div>
                                <div class="comment-body"><?= nl2br(htmlspecialchars($r['Contenido'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($idUsuario): ?>
        <div class="comment-form">
            <h4>Agregar respuesta al hilo</h4>
            <form action="index.php" method="POST">
                <input type="hidden" name="action"  value="comentarForo">
                <input type="hidden" name="id_foro" value="<?= $idForo ?>">
                <div class="form-group">
                    <textarea name="contenido" rows="3" maxlength="1024"
                              placeholder="Escribe tu aporte…" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Publicar</button>
            </form>
        </div>
    <?php else: ?>
        <p><a href="index.php?page=viewLogin">Inicia sesión</a> para participar en este hilo.</p>
    <?php endif; ?>
</div>
