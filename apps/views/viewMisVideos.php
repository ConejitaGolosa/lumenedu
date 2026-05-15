<?php
// Vista: panel de videos del profesor (estado, aprobaciones, publicar, editar, eliminar)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<p>Acceso denegado.</p>';
    return;
}

require_once __DIR__ . '/../models/modelVideo.php';
$videos = Video::getMisVideos($_SESSION['usuario_id']);

$etiqEstado = [
    'Pendiente'  => 'En revisión',
    'Aprobado'   => 'Aprobado — listo para publicar',
    'Rechazado'  => 'Rechazado',
    'Publicado'  => 'Publicado',
    'Eliminado'  => 'Eliminado',
];
?>
<h2>Mis Videos</h2>
<p><a href="index.php?page=viewSubirVideo">+ Subir nuevo video</a></p>

<?php if (empty($videos)): ?>
    <p>Aún no has subido ningún video.</p>
<?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>#</th><th>Título</th><th>Estado</th><th>Privacidad</th>
                <th>Fecha subida</th><th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($videos as $v): ?>
            <?php if ($v['Estado'] === 'Eliminado') continue; ?>
            <tr>
                <td><?= $v['IdVideo'] ?></td>
                <td><?= $v['Titulo'] ? htmlspecialchars($v['Titulo']) : '<em>Sin título</em>' ?></td>
                <td>
                    <?= htmlspecialchars($etiqEstado[$v['Estado']] ?? $v['Estado']) ?>
                    <?php if ($v['Estado'] === 'Rechazado' && $v['MotivoRechazo']): ?>
                        <br><small style="color:red;">Motivo: <?= htmlspecialchars($v['MotivoRechazo']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= $v['Privacidad'] ? htmlspecialchars($v['Privacidad']) : '—' ?></td>
                <td><?= htmlspecialchars($v['FechaSubida']) ?></td>
                <td>
                    <?php if ($v['Estado'] === 'Aprobado'): ?>
                        <a href="index.php?page=viewPublicarVideo&id=<?= $v['IdVideo'] ?>">Publicar</a>

                    <?php elseif ($v['Estado'] === 'Publicado'): ?>
                        <a href="index.php?page=viewVideo&id=<?= $v['IdVideo'] ?>">Ver</a>

                        <!-- Cambiar privacidad -->
                        <form action="index.php" method="POST"
                              style="display:inline-block; margin-left:8px;">
                            <input type="hidden" name="action"   value="cambiarPrivacidad">
                            <input type="hidden" name="id_video" value="<?= $v['IdVideo'] ?>">
                            <select name="privacidad" onchange="this.form.submit()"
                                    title="Cambiar privacidad">
                                <option value="Publico"      <?= $v['Privacidad']==='Publico'      ? 'selected':'' ?>>Público</option>
                                <option value="Suscriptores" <?= $v['Privacidad']==='Suscriptores' ? 'selected':'' ?>>Suscriptores</option>
                                <option value="Privado"      <?= $v['Privacidad']==='Privado'      ? 'selected':'' ?>>Privado</option>
                            </select>
                        </form>

                        <!-- Eliminar -->
                        <form action="index.php" method="POST"
                              style="display:inline-block; margin-left:6px;"
                              onsubmit="return confirm('¿Eliminar este video? No podrás recuperarlo.');">
                            <input type="hidden" name="action"   value="eliminarMiVideo">
                            <input type="hidden" name="id_video" value="<?= $v['IdVideo'] ?>">
                            <button type="submit"
                                    style="background:none; border:none; color:#dc3545;
                                           cursor:pointer; padding:0; font-size:0.9em;">
                                Eliminar
                            </button>
                        </form>

                    <?php else: ?>
                        <!-- Eliminar video pendiente o rechazado -->
                        <form action="index.php" method="POST"
                              onsubmit="return confirm('¿Eliminar este video?');">
                            <input type="hidden" name="action"   value="eliminarMiVideo">
                            <input type="hidden" name="id_video" value="<?= $v['IdVideo'] ?>">
                            <button type="submit"
                                    style="background:none; border:none; color:#dc3545;
                                           cursor:pointer; padding:0; font-size:0.9em;">
                                Eliminar
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
