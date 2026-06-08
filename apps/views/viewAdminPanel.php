<?php
// Vista: panel de moderación (Administrador + Moderador)
$tipoUsuario = $_SESSION['usuario_tipo'] ?? '';
$esAdmin     = $tipoUsuario === 'Administrador';
$esMod       = $tipoUsuario === 'Moderador';

if (!isset($_SESSION['usuario_id']) || (!$esAdmin && !$esMod)) {
    echo '<div class="alert alert-error">Acceso restringido.</div>';
    return;
}

require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelUser.php';

$pendientes = Video::getPendientes();
$usuarios   = $esAdmin ? Usuario::getUsuariosActivos() : [];
$baneados   = $esAdmin ? Usuario::getBaneados()         : [];
?>

<div class="page-header">
    <h2>Panel de <?= $esAdmin ? 'Administración' : 'Moderación' ?></h2>
    <p>Revisa el contenido pendiente y gestiona la plataforma.</p>
</div>

<!-- ── VIDEOS PENDIENTES ─────────────────────────────────────── -->
<div class="section">
    <h3 class="section-title">
        Videos pendientes de revisión
        <?php if (!empty($pendientes)): ?>
            <span class="badge badge-warn" style="margin-left:.5rem;"><?= count($pendientes) ?></span>
        <?php endif; ?>
    </h3>

    <?php if (empty($pendientes)): ?>
        <div class="empty-state" style="padding:2rem 1rem;">
            <p>No hay videos pendientes. Todo está al día.</p>
        </div>
    <?php else: ?>
        <?php foreach ($pendientes as $v): ?>
            <div class="review-card">
                <div class="review-card-header">
                    <div>
                        <strong>Video #<?= $v['IdVideo'] ?></strong>
                        &nbsp;—&nbsp;
                        Profesor: <strong><?= htmlspecialchars($v['Profesor']) ?></strong>
                    </div>
                    <small>Subido: <?= htmlspecialchars($v['FechaSubida']) ?></small>
                </div>
                <div class="review-card-body">
                    <video controls>
                        <source src="<?= htmlspecialchars($v['ArchivoVideo']) ?>" type="video/mp4">
                        Tu navegador no soporta el reproductor de video.
                    </video>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="action"   value="revisarVideo">
                        <input type="hidden" name="id_video" value="<?= $v['IdVideo'] ?>">

                        <div class="form-group">
                            <label class="check-item">
                                <input type="checkbox" name="validado" value="1"
                                       id="val_<?= $v['IdVideo'] ?>"
                                       onchange="document.getElementById('motivo_<?= $v['IdVideo'] ?>').required = !this.checked;">
                                <span><strong>Aprobar video</strong></span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="motivo_<?= $v['IdVideo'] ?>">
                                Motivo de rechazo <small>(obligatorio si no se aprueba)</small>
                            </label>
                            <textarea id="motivo_<?= $v['IdVideo'] ?>" name="motivo"
                                      rows="2" maxlength="512"
                                      placeholder="Describe por qué se rechaza el video…"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Enviar revisión</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($esAdmin): ?>

    <!-- ── GESTIÓN DE ROLES ──────────────────────────────────── -->
    <div class="section">
        <h3 class="section-title">Gestión de roles</h3>
        <p>Asigna o revoca roles a cualquier usuario de la plataforma.</p>

        <?php if (!empty($usuarios)): ?>
            <div class="card" style="max-width:480px;">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="asignarModerador">

                    <div class="form-group">
                        <label for="u_rol">Usuario</label>
                        <select id="u_rol" name="id_usuario" required>
                            <option value="">— Selecciona un usuario —</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['IdUsuario'] ?>">
                                    <?= htmlspecialchars($u['NombreUsuario']) ?>
                                    (<?= htmlspecialchars($u['TipoUsuario']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nuevo_rol">Nuevo rol</label>
                        <select id="nuevo_rol" name="nuevo_rol" required>
                            <option value="Moderador">Moderador</option>
                            <option value="Creador">Creador (profesor)</option>
                            <option value="Suscriptor">Suscriptor (alumno)</option>
                            <option value="EstudianteGratis">Estudiante Gratis</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Actualizar rol</button>
                </form>
            </div>
        <?php else: ?>
            <p>No hay usuarios disponibles.</p>
        <?php endif; ?>
    </div>

    <!-- ── BANEAR USUARIO ───────────────────────────────────── -->
    <div class="section">
        <h3 class="section-title">Banear usuario</h3>
        <p>El usuario baneado no podrá iniciar sesión y su sesión activa se cerrará en cuanto recargue la página.</p>

        <?php if (!empty($usuarios)): ?>
            <div class="card" style="max-width:480px;">
                <form action="index.php" method="POST"
                      onsubmit="return confirm('¿Banear a este usuario? Perderá el acceso de inmediato.');">
                    <input type="hidden" name="action" value="banearUsuario">
                    <div class="form-group">
                        <label for="ban_usuario">Usuario</label>
                        <select id="ban_usuario" name="id_usuario" required>
                            <option value="">— Selecciona un usuario —</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['IdUsuario'] ?>">
                                    <?= htmlspecialchars($u['NombreUsuario']) ?>
                                    (<?= htmlspecialchars($u['TipoUsuario']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger">Banear usuario</button>
                </form>
            </div>
        <?php else: ?>
            <p class="text-muted">No hay usuarios activos para banear.</p>
        <?php endif; ?>
    </div>

    <!-- ── USUARIOS BANEADOS ─────────────────────────────────── -->
    <div class="section">
        <h3 class="section-title">
            Usuarios baneados
            <?php if (!empty($baneados)): ?>
                <span class="badge badge-error" style="margin-left:.5rem;"><?= count($baneados) ?></span>
            <?php endif; ?>
        </h3>

        <?php if (empty($baneados)): ?>
            <div class="empty-state" style="padding:1.5rem 1rem;">
                <p>No hay usuarios baneados actualmente.</p>
            </div>
        <?php else: ?>
            <div class="ban-list">
                <?php foreach ($baneados as $b): ?>
                    <div class="ban-item">
                        <div class="ban-info">
                            <strong><?= htmlspecialchars($b['NombreUsuario']) ?></strong>
                            <span class="text-muted"><?= htmlspecialchars($b['Correo']) ?></span>
                            <span class="badge badge-muted"><?= htmlspecialchars($b['TipoUsuario']) ?></span>
                        </div>
                        <form action="index.php" method="POST"
                              onsubmit="return confirm('¿Desbanear a <?= htmlspecialchars($b['NombreUsuario'], ENT_QUOTES) ?>?');">
                            <input type="hidden" name="action"     value="desbanearUsuario">
                            <input type="hidden" name="id_usuario" value="<?= $b['IdUsuario'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">Quitar baneo</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── ELIMINAR VIDEO ────────────────────────────────────── -->
    <div class="section">
        <h3 class="section-title">Eliminar video</h3>
        <p>Oculta un video que viole las políticas de LumenEdu.</p>

        <div class="card" style="max-width:320px;">
            <form action="index.php" method="POST"
                  onsubmit="return confirm('¿Eliminar el video #' + document.getElementById('vid_eliminar').value + '? Esta acción es permanente.');">
                <input type="hidden" name="action" value="eliminarVideo">
                <div class="form-group">
                    <label for="vid_eliminar">ID del video</label>
                    <input type="number" id="vid_eliminar" name="id_video" min="1" required
                           placeholder="Ej: 12">
                </div>
                <button type="submit" class="btn btn-danger">Eliminar video</button>
            </form>
        </div>
    </div>

    <!-- ── SUSPENDER CANAL ───────────────────────────────────── -->
    <div class="section">
        <h3 class="section-title">Suspender canal</h3>
        <p>Suspende una cuenta y oculta todo su contenido. El usuario no podrá iniciar sesión.</p>

        <?php if (!empty($usuarios)): ?>
            <div class="card" style="max-width:480px;">
                <form action="index.php" method="POST"
                      onsubmit="return confirm('¿Suspender este canal? El usuario perderá acceso y se ocultará su contenido.');">
                    <input type="hidden" name="action" value="eliminarCanal">

                    <div class="form-group">
                        <label for="canal_suspender">Usuario a suspender</label>
                        <select id="canal_suspender" name="id_usuario" required>
                            <option value="">— Selecciona un usuario —</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['IdUsuario'] ?>">
                                    <?= htmlspecialchars($u['NombreUsuario']) ?>
                                    (<?= htmlspecialchars($u['TipoUsuario']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-danger">Suspender canal</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>
