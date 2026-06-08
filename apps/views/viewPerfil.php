<?php
// Vista: perfil público de un usuario
require_once __DIR__ . '/../models/modelPerfil.php';
require_once __DIR__ . '/../models/modelAmistad.php';

$idPerfil    = (int)($_GET['id'] ?? 0);
$idVisitante = (int)($_SESSION['usuario_id'] ?? 0);
$tipoVis     = $_SESSION['usuario_tipo'] ?? null;

if (!$idPerfil) {
    echo '<div class="alert alert-warn">Perfil no especificado.</div>';
    return;
}

$perfil = Perfil::getByUsuario($idPerfil);
if (!$perfil) {
    echo '<div class="alert alert-error">Usuario no encontrado.</div>';
    return;
}

$esPropio = $idVisitante && $idVisitante === (int)$perfil['IdUsuario'];
$esAmigo  = $idVisitante && !$esPropio ? Amistad::sonAmigos($idVisitante, $idPerfil) : false;
$puedeVer = Perfil::puedeVer($perfil, $idVisitante, $tipoVis, $esAmigo);
$relacion  = ($idVisitante && !$esPropio) ? Amistad::getRelacion($idVisitante, $idPerfil) : null;
$solicitudesPendientes = $esPropio ? Amistad::getSolicitudesPendientes($idVisitante) : [];
$amigos   = ($esPropio || $esAmigo || in_array($tipoVis, ['Creador','Moderador','Administrador']))
              ? Amistad::getAmigos($idPerfil) : [];

$foto = $perfil['FotoPerfil'] ? htmlspecialchars($perfil['FotoPerfil']) : null;
?>

<div class="profile-page">

    <!-- ── CABECERA ─────────────────────────────────────────────── -->
    <div class="profile-header card">
        <div class="profile-photo-wrap">
            <?php if ($foto): ?>
                <img src="<?= $foto ?>" alt="Foto de <?= htmlspecialchars($perfil['NombreUsuario']) ?>"
                     class="profile-photo">
            <?php else: ?>
                <div class="profile-photo profile-photo-default">
                    <?= mb_strtoupper(mb_substr($perfil['NombreUsuario'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <h2 class="profile-name">
                <?= htmlspecialchars($perfil['NombreUsuario']) ?>
                <?= rolBadge($perfil['TipoUsuario']) ?>
            </h2>
            <p class="profile-tipo"><?= htmlspecialchars($perfil['TipoUsuario']) ?></p>
            <p class="profile-fecha">Miembro desde <?= htmlspecialchars(substr($perfil['FechaRegistro'] ?? '', 0, 10)) ?></p>

            <?php if (!$puedeVer && !$esPropio): ?>
                <p class="text-muted" style="margin-top:.75rem; font-size:.875rem;">
                    Este perfil es privado.
                </p>
            <?php endif; ?>

            <!-- Acciones -->
            <?php if ($idVisitante && !$esPropio): ?>
                <div class="profile-actions">

                    <?php if ($relacion && $relacion['Estado'] === 'Aceptada'): ?>
                        <!-- Son amigos: botón eliminar y DM -->
                        <form action="index.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action"     value="eliminarAmigo">
                            <input type="hidden" name="id_amistad" value="<?= $relacion['IdAmistad'] ?>">
                            <input type="hidden" name="redirect"   value="index.php?page=viewPerfil&id=<?= $idPerfil ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">Eliminar amigo</button>
                        </form>
                        <a href="index.php?page=viewMensajes&usuario=<?= $idPerfil ?>" class="btn btn-primary btn-sm">Mensaje</a>

                    <?php elseif ($relacion && $relacion['Estado'] === 'Pendiente'): ?>
                        <?php if ((int)$relacion['IdSolicitante'] === $idVisitante): ?>
                            <!-- El visitante ya envió solicitud -->
                            <form action="index.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action"     value="cancelarSolicitud">
                                <input type="hidden" name="id_amistad" value="<?= $relacion['IdAmistad'] ?>">
                                <input type="hidden" name="redirect"   value="index.php?page=viewPerfil&id=<?= $idPerfil ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">Cancelar solicitud</button>
                            </form>
                        <?php else: ?>
                            <!-- El otro envió solicitud al visitante -->
                            <form action="index.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action"     value="aceptarSolicitud">
                                <input type="hidden" name="id_amistad" value="<?= $relacion['IdAmistad'] ?>">
                                <input type="hidden" name="redirect"   value="index.php?page=viewPerfil&id=<?= $idPerfil ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Aceptar solicitud</button>
                            </form>
                            <form action="index.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action"     value="rechazarSolicitud">
                                <input type="hidden" name="id_amistad" value="<?= $relacion['IdAmistad'] ?>">
                                <input type="hidden" name="redirect"   value="index.php?page=viewPerfil&id=<?= $idPerfil ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">Rechazar</button>
                            </form>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Sin relación: enviar solicitud -->
                        <form action="index.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action"      value="enviarSolicitud">
                            <input type="hidden" name="id_receptor" value="<?= $idPerfil ?>">
                            <input type="hidden" name="redirect"    value="index.php?page=viewPerfil&id=<?= $idPerfil ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Agregar amigo</button>
                        </form>
                        <a href="index.php?page=viewMensajes&usuario=<?= $idPerfil ?>" class="btn btn-secondary btn-sm">Mensaje</a>
                    <?php endif; ?>

                </div>
            <?php elseif ($esPropio): ?>
                <div class="profile-actions">
                    <a href="index.php?page=viewEditarPerfil" class="btn btn-primary btn-sm">Editar perfil</a>
                </div>
            <?php endif; ?>
        </div>
    </div><!-- /.profile-header -->

    <?php if ($puedeVer): ?>

    <!-- ── BIO + ENLACE ──────────────────────────────────────────── -->
    <?php if ($perfil['Biografia'] || $perfil['EnlacePersonal']): ?>
    <div class="card mt-2">
        <?php if ($perfil['Biografia']): ?>
            <p style="margin-bottom:<?= $perfil['EnlacePersonal'] ? '.75rem' : '0' ?>;">
                <?= nl2br(htmlspecialchars($perfil['Biografia'])) ?>
            </p>
        <?php endif; ?>
        <?php if ($perfil['EnlacePersonal']): ?>
            <a href="<?= htmlspecialchars($perfil['EnlacePersonal']) ?>"
               target="_blank" rel="noopener noreferrer" class="profile-link">
                <?= htmlspecialchars($perfil['EnlacePersonal']) ?>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── AMIGOS ────────────────────────────────────────────────── -->
    <?php if (!empty($amigos)): ?>
    <div class="section mt-3">
        <h3 class="section-title">Amigos (<?= count($amigos) ?>)</h3>
        <div class="friends-grid">
            <?php foreach ($amigos as $amigo): ?>
                <a href="index.php?page=viewPerfil&id=<?= $amigo['IdUsuario'] ?>" class="friend-card">
                    <?php if ($amigo['FotoPerfil']): ?>
                        <img src="<?= htmlspecialchars($amigo['FotoPerfil']) ?>"
                             alt="<?= htmlspecialchars($amigo['NombreUsuario']) ?>"
                             class="friend-photo">
                    <?php else: ?>
                        <div class="friend-photo friend-photo-default">
                            <?= mb_strtoupper(mb_substr($amigo['NombreUsuario'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <span class="friend-name"><?= htmlspecialchars($amigo['NombreUsuario']) ?>
                        <?= rolBadge($amigo['TipoUsuario']) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // $puedeVer ?>

    <!-- ── SOLICITUDES PENDIENTES (solo para el dueño) ───────────── -->
    <?php if ($esPropio && !empty($solicitudesPendientes)): ?>
    <div class="section mt-3">
        <h3 class="section-title">Solicitudes de amistad (<?= count($solicitudesPendientes) ?>)</h3>
        <div class="grid-list">
            <?php foreach ($solicitudesPendientes as $s): ?>
                <div class="card" style="display:flex; align-items:center; gap:1rem; padding:.9rem 1.25rem;">
                    <?php if ($s['FotoPerfil']): ?>
                        <img src="<?= htmlspecialchars($s['FotoPerfil']) ?>" alt=""
                             class="friend-photo" style="width:42px;height:42px;">
                    <?php else: ?>
                        <div class="friend-photo friend-photo-default" style="width:42px;height:42px;font-size:1.1rem;">
                            <?= mb_strtoupper(mb_substr($s['NombreUsuario'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div style="flex:1;">
                        <strong>
                            <a href="index.php?page=viewPerfil&id=<?= $s['IdUsuario'] ?>">
                                <?= htmlspecialchars($s['NombreUsuario']) ?>
                            </a>
                            <?= rolBadge($s['TipoUsuario']) ?>
                        </strong>
                        <small><?= htmlspecialchars(substr($s['FechaSolicitud'], 0, 10)) ?></small>
                    </div>
                    <div style="display:flex; gap:.5rem;">
                        <form action="index.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action"     value="aceptarSolicitud">
                            <input type="hidden" name="id_amistad" value="<?= $s['IdAmistad'] ?>">
                            <input type="hidden" name="redirect"   value="index.php?page=viewPerfil&id=<?= $idVisitante ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Aceptar</button>
                        </form>
                        <form action="index.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action"     value="rechazarSolicitud">
                            <input type="hidden" name="id_amistad" value="<?= $s['IdAmistad'] ?>">
                            <input type="hidden" name="redirect"   value="index.php?page=viewPerfil&id=<?= $idVisitante ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">Rechazar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.profile-page -->
