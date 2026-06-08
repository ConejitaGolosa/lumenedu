<?php
// Vista: configuración de disponibilidad del profesor
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    echo '<div class="alert alert-warn">Esta página es exclusiva para profesores.</div>';
    return;
}

require_once __DIR__ . '/../models/modelUser.php';

$idUsuario  = (int)$_SESSION['usuario_id'];
$diasActual = Usuario::getMinDias($idUsuario);
?>

<div class="page-header">
    <a href="index.php?page=viewSolicitudes" class="btn btn-ghost btn-sm" style="margin-bottom:.75rem;">← Solicitudes</a>
    <h2>Disponibilidad para clases</h2>
    <p>Define cuántos días de anticipación mínima necesitan tus alumnos para solicitar una clase virtual.</p>
</div>

<div class="form-card" style="max-width:400px; margin:0;">
    <div class="card-meta" style="margin-bottom:1.25rem;">
        <span>Configuración actual:</span>
        <span class="badge badge-gold"><?= $diasActual ?> día<?= $diasActual !== 1 ? 's' : '' ?></span>
    </div>

    <form action="index.php" method="POST">
        <input type="hidden" name="action" value="actualizarDiasMinimos">

        <div class="form-group">
            <label for="dias_minimos">Días mínimos de anticipación <small>(1–30)</small></label>
            <input type="number" id="dias_minimos" name="dias_minimos"
                   min="1" max="30" value="<?= $diasActual ?>" required
                   style="max-width:120px;">
            <span class="form-hint">Los alumnos recibirán un aviso si la fecha propuesta no cumple este plazo.</span>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
