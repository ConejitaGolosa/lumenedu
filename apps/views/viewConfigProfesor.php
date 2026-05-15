<?php
// Vista: configuración de disponibilidad del profesor (días mínimos para clases)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<p>Esta página es exclusiva para profesores.</p>';
    return;
}

require_once __DIR__ . '/../models/modelUser.php';

$idUsuario  = (int)$_SESSION['usuario_id'];
$diasActual = Usuario::getMinDias($idUsuario);
?>

<h2>Configuración de disponibilidad</h2>

<p>
    Define cuántos días de anticipación mínima necesitas para recibir solicitudes de clase virtual.<br>
    Los alumnos verán un aviso si la fecha que proponen no cumple este plazo.
</p>

<form action="index.php" method="POST">
    <input type="hidden" name="action" value="actualizarDiasMinimos">

    <label for="dias_minimos">
        Días mínimos de anticipación <small>(entre 1 y 30)</small>:
    </label><br>
    <input type="number" id="dias_minimos" name="dias_minimos"
           min="1" max="30" value="<?= $diasActual ?>" required
           style="width:80px;"><br><br>

    <p>Valor actual: <strong><?= $diasActual ?> día(s)</strong></p>

    <input type="submit" value="Guardar configuración">
</form>

<br>
<p><a href="index.php?page=viewSolicitudes">&larr; Volver a solicitudes</a></p>
