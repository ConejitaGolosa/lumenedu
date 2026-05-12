<?php
// Vista: listado de videos accesibles según el tipo de usuario
require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelTicket.php';

$tipoUsuario   = $_SESSION['usuario_tipo']   ?? null;
$idUsuario     = (int)($_SESSION['usuario_id'] ?? 0);
$ticketedProfs = [];

// Los Suscriptores pueden ver contenido adicional de profesores ticketeados
if ($tipoUsuario === 'Suscriptor' && $idUsuario) {
    $ticketedProfs = Ticket::profesoresDesbloqueados($idUsuario);
}

$videos = Video::getListaVisible($tipoUsuario, $ticketedProfs);
?>
<h2>Videos disponibles</h2>

<?php if (!$tipoUsuario): ?>
    <p><em>Inicia sesión para acceder a más contenido.</em></p>
<?php elseif ($tipoUsuario === 'EstudianteGratis'): ?>
    <p><em>Estás viendo los videos públicos. <a href="index.php?page=viewRegistro">Regístrate como Suscriptor</a> para acceder a más contenido.</em></p>
<?php elseif ($tipoUsuario === 'Suscriptor'): ?>
    <p>Tienes <?= count($ticketedProfs) ?>/3 ticket(s) usados este mes.
       <a href="index.php?page=viewTickets">Gestionar tickets</a></p>
<?php endif; ?>

<?php if (empty($videos)): ?>
    <p>No hay videos disponibles por ahora.</p>
<?php else: ?>
    <div>
    <?php foreach ($videos as $v): ?>
        <div style="border:1px solid #ddd; padding:10px; margin-bottom:12px; border-radius:4px;">
            <strong><?= htmlspecialchars($v['Titulo']) ?></strong>
            <small style="color:#666;">
                — <?= htmlspecialchars($v['Profesor']) ?>
                — <?= $v['Privacidad'] === 'Suscriptores' ? 'Solo suscriptores' : htmlspecialchars($v['Privacidad']) ?>
            </small><br>
            <?php if ($v['Descripcion']): ?>
                <p style="margin:4px 0;"><?= nl2br(htmlspecialchars(mb_substr($v['Descripcion'], 0, 180))) ?>...</p>
            <?php endif; ?>
            <a href="index.php?page=viewVideo&id=<?= $v['IdVideo'] ?>">Ver video &rarr;</a>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
