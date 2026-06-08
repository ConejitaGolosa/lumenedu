<?php
// Vista: chat de grupo
require_once __DIR__ . '/../models/modelGrupo.php';
require_once __DIR__ . '/../models/modelPerfil.php';

$idUsuario = (int)$_SESSION['usuario_id'];
$idGrupo   = (int)($_GET['id'] ?? 0);

if (!$idGrupo) {
    echo '<div class="alert alert-warn">Grupo no especificado. <a href="index.php?page=viewGrupos">Volver</a></div>';
    return;
}

$grupo = Grupo::getById($idGrupo, $idUsuario);
if (!$grupo) {
    echo '<div class="alert alert-error">Grupo no encontrado o no tienes acceso.</div>';
    return;
}

$mensajes  = Grupo::getMensajes($idGrupo);
$miembros  = Grupo::getMiembros($idGrupo);
$esCreador = (int)$grupo['IdCreador'] === $idUsuario;

$todosUsuarios = $esCreador ? Perfil::getUsuariosActivos($idUsuario) : [];
$idsMiembros   = array_column($miembros, 'IdUsuario');
$noMiembros    = array_filter($todosUsuarios, fn($u) => !in_array((int)$u['IdUsuario'], $idsMiembros));
?>

<div class="page-header">
    <a href="index.php?page=viewGrupos" class="btn btn-ghost btn-sm" style="margin-bottom:.75rem;">← Mis grupos</a>
    <h2><?= htmlspecialchars($grupo['Nombre']) ?></h2>
    <p><?= count($miembros) ?> miembro<?= count($miembros) != 1 ? 's' : '' ?></p>
</div>

<div class="grupo-layout">

    <!-- ── MENSAJES ──────────────────────────────────────────────── -->
    <div class="grupo-chat">

        <div class="chat-messages" id="chatMessages">
            <?php if (empty($mensajes)): ?>
                <p class="chat-empty">Sin mensajes aún. ¡Inicia la conversación!</p>
            <?php else: ?>
                <?php foreach ($mensajes as $m): ?>
                    <div class="chat-bubble-wrap <?= (int)$m['IdEmisor'] === $idUsuario ? 'mine' : 'theirs' ?>">
                        <?php if ((int)$m['IdEmisor'] !== $idUsuario): ?>
                            <span class="chat-author">
                                <?= htmlspecialchars($m['NombreUsuario']) ?>
                                <?= rolBadge($m['TipoUsuario']) ?>
                            </span>
                        <?php endif; ?>
                        <div class="chat-bubble">
                            <?= nl2br(htmlspecialchars($m['Contenido'])) ?>
                        </div>
                        <span class="chat-ts"><?= htmlspecialchars(substr($m['FechaEnvio'], 11, 5)) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form class="chat-form" id="chatSendForm">
            <textarea name="contenido" rows="2" maxlength="1024"
                      placeholder="Escribe un mensaje en el grupo…" required></textarea>
            <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
    </div>

    <!-- ── PANEL LATERAL: MIEMBROS ──────────────────────────────── -->
    <div class="grupo-sidebar">
        <h4 class="section-title">Miembros</h4>
        <div class="grupo-members-list">
            <?php foreach ($miembros as $m): ?>
                <a href="index.php?page=viewPerfil&id=<?= $m['IdUsuario'] ?>"
                   class="grupo-member">
                    <?php if ($m['FotoPerfil']): ?>
                        <img src="<?= htmlspecialchars($m['FotoPerfil']) ?>"
                             alt="" class="chat-avatar" style="width:34px;height:34px;font-size:.8rem;">
                    <?php else: ?>
                        <div class="chat-avatar chat-avatar-default" style="width:34px;height:34px;font-size:.8rem;">
                            <?= mb_strtoupper(mb_substr($m['NombreUsuario'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <span>
                        <?= htmlspecialchars($m['NombreUsuario']) ?>
                        <?= rolBadge($m['TipoUsuario']) ?>
                        <?php if ((int)$grupo['IdCreador'] === (int)$m['IdUsuario']): ?>
                            <span class="badge badge-gold" style="font-size:.62rem;">creador</span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($esCreador && !empty($noMiembros)): ?>
            <h4 class="section-title" style="margin-top:1.5rem;">Agregar miembro</h4>
            <form action="index.php" method="POST">
                <input type="hidden" name="action"   value="agregarAlGrupo">
                <input type="hidden" name="id_grupo" value="<?= $idGrupo ?>">
                <div class="form-group">
                    <select name="id_usuario" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($noMiembros as $u): ?>
                            <option value="<?= $u['IdUsuario'] ?>">
                                <?= htmlspecialchars($u['NombreUsuario']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">Agregar</button>
            </form>
        <?php endif; ?>
    </div>

</div><!-- /.grupo-layout -->

<script>
(function() {
    const cm = document.getElementById('chatMessages');
    if (!cm) return;
    cm.scrollTop = cm.scrollHeight;

    const idGrupo = <?= $idGrupo ?>;
    const idMio   = <?= $idUsuario ?>;
    let ultimoId  = <?= !empty($mensajes) ? (int)end($mensajes)['IdMensaje'] : 0 ?>;

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    }

    function appendMsg(m) {
        const esMio = m.IdEmisor == idMio;
        const wrap  = document.createElement('div');
        wrap.className = 'chat-bubble-wrap ' + (esMio ? 'mine' : 'theirs');
        const hora = (m.FechaEnvio || '').substring(11, 16);
        let html = '';
        if (!esMio) html += '<span class="chat-author">' + esc(m.NombreUsuario) + '</span>';
        html += '<div class="chat-bubble">' + esc(m.Contenido) + '</div>'
              + '<span class="chat-ts">' + hora + '</span>';
        wrap.innerHTML = html;
        cm.appendChild(wrap);
    }

    function poll() {
        fetch('apps/api/chat.php?tipo=grupo&id=' + idGrupo + '&desde_id=' + ultimoId)
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

    // Envío AJAX: sin recargar la página
    const sendForm = document.getElementById('chatSendForm');
    if (sendForm) {
        sendForm.addEventListener('submit', e => {
            e.preventDefault();
            e.stopImmediatePropagation();
            const ta  = sendForm.querySelector('textarea');
            const txt = ta.value.trim();
            if (!txt) return;
            const btn = sendForm.querySelector('button[type="submit"]');
            btn.disabled = true;

            const fd = new FormData();
            fd.append('action',    'enviarMensajeGrupo');
            fd.append('id_grupo',  idGrupo);
            fd.append('contenido', txt);

            fetch('index.php', { method: 'POST', body: fd })
                .then(() => {
                    ta.value     = '';
                    btn.disabled = false;
                    poll();
                    ta.focus();
                })
                .catch(() => { btn.disabled = false; });
        });
    }
})();
</script>
