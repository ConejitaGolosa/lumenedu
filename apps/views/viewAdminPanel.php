<?php
// Vista: panel del administrador para revisar videos pendientes
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Administrador') {
    echo '<p>Acceso restringido.</p>';
    return;
}

require_once __DIR__ . '/../models/modelVideo.php';
$pendientes = Video::getPendientes();
?>
<h2>Panel de Administración — Videos pendientes</h2>

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

            <!-- Reproductor del video para que el admin pueda verlo -->
            <video controls width="480" style="display:block; margin-bottom:10px;">
                <source src="<?= htmlspecialchars($v['ArchivoVideo']) ?>" type="video/mp4">
                Tu navegador no soporta el reproductor de video.
            </video>

            <!-- Formulario de revisión -->
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
