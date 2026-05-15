<?php
// Vista: notificaciones del usuario con enlace directo a comentarios respondidos
if (!isset($_SESSION['usuario_id'])) {
    echo '<p>Debes iniciar sesión. <a href="index.php?page=viewLogin">Iniciar sesión</a></p>';
    return;
}

require_once __DIR__ . '/../models/modelNotificacion.php';
require_once __DIR__ . '/../models/modelComentario.php';

$idUsuario = (int)$_SESSION['usuario_id'];
Notificacion::marcarTodasLeidas($idUsuario);
$notifs = Notificacion::getByUsuario($idUsuario);

$iconos = [
    'VideoAprobado'       => '✓',
    'VideoRechazado'      => '✗',
    'SolicitudClase'      => '📅',
    'RespuestaSolicitud'  => '💬',
    'RespuestaComentario' => '↩',
];
?>
<h2>Mis Notificaciones</h2>

<?php if (empty($notifs)): ?>
    <p>No tienes notificaciones.</p>
<?php else: ?>
    <?php foreach ($notifs as $n): ?>
        <div style="border:1px solid #ddd; padding:10px; margin-bottom:8px; border-radius:4px;
                    background:<?= $n['Leida'] ? '#fff' : '#f0f7ff' ?>;">
            <small style="color:#888;"><?= htmlspecialchars($n['FechaNotificacion']) ?></small>
            <span style="margin-left:8px; font-weight:bold;">
                <?= $iconos[$n['Tipo']] ?? '' ?>
                <?= htmlspecialchars($n['Tipo']) ?>
            </span><br>
            <?= nl2br(htmlspecialchars($n['Mensaje'])) ?>

            <?php if ($n['IdReferencia'] && str_contains($n['Tipo'], 'Video')): ?>
                <br><a href="index.php?page=viewMisVideos">Ver mis videos</a>

            <?php elseif ($n['Tipo'] === 'RespuestaComentario' && $n['IdReferencia']): ?>
                <?php $ctx = Comentario::getContexto((int)$n['IdReferencia']); ?>
                <?php if ($ctx): ?>
                    <?php if ($ctx['IdVideo']): ?>
                        <br><a href="index.php?page=viewVideo&id=<?= $ctx['IdVideo'] ?>#comentario-<?= $n['IdReferencia'] ?>">
                            Ir al comentario y responder
                        </a>
                    <?php elseif ($ctx['IdForo']): ?>
                        <br><a href="index.php?page=viewForo&id=<?= $ctx['IdForo'] ?>#comentario-<?= $n['IdReferencia'] ?>">
                            Ir al comentario y responder
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
