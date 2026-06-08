<?php
// Vista: listado de videos accesibles según el tipo de usuario
require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelTicket.php';

$tipoUsuario   = $_SESSION['usuario_tipo']   ?? null;
$idUsuario     = (int)($_SESSION['usuario_id'] ?? 0);
$ticketedProfs = [];

if ($tipoUsuario === 'Suscriptor' && $idUsuario) {
    $ticketedProfs = Ticket::profesoresDesbloqueados($idUsuario);
}

$videos = Video::getListaVisible($tipoUsuario, $ticketedProfs);
?>

<div class="page-header page-header-row">
    <div>
        <h2>Videos disponibles</h2>
        <p>Contenido publicado por profesores de la plataforma.</p>
    </div>
</div>

<?php if (!$tipoUsuario): ?>
    <div class="alert alert-warn mb-2">
        <a href="index.php?page=viewLogin">Inicia sesión</a> para acceder a más contenido exclusivo.
    </div>
<?php elseif ($tipoUsuario === 'EstudianteGratis'): ?>
    <div class="alert alert-warn mb-2">
        Estás viendo los videos públicos.
        <a href="index.php?page=viewRegistro">Regístrate como Suscriptor</a> para acceder a más contenido.
    </div>
<?php elseif ($tipoUsuario === 'Suscriptor'): ?>
    <div class="alert alert-ok mb-2">
        Tienes <strong><?= count($ticketedProfs) ?>/3</strong> ticket(s) activos este mes.
        <a href="index.php?page=viewTickets">Gestionar tickets</a>
    </div>
<?php endif; ?>

<?php if (empty($videos)): ?>
    <div class="empty-state">
        <p>No hay videos disponibles por ahora.</p>
        <?php if ($tipoUsuario === 'Creador'): ?>
            <a href="index.php?page=viewSubirVideo" class="btn btn-primary">Subir el primer video</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($videos as $v): ?>
            <div class="card video-card">
                <div class="card-title">
                    <a href="index.php?page=viewVideo&id=<?= $v['IdVideo'] ?>">
                        <?= htmlspecialchars($v['Titulo']) ?>
                    </a>
                </div>
                <div class="card-meta">
                    <span><?= htmlspecialchars($v['Profesor']) ?></span>
                    <span class="sep">·</span>
                    <span class="badge <?= $v['Privacidad'] === 'Publico' ? 'badge-ok' : 'badge-warn' ?>">
                        <?= $v['Privacidad'] === 'Suscriptores' ? 'Suscriptores' : htmlspecialchars($v['Privacidad']) ?>
                    </span>
                </div>
                <?php if ($v['Descripcion']): ?>
                    <div class="card-body">
                        <?= nl2br(htmlspecialchars(mb_substr($v['Descripcion'], 0, 160))) ?>…
                    </div>
                <?php endif; ?>
                <div class="card-footer">
                    <a href="index.php?page=viewVideo&id=<?= $v['IdVideo'] ?>" class="btn btn-ghost btn-sm">
                        Ver video →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
