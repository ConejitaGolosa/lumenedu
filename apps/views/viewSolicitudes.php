<?php
// Vista: bandeja de solicitudes de clase recibidas (solo Creadores)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<p>Esta sección es exclusiva para profesores.</p>';
    return;
}

require_once __DIR__ . '/../models/modelSolicitudClase.php';

$idUsuario   = (int)$_SESSION['usuario_id'];
$solicitudes = SolicitudClase::getDeProfesor($idUsuario);
?>
<h2>Solicitudes de Clase</h2>

<?php if (empty($solicitudes)): ?>
    <p>No tienes solicitudes de clase pendientes.</p>
<?php else: ?>
    <?php foreach ($solicitudes as $s): ?>
        <div style="border:1px solid #ddd; padding:12px; margin-bottom:12px; border-radius:4px;">
            <strong>Alumno:</strong> <?= htmlspecialchars($s['Estudiante']) ?><br>
            <strong>Fecha propuesta:</strong> <?= htmlspecialchars($s['FechaPropuesta']) ?><br>
            <strong>Solicitud enviada:</strong> <?= htmlspecialchars($s['FechaSolicitud']) ?><br>
            <strong>Estado:</strong> <?= htmlspecialchars($s['Estado']) ?>

            <?php if ($s['Estado'] === 'Pendiente'): ?>
                <br><br>
                <form action="index.php" method="POST" style="display:inline-block;">
                    <input type="hidden" name="action"       value="responderSolicitud">
                    <input type="hidden" name="id_solicitud" value="<?= $s['IdSolicitud'] ?>">

                    <label for="estado_<?= $s['IdSolicitud'] ?>">Tu respuesta:</label><br>
                    <select id="estado_<?= $s['IdSolicitud'] ?>" name="estado" required>
                        <option value="">— Elige —</option>
                        <option value="Aceptada">Aceptar</option>
                        <option value="Rechazada">Rechazar</option>
                        <option value="AceptadaConCondiciones">Aceptar con condiciones</option>
                    </select><br><br>

                    <label for="resp_<?= $s['IdSolicitud'] ?>">
                        Descripción / condiciones <small>(obligatorio si aceptas con condiciones)</small>:
                    </label><br>
                    <textarea id="resp_<?= $s['IdSolicitud'] ?>" name="respuesta"
                              rows="3" cols="50" maxlength="512"
                              placeholder="Ej: prefiero el jueves a las 19hs por Meet..."></textarea><br><br>

                    <input type="submit" value="Enviar respuesta">
                </form>

            <?php elseif ($s['RespuestaProfesor']): ?>
                <br><em>Tu respuesta: <?= htmlspecialchars($s['RespuestaProfesor']) ?></em>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
