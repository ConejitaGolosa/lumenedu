<?php
// Vista: reproductor de video individual + comentarios con respuestas anidadas
require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelComentario.php';
require_once __DIR__ . '/../models/modelTicket.php';

$idVideo     = (int)($_GET['id'] ?? 0);
$tipoUsuario = $_SESSION['usuario_tipo'] ?? null;
$idUsuario   = (int)($_SESSION['usuario_id'] ?? 0);

if (!$idVideo) {
    echo '<div class="alert alert-warn">Video no especificado. <a href="index.php?page=viewVideos">Ver todos los videos</a></div>';
    return;
}

$video = Video::getById($idVideo);

if (!$video) {
    echo '<div class="alert alert-error">Video no encontrado.</div>';
    return;
}

$ticketedProfs = [];
if ($tipoUsuario === 'Suscriptor' && $idUsuario) {
    $ticketedProfs = Ticket::profesoresDesbloqueados($idUsuario);
}

if (!Video::puedeVer($video, $tipoUsuario, $idUsuario, $ticketedProfs)) {
    echo '<div class="alert alert-error">';
    echo '<strong>Acceso restringido.</strong><br>';
    if ($tipoUsuario === 'EstudianteGratis') {
        echo 'Este video es exclusivo para suscriptores. <a href="index.php?page=viewRegistro">Regístrate como Suscriptor</a>.';
    } elseif ($tipoUsuario === 'Suscriptor') {
        echo 'Necesitas un ticket con este profesor. <a href="index.php?page=viewTickets">Usar un ticket</a>.';
    } else {
        echo 'No tienes permiso para ver este video.';
    }
    echo '</div>';
    return;
}

$comentarios = Comentario::getByVideo($idVideo);
?>

<div class="page-header">
    <a href="index.php?page=viewVideos" class="btn btn-ghost btn-sm" style="margin-bottom:.75rem;">← Volver a videos</a>
    <h2><?= htmlspecialchars($video['Titulo'] ?? 'Video sin título') ?></h2>
</div>

<div class="video-player-wrap">
    <video controls>
        <source src="<?= htmlspecialchars($video['ArchivoVideo']) ?>"
                type="video/<?= pathinfo($video['ArchivoVideo'], PATHINFO_EXTENSION) ?>">
        Tu navegador no soporta el reproductor de video.
    </video>
</div>

<div class="video-meta">
    <span><strong>Profesor:</strong> <?= htmlspecialchars($video['Profesor']) ?></span>
    <span class="sep">·</span>
    <span>
        <span class="badge <?= $video['Privacidad'] === 'Publico' ? 'badge-ok' : 'badge-warn' ?>">
            <?= htmlspecialchars($video['Privacidad'] ?? '—') ?>
        </span>
    </span>
    <span class="sep">·</span>
    <span><strong>Publicado:</strong> <?= htmlspecialchars($video['FechaPublicacion'] ?? '—') ?></span>
</div>

<?php if ($video['Descripcion']): ?>
    <div class="card mb-3">
        <div class="card-body"><?= nl2br(htmlspecialchars($video['Descripcion'])) ?></div>
    </div>
<?php endif; ?>

<!-- COMENTARIOS -->
<div class="comments-section">
    <h3 class="section-title">Comentarios (<span id="comentariosCount"><?= count($comentarios) ?></span>)</h3>

    <?php if (empty($comentarios)): ?>
        <p class="text-muted" data-empty-comments>Sé el primero en comentar este video.</p>
    <?php else: ?>
        <?php foreach ($comentarios as $c): ?>
            <?php $respuestas = Comentario::getRespuestas($c['IdComentario']); ?>
            <div class="comment" id="comentario-<?= $c['IdComentario'] ?>">
                <div class="comment-header">
                    <?= avatar($c['NombreUsuario'], $c['FotoPerfil'] ?? null) ?>
                    <span class="comment-author"><?= htmlspecialchars($c['NombreUsuario']) ?><?= rolBadge($c['TipoUsuario']) ?></span>
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
                                <input type="hidden" name="id_video"            value="<?= $idVideo ?>">
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
                                    <?= avatar($r['NombreUsuario'], $r['FotoPerfil'] ?? null, '26px') ?>
                                    <span class="comment-author"><?= htmlspecialchars($r['NombreUsuario']) ?><?= rolBadge($r['TipoUsuario']) ?></span>
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
            <h4>Deja un comentario</h4>
            <form action="index.php" method="POST">
                <input type="hidden" name="action"   value="comentarVideo">
                <input type="hidden" name="id_video" value="<?= $idVideo ?>">
                <div class="form-group">
                    <textarea name="contenido" rows="3" maxlength="1024"
                              placeholder="Escribe tu comentario…" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Publicar comentario</button>
            </form>
        </div>
    <?php else: ?>
        <p><a href="index.php?page=viewLogin">Inicia sesión</a> para comentar.</p>
    <?php endif; ?>
</div>

<?php $maxCommentId = Comentario::getMaxId('video', $idVideo); ?>
<script>
(function () {
    var desdeId    = <?= $maxCommentId ?>;
    var idVideo    = <?= $idVideo ?>;
    var logueado   = <?= $idUsuario ? 'true' : 'false' ?>;

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function nl2br(s) { return escHtml(s).replace(/\n/g,'<br>'); }

    function mkAvatar(nombre, foto, size) {
        size = size || '32px';
        if (foto) return '<img src="' + escHtml(foto) + '" alt="" class="comment-avatar" style="width:' + size + ';height:' + size + ';">';
        var i = escHtml((nombre || '?')[0].toUpperCase());
        return '<div class="comment-avatar comment-avatar-default" style="width:' + size + ';height:' + size + ';">' + i + '</div>';
    }

    function rolBadge(tipo) {
        if (tipo === 'Administrador') return '<span class="rol-badge rol-admin">Admin</span>';
        if (tipo === 'Moderador')     return '<span class="rol-badge rol-mod">Mod</span>';
        return '';
    }

    function buildComment(c) {
        var replyForm = logueado
            ? '<div>' +
                '<button class="reply-toggle btn btn-ghost btn-sm">Responder</button>' +
                '<div class="reply-form">' +
                    '<form action="index.php" method="POST" style="margin-top:.5rem;">' +
                        '<input type="hidden" name="action" value="responderComentario">' +
                        '<input type="hidden" name="id_comentario_padre" value="' + c.IdComentario + '">' +
                        '<input type="hidden" name="id_video" value="' + idVideo + '">' +
                        '<div class="form-group">' +
                            '<textarea name="contenido" rows="2" maxlength="1024" placeholder="Escribe tu respuesta…" required></textarea>' +
                        '</div>' +
                        '<button type="submit" class="btn btn-primary btn-sm">Publicar respuesta</button>' +
                    '</form>' +
                '</div>' +
              '</div>'
            : '';
        return '<div class="comment" id="comentario-' + c.IdComentario + '">' +
            '<div class="comment-header">' +
                mkAvatar(c.NombreUsuario, c.FotoPerfil) +
                '<span class="comment-author">' + escHtml(c.NombreUsuario) + rolBadge(c.TipoUsuario) + '</span>' +
                '<span class="badge badge-muted comment-role">' + escHtml(c.TipoUsuario) + '</span>' +
                '<span class="comment-date">' + escHtml(c.FechaComentario) + '</span>' +
            '</div>' +
            '<div class="comment-body">' + nl2br(c.Contenido) + '</div>' +
            replyForm +
            '<div class="replies"></div>' +
        '</div>';
    }

    function buildReply(r) {
        return '<div class="reply">' +
            '<div class="comment-header">' +
                mkAvatar(r.NombreUsuario, r.FotoPerfil, '26px') +
                '<span class="comment-author">' + escHtml(r.NombreUsuario) + rolBadge(r.TipoUsuario) + '</span>' +
                '<span class="badge badge-muted comment-role">' + escHtml(r.TipoUsuario) + '</span>' +
                '<span class="comment-date">' + escHtml(r.FechaComentario) + '</span>' +
            '</div>' +
            '<div class="comment-body">' + nl2br(r.Contenido) + '</div>' +
        '</div>';
    }

    function attachReplyToggle(el) {
        el.querySelectorAll('.reply-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = btn.nextElementSibling;
                if (form && form.classList.contains('reply-form')) {
                    var open = form.classList.toggle('open');
                    btn.textContent = open ? 'Cancelar' : 'Responder';
                    if (open) { var ta = form.querySelector('textarea'); if (ta) ta.focus(); }
                }
            });
        });
    }

    function poll() {
        fetch('apps/api/comentarios.php?tipo=video&id=' + idVideo + '&desde_id=' + desdeId)
            .then(function (r) { return r.json(); })
            .then(function (items) {
                if (!items.length) return;
                var section   = document.querySelector('.comments-section');
                var countEl   = document.getElementById('comentariosCount');
                var emptyMsg  = section.querySelector('[data-empty-comments]');

                items.forEach(function (item) {
                    desdeId = Math.max(desdeId, item.IdComentario);

                    if (!item.IdComentarioPadre) {
                        if (emptyMsg) { emptyMsg.remove(); emptyMsg = null; }
                        var wrap = document.createElement('div');
                        wrap.innerHTML = buildComment(item);
                        var newEl = wrap.firstElementChild;
                        var commentForm = section.querySelector('.comment-form');
                        if (commentForm) section.insertBefore(newEl, commentForm);
                        else section.appendChild(newEl);
                        attachReplyToggle(newEl);
                        if (countEl) countEl.textContent = parseInt(countEl.textContent || '0') + 1;
                    } else {
                        var parent = document.getElementById('comentario-' + item.IdComentarioPadre);
                        if (parent) {
                            var repliesDiv = parent.querySelector('.replies');
                            if (!repliesDiv) {
                                repliesDiv = document.createElement('div');
                                repliesDiv.className = 'replies';
                                parent.appendChild(repliesDiv);
                            }
                            repliesDiv.insertAdjacentHTML('beforeend', buildReply(item));
                        }
                    }
                });
            })
            .catch(function () {});
    }

    setInterval(poll, 5000);
})();
</script>
