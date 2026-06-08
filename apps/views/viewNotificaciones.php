<?php
// Vista: notificaciones del usuario con enlace directo a comentarios respondidos
if (!isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-warn">Debes iniciar sesión. <a href="index.php?page=viewLogin">Iniciar sesión</a></div>';
    return;
}

require_once __DIR__ . '/../models/modelNotificacion.php';
require_once __DIR__ . '/../models/modelComentario.php';

$idUsuario = (int)$_SESSION['usuario_id'];
Notificacion::marcarTodasLeidas($idUsuario);
$notifs = Notificacion::getByUsuario($idUsuario);

$iconos = [
    'VideoAprobado'       => '✓',
    'VideoRechazado'      => '✕',
    'SolicitudClase'      => '📅',
    'RespuestaSolicitud'  => '💬',
    'RespuestaComentario' => '↩',
];
?>

<div class="page-header">
    <h2>Notificaciones</h2>
    <p>Todas tus alertas y novedades recientes.</p>
</div>

<?php if (empty($notifs)): ?>
    <div class="empty-state">
        <p>No tienes notificaciones por ahora.</p>
        <a href="index.php?page=viewHome" class="btn btn-secondary">Volver al inicio</a>
    </div>
<?php else: ?>
    <div class="notif-list">
        <?php foreach ($notifs as $n): ?>
            <div class="notif-item <?= $n['Leida'] ? '' : 'unread' ?>">
                <div class="notif-icon"><?= $iconos[$n['Tipo']] ?? '•' ?></div>
                <div class="notif-content">
                    <div class="notif-type"><?= htmlspecialchars($n['Tipo']) ?></div>
                    <div class="notif-msg"><?= nl2br(htmlspecialchars($n['Mensaje'])) ?></div>
                    <div class="notif-date"><?= htmlspecialchars($n['FechaNotificacion']) ?></div>

                    <?php if ($n['IdReferencia'] && str_contains($n['Tipo'], 'Video')): ?>
                        <a href="index.php?page=viewMisVideos" class="btn btn-ghost btn-sm mt-1">Ver mis videos</a>

                    <?php elseif ($n['Tipo'] === 'RespuestaComentario' && $n['IdReferencia']): ?>
                        <?php $ctx = Comentario::getContexto((int)$n['IdReferencia']); ?>
                        <?php if ($ctx): ?>
                            <?php if ($ctx['IdVideo']): ?>
                                <a href="index.php?page=viewVideo&id=<?= $ctx['IdVideo'] ?>#comentario-<?= $n['IdReferencia'] ?>"
                                   class="btn btn-ghost btn-sm mt-1">Ir al comentario →</a>
                            <?php elseif ($ctx['IdForo']): ?>
                                <a href="index.php?page=viewForo&id=<?= $ctx['IdForo'] ?>#comentario-<?= $n['IdReferencia'] ?>"
                                   class="btn btn-ghost btn-sm mt-1">Ir al comentario →</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
