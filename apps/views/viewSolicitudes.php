<?php
// Vista: bandeja de solicitudes de clase recibidas (solo Creadores)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<div class="alert alert-warn">Esta sección es exclusiva para profesores.</div>';
    return;
}

require_once __DIR__ . '/../models/modelSolicitudClase.php';

$idUsuario   = (int)$_SESSION['usuario_id'];
$solicitudes = SolicitudClase::getDeProfesor($idUsuario);

$badgeSolicitud = [
    'Pendiente'              => 'badge-warn',
    'Aceptada'               => 'badge-ok',
    'Rechazada'              => 'badge-error',
    'AceptadaConCondiciones' => 'badge-gold',
];
?>

<div class="page-header">
    <h2>Solicitudes de Clase</h2>
    <p>Peticiones de clase virtual de tus alumnos.</p>
</div>

<?php if (empty($solicitudes)): ?>
    <div class="empty-state">
        <p>No tienes solicitudes de clase pendientes.</p>
        <a href="index.php?page=viewHome" class="btn btn-secondary">Volver al inicio</a>
    </div>
<?php else: ?>
    <div class="grid-list">
        <?php foreach ($solicitudes as $s): ?>
            <div class="card">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;">
                    <div>
                        <strong><?= htmlspecialchars($s['Estudiante']) ?></strong>
                        <span class="badge badge-muted" style="margin-left:.4rem;">Alumno</span>
                    </div>
                    <span class="badge <?= $badgeSolicitud[$s['Estado']] ?? 'badge-muted' ?>">
                        <?= htmlspecialchars($s['Estado']) ?>
                    </span>
                </div>

                <div class="card-meta" style="margin-bottom:.75rem;">
                    <span><strong>Fecha propuesta:</strong> <?= htmlspecialchars($s['FechaPropuesta']) ?></span>
                    <span>·</span>
                    <span><strong>Enviada:</strong> <?= htmlspecialchars($s['FechaSolicitud']) ?></span>
                </div>

                <?php if ($s['Estado'] === 'Pendiente'): ?>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action"       value="responderSolicitud">
                        <input type="hidden" name="id_solicitud" value="<?= $s['IdSolicitud'] ?>">

                        <div class="form-group">
                            <label for="estado_<?= $s['IdSolicitud'] ?>">Tu respuesta</label>
                            <select id="estado_<?= $s['IdSolicitud'] ?>" name="estado" required>
                                <option value="">— Elige —</option>
                                <option value="Aceptada">Aceptar</option>
                                <option value="Rechazada">Rechazar</option>
                                <option value="AceptadaConCondiciones">Aceptar con condiciones</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="resp_<?= $s['IdSolicitud'] ?>">
                                Descripción / condiciones <small>(obligatorio si aceptas con condiciones)</small>
                            </label>
                            <textarea id="resp_<?= $s['IdSolicitud'] ?>" name="respuesta"
                                      rows="2" maxlength="512"
                                      placeholder="Ej: prefiero el jueves a las 19hs por Meet…"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">Enviar respuesta</button>
                    </form>

                <?php elseif ($s['RespuestaProfesor']): ?>
                    <p><em>Tu respuesta: <?= htmlspecialchars($s['RespuestaProfesor']) ?></em></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
