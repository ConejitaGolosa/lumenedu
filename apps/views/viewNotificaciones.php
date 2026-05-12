<?php
// Vista: notificaciones del usuario con opción de marcarlas todas como leídas
if (!isset($_SESSION['usuario_id'])) {
    echo '<p>Debes iniciar sesión. <a href="index.php?page=viewLogin">Iniciar sesión</a></p>';
    return;
}

require_once __DIR__ . '/../models/modelNotificacion.php';

$idUsuario = (int)$_SESSION['usuario_id'];
Notificacion::marcarTodasLeidas($idUsuario); // Las marca leídas al entrar a esta vista
$notifs = Notificacion::getByUsuario($idUsuario);

$iconos = [
    'VideoAprobado'      => '✓',
    'VideoRechazado'     => '✗',
    'SolicitudClase'     => '📅',
    'RespuestaSolicitud' => '💬',
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
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
