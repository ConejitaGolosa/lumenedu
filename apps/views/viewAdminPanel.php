<?php
// Vista: panel de moderación (Administrador + Moderador)
$tipoUsuario = $_SESSION['usuario_tipo'] ?? '';
$esAdmin     = $tipoUsuario === 'Administrador';
$esMod       = $tipoUsuario === 'Moderador';

if (!isset($_SESSION['usuario_id']) || (!$esAdmin && !$esMod)) {
    echo '<p>Acceso restringido.</p>';
    return;
}

require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelUser.php';

$pendientes = Video::getPendientes();
$usuarios   = $esAdmin ? Usuario::getUsuariosActivos() : [];
?>

<h2>Panel de <?= $esAdmin ? 'Administración' : 'Moderación' ?></h2>

<!-- ── VIDEOS PENDIENTES (Admin + Moderador) ────────────────── -->
<h3>Videos pendientes de revisión</h3>

<?php if (empty($pendientes)): ?>
    <p>No hay videos pendientes de revisión.</p>
<?php else: ?>
    <p><?= count($pendientes) ?> video(s) esperando revisión.</p>

    <?php foreach ($pendientes as $v): ?>
        <div style="border:1px solid #ccc; padding:12px; margin-bottom:20px; border-radius:4px;">
            <strong>Video #<?= $v['IdVideo'] ?></strong> —
            Profesor: <strong><?= htmlspecialchars($v['Profesor']) ?></strong> —
            Subido: <?= htmlspecialchars($v['FechaSubida']) ?>
            <br><br>

            <video controls width="480" style="display:block; margin-bottom:10px;">
                <source src="<?= htmlspecialchars($v['ArchivoVideo']) ?>" type="video/mp4">
                Tu navegador no soporta el reproductor de video.
            </video>

            <form action="index.php" method="POST">
                <input type="hidden" name="action"   value="revisarVideo">
                <input type="hidden" name="id_video" value="<?= $v['IdVideo'] ?>">

                <label>
                    <input type="checkbox" name="validado" value="1" id="val_<?= $v['IdVideo'] ?>"
                           onchange="document.getElementById('motivo_<?= $v['IdVideo'] ?>').required = !this.checked;">
                    Validado (marcar para aprobar)
                </label><br><br>

                <label for="motivo_<?= $v['IdVideo'] ?>">
                    Motivo de rechazo <small>(obligatorio si NO se valida)</small>:
                </label><br>
                <textarea id="motivo_<?= $v['IdVideo'] ?>" name="motivo"
                          rows="3" cols="50" maxlength="512"
                          placeholder="Describe por qué se rechaza el video..."></textarea><br><br>

                <input type="submit" value="Enviar revisión">
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($esAdmin): ?>
    <hr>

    <!-- ── GESTIÓN DE ROLES ─────────────────────────────────── -->
    <h3>Gestión de roles</h3>
    <p>Asigna o revoca el rol de Moderador (u otros roles) a cualquier usuario.</p>

    <?php if (!empty($usuarios)): ?>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="asignarModerador">

            <label for="u_rol">Usuario:</label><br>
            <select id="u_rol" name="id_usuario" required>
                <option value="">— Selecciona un usuario —</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['IdUsuario'] ?>">
                        <?= htmlspecialchars($u['NombreUsuario']) ?>
                        (<?= htmlspecialchars($u['TipoUsuario']) ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="nuevo_rol">Nuevo rol:</label><br>
            <select id="nuevo_rol" name="nuevo_rol" required>
                <option value="Moderador">Moderador</option>
                <option value="Creador">Creador (profesor)</option>
                <option value="Suscriptor">Suscriptor (alumno)</option>
                <option value="EstudianteGratis">Estudiante Gratis</option>
            </select><br><br>

            <input type="submit" value="Actualizar rol">
        </form>
    <?php else: ?>
        <p>No hay usuarios disponibles.</p>
    <?php endif; ?>

    <hr>

    <!-- ── ELIMINAR VIDEO ───────────────────────────────────── -->
    <h3>Eliminar video</h3>
    <p>Oculta un video que viole las políticas de LumenEdu (el profesor no podrá recuperarlo).</p>

    <form action="index.php" method="POST"
          onsubmit="return confirm('¿Eliminar el video #' + document.getElementById('vid_eliminar').value + '? Esta acción es permanente.');">
        <input type="hidden" name="action" value="eliminarVideo">
        <label for="vid_eliminar">ID del video:</label><br>
        <input type="number" id="vid_eliminar" name="id_video" min="1" required
               style="width:100px;"><br><br>
        <input type="submit" value="Eliminar video"
               style="background:#dc3545; color:#fff; border:none;
                      padding:6px 14px; cursor:pointer; border-radius:4px;">
    </form>

    <hr>

    <!-- ── SUSPENDER CANAL ──────────────────────────────────── -->
    <h3>Suspender canal (usuario)</h3>
    <p>Suspende una cuenta y oculta todo su contenido. El usuario no podrá iniciar sesión.</p>

    <?php if (!empty($usuarios)): ?>
        <form action="index.php" method="POST"
              onsubmit="return confirm('¿Suspender este canal? El usuario perderá acceso y se ocultará su contenido.');">
            <input type="hidden" name="action" value="eliminarCanal">

            <label for="canal_suspender">Usuario a suspender:</label><br>
            <select id="canal_suspender" name="id_usuario" required>
                <option value="">— Selecciona un usuario —</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['IdUsuario'] ?>">
                        <?= htmlspecialchars($u['NombreUsuario']) ?>
                        (<?= htmlspecialchars($u['TipoUsuario']) ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <input type="submit" value="Suspender canal"
                   style="background:#dc3545; color:#fff; border:none;
                          padding:6px 14px; cursor:pointer; border-radius:4px;">
        </form>
    <?php endif; ?>
<?php endif; ?>
