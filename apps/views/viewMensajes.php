<?php
// Vista: mensajes directos
require_once __DIR__ . '/../models/modelMensaje.php';
require_once __DIR__ . '/../models/modelPerfil.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?page=viewLogin');
    exit;
}

$idUsuario      = (int)$_SESSION['usuario_id'];
$idConversacion = (int)($_GET['usuario'] ?? 0);

$conversaciones = Mensaje::getConversaciones($idUsuario);
$mensajes       = $idConversacion ? Mensaje::getConversacion($idUsuario, $idConversacion) : [];
$contacto       = $idConversacion ? Perfil::getByUsuario($idConversacion) : null;
$todosUsuarios  = $idConversacion ? [] : Perfil::getUsuariosActivos($idUsuario);
?>

<div class="chat-layout">

    <!-- ── LISTA DE CONVERSACIONES ──────────────────────────────── -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h3>Mensajes</h3>
            <a href="index.php?page=viewMensajes" class="btn btn-ghost btn-sm">+ Nuevo</a>
        </div>

        <?php if (empty($conversaciones)): ?>
            <p class="chat-empty">Sin conversaciones.</p>
        <?php else: ?>
            <div class="chat-list">
                <?php foreach ($conversaciones as $c): ?>
                    <a href="index.php?page=viewMensajes&usuario=<?= $c['IdUsuario'] ?>"
                       class="chat-list-item <?= $idConversacion === (int)$c['IdUsuario'] ? 'active' : '' ?>">

                        <?php if ($c['FotoPerfil']): ?>
                            <img src="<?= htmlspecialchars($c['FotoPerfil']) ?>"
                                 alt="" class="chat-avatar">
                        <?php else: ?>
                            <div class="chat-avatar chat-avatar-default">
                                <?= mb_strtoupper(mb_substr($c['NombreUsuario'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="chat-list-info">
                            <span class="chat-list-name">
                                <?= htmlspecialchars($c['NombreUsuario']) ?>
                                <?= rolBadge($c['TipoUsuario']) ?>
                                <?php if ((int)$c['NoLeidos'] > 0): ?>
                                    <span class="chat-unread-dot"></span>
                                <?php endif; ?>
                            </span>
                            <span class="chat-list-preview">
                                <?= htmlspecialchars(mb_substr($c['UltimoMensaje'] ?? '', 0, 40)) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── PANEL DERECHO ────────────────────────────────────────── -->
    <div class="chat-main">

        <?php if ($contacto): ?>

            <div class="chat-header">
                <?php if ($contacto['FotoPerfil']): ?>
                    <img src="<?= htmlspecialchars($contacto['FotoPerfil']) ?>"
                         alt="" class="chat-avatar">
                <?php else: ?>
                    <div class="chat-avatar chat-avatar-default">
                        <?= mb_strtoupper(mb_substr($contacto['NombreUsuario'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <strong>
                        <a href="index.php?page=viewPerfil&id=<?= $contacto['IdUsuario'] ?>">
                            <?= htmlspecialchars($contacto['NombreUsuario']) ?>
                        </a>
                        <?= rolBadge($contacto['TipoUsuario']) ?>
                    </strong>
                    <small><?= htmlspecialchars($contacto['TipoUsuario']) ?></small>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($mensajes)): ?>
                    <p class="chat-empty">Inicia la conversación.</p>
                <?php else: ?>
                    <?php foreach ($mensajes as $m): ?>
                        <div class="chat-bubble-wrap <?= (int)$m['IdEmisor'] === $idUsuario ? 'mine' : 'theirs' ?>">
                            <div class="chat-bubble">
                                <?= nl2br(htmlspecialchars($m['ContenidoMensaje'])) ?>
                            </div>
                            <span class="chat-ts"><?= htmlspecialchars(substr($m['FechaMensaje'], 11, 5)) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form action="index.php" method="POST" class="chat-form">
                <input type="hidden" name="action"      value="enviarMensaje">
                <input type="hidden" name="id_receptor" value="<?= $idConversacion ?>">
                <textarea name="contenido" rows="2" maxlength="1024"
                          placeholder="Escribe un mensaje…" required></textarea>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </form>

        <?php elseif (!empty($todosUsuarios)): ?>

            <!-- Nueva conversación: seleccionar usuario -->
            <div class="chat-new-wrap">
                <h3 style="margin-bottom:1rem;">Nueva conversación</h3>
                <div class="user-picker-list">
                    <?php foreach ($todosUsuarios as $u): ?>
                        <a href="index.php?page=viewMensajes&usuario=<?= $u['IdUsuario'] ?>"
                           class="user-picker-item">
                            <div class="chat-avatar chat-avatar-default">
                                <?= mb_strtoupper(mb_substr($u['NombreUsuario'], 0, 1)) ?>
                            </div>
                            <span>
                                <?= htmlspecialchars($u['NombreUsuario']) ?>
                                <?= rolBadge($u['TipoUsuario']) ?>
                            </span>
                            <small class="text-muted"><?= htmlspecialchars($u['TipoUsuario']) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>

            <div class="chat-empty" style="margin:auto; text-align:center;">
                <p>Selecciona una conversación o <a href="index.php?page=viewMensajes">inicia una nueva</a>.</p>
            </div>

        <?php endif; ?>

    </div><!-- /.chat-main -->

</div><!-- /.chat-layout -->

<script>
(function() {
    const cm = document.getElementById('chatMessages');
    if (!cm) return;
    cm.scrollTop = cm.scrollHeight;

    const idOtro = <?= $idConversacion ?>;
    if (!idOtro) return;

    const idMio = <?= $idUsuario ?>;
    let ultimoId = <?= !empty($mensajes) ? (int)end($mensajes)['IdMensaje'] : 0 ?>;

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    }

    function appendMsg(m) {
        const wrap = document.createElement('div');
        wrap.className = 'chat-bubble-wrap ' + (m.IdEmisor == idMio ? 'mine' : 'theirs');
        const hora = (m.FechaMensaje || '').substring(11, 16);
        wrap.innerHTML = '<div class="chat-bubble">' + esc(m.ContenidoMensaje) + '</div>'
                       + '<span class="chat-ts">' + hora + '</span>';
        cm.appendChild(wrap);
    }

    function poll() {
        fetch('apps/api/chat.php?tipo=dm&usuario=' + idOtro + '&desde_id=' + ultimoId)
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data) || !data.length) return;
                const atBottom = cm.scrollHeight - cm.scrollTop <= cm.clientHeight + 60;
                data.forEach(m => {
                    appendMsg(m);
                    ultimoId = Math.max(ultimoId, parseInt(m.IdMensaje) || 0);
                });
                if (atBottom) cm.scrollTop = cm.scrollHeight;
            })
            .catch(() => {});
    }

    setInterval(poll, 3000);
})();
</script>
