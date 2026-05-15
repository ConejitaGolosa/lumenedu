<?php
// Vista: hilo individual con comentarios y respuestas anidadas
require_once __DIR__ . '/../models/modelForo.php';
require_once __DIR__ . '/../models/modelComentario.php';

$idForo    = (int)($_GET['id'] ?? 0);
$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);

if (!$idForo) {
    echo '<p>Foro no especificado. <a href="index.php?page=viewForos">Ver todos los foros</a></p>';
    return;
}

$foro = Foro::getById($idForo);
if (!$foro) {
    echo '<p>Hilo no encontrado. <a href="index.php?page=viewForos">Ver todos los foros</a></p>';
    return;
}

$comentarios = Comentario::getByForo($idForo);
?>

<p><a href="index.php?page=viewForos">&larr; Volver a los foros</a></p>

<h2><?= htmlspecialchars($foro['Titulo']) ?></h2>
<p>
    <span style="background:#e9ecef; padding:2px 8px; border-radius:10px; font-size:0.85em;">
        <?= htmlspecialchars($foro['Categoria']) ?>
    </span>
    &nbsp;
    <small style="color:#666;">
        Por <strong><?= htmlspecialchars($foro['Autor']) ?></strong>
        (<?= htmlspecialchars($foro['TipoUsuario']) ?>) —
        <?= htmlspecialchars($foro['FechaPublicacion']) ?>
    </small>
</p>

<div style="background:#f8f9fa; border-left:4px solid #007BFF;
            padding:12px 16px; margin:16px 0; border-radius:4px;">
    <?= nl2br(htmlspecialchars($foro['Contenido'])) ?>
</div>

<hr>
<h3>Respuestas (<?= count($comentarios) ?>)</h3>

<?php foreach ($comentarios as $c): ?>
    <?php $respuestas = Comentario::getRespuestas($c['IdComentario']); ?>
    <div id="comentario-<?= $c['IdComentario'] ?>"
         style="border-left:3px solid #007BFF; padding:6px 12px; margin-bottom:12px;">
        <strong><?= htmlspecialchars($c['NombreUsuario']) ?></strong>
        <small style="color:#666;">
            (<?= htmlspecialchars($c['TipoUsuario']) ?>) — <?= htmlspecialchars($c['FechaComentario']) ?>
        </small><br>
        <?= nl2br(htmlspecialchars($c['Contenido'])) ?>

        <?php if ($idUsuario): ?>
            <details style="margin-top:6px;">
                <summary style="cursor:pointer; font-size:0.85em; color:#007BFF;">Responder</summary>
                <form action="index.php" method="POST" style="margin-top:6px;">
                    <input type="hidden" name="action"              value="responderComentario">
                    <input type="hidden" name="id_comentario_padre" value="<?= $c['IdComentario'] ?>">
                    <input type="hidden" name="id_foro"             value="<?= $idForo ?>">
                    <textarea name="contenido" rows="2" cols="55" maxlength="1024"
                              placeholder="Escribe tu respuesta..." required></textarea><br>
                    <input type="submit" value="Publicar respuesta">
                </form>
            </details>
        <?php endif; ?>

        <?php if (!empty($respuestas)): ?>
            <div style="margin-left:20px; margin-top:8px;
                        border-left:2px solid #dee2e6; padding-left:12px;">
                <?php foreach ($respuestas as $r): ?>
                    <div style="margin-bottom:8px;">
                        <strong><?= htmlspecialchars($r['NombreUsuario']) ?></strong>
                        <small style="color:#666;">
                            (<?= htmlspecialchars($r['TipoUsuario']) ?>) — <?= htmlspecialchars($r['FechaComentario']) ?>
                        </small><br>
                        <?= nl2br(htmlspecialchars($r['Contenido'])) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if ($idUsuario): ?>
    <h4>Agregar respuesta al hilo</h4>
    <form action="index.php" method="POST">
        <input type="hidden" name="action"  value="comentarForo">
        <input type="hidden" name="id_foro" value="<?= $idForo ?>">
        <textarea name="contenido" rows="4" cols="60" maxlength="1024"
                  placeholder="Escribe tu aporte..." required></textarea><br><br>
        <input type="submit" value="Publicar">
    </form>
<?php else: ?>
    <p><a href="index.php?page=viewLogin">Inicia sesión</a> para participar en este hilo.</p>
<?php endif; ?>
