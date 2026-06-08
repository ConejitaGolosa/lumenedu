<?php
// Vista: panel de videos del profesor
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<div class="alert alert-error">Acceso denegado.</div>';
    return;
}

require_once __DIR__ . '/../models/modelVideo.php';
$videos = Video::getMisVideos($_SESSION['usuario_id']);

$etiqEstado = [
    'Pendiente'  => 'En revisión',
    'Aprobado'   => 'Aprobado',
    'Rechazado'  => 'Rechazado',
    'Publicado'  => 'Publicado',
    'Eliminado'  => 'Eliminado',
];
$badgeEstado = [
    'Pendiente' => 'badge-warn',
    'Aprobado'  => 'badge-ok',
    'Rechazado' => 'badge-error',
    'Publicado' => 'badge-gold',
];
?>

<div class="page-header page-header-row">
    <div>
        <h2>Mis Videos</h2>
        <p>Gestiona tu biblioteca de contenido.</p>
    </div>
    <a href="index.php?page=viewSubirVideo" class="btn btn-primary">+ Subir video</a>
</div>

<?php if (empty($videos)): ?>
    <div class="empty-state">
        <p>Aún no has subido ningún video.</p>
        <a href="index.php?page=viewSubirVideo" class="btn btn-primary">Subir primer video</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Privacidad</th>
                    <th>Fecha subida</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($videos as $v): ?>
                <?php if ($v['Estado'] === 'Eliminado') continue; ?>
                <tr>
                    <td style="color:var(--text-light); font-size:.8rem;"><?= $v['IdVideo'] ?></td>
                    <td>
                        <strong style="color:var(--text); font-size:.875rem;">
                            <?= $v['Titulo'] ? htmlspecialchars($v['Titulo']) : '<em>Sin título</em>' ?>
                        </strong>
                        <?php if ($v['Estado'] === 'Rechazado' && $v['MotivoRechazo']): ?>
                            <br><small style="color:var(--error-text);">Motivo: <?= htmlspecialchars($v['MotivoRechazo']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $badgeEstado[$v['Estado']] ?? 'badge-muted' ?>">
                            <?= htmlspecialchars($etiqEstado[$v['Estado']] ?? $v['Estado']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($v['Estado'] === 'Publicado'): ?>
                            <form action="index.php" method="POST" class="form-inline">
                                <input type="hidden" name="action"   value="cambiarPrivacidad">
                                <input type="hidden" name="id_video" value="<?= $v['IdVideo'] ?>">
                                <select name="privacidad" class="privacy-select" title="Cambiar privacidad">
                                    <option value="Publico"      <?= $v['Privacidad']==='Publico'      ? 'selected':'' ?>>Público</option>
                                    <option value="Suscriptores" <?= $v['Privacidad']==='Suscriptores' ? 'selected':'' ?>>Suscriptores</option>
                                    <option value="Privado"      <?= $v['Privacidad']==='Privado'      ? 'selected':'' ?>>Privado</option>
                                </select>
                            </form>
                        <?php else: ?>
                            <span style="color:var(--text-light); font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem; white-space:nowrap;">
                        <?= htmlspecialchars($v['FechaSubida']) ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:.4rem; flex-wrap:wrap; align-items:center;">
                            <?php if ($v['Estado'] === 'Aprobado'): ?>
                                <a href="index.php?page=viewPublicarVideo&id=<?= $v['IdVideo'] ?>"
                                   class="btn btn-primary btn-sm">Publicar</a>

                            <?php elseif ($v['Estado'] === 'Publicado'): ?>
                                <a href="index.php?page=viewVideo&id=<?= $v['IdVideo'] ?>"
                                   class="btn btn-secondary btn-sm">Ver</a>
                            <?php endif; ?>

                            <form action="index.php" method="POST"
                                  onsubmit="return confirm('¿Eliminar este video? No podrás recuperarlo.');">
                                <input type="hidden" name="action"   value="eliminarMiVideo">
                                <input type="hidden" name="id_video" value="<?= $v['IdVideo'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
