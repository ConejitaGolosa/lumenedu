<?php
// Vista: gestión de tickets y solicitudes de clase (solo Suscriptores)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Suscriptor') {
    echo '<p>Esta sección es exclusiva para alumnos suscritos.</p>';
    return;
}

require_once __DIR__ . '/../models/modelTicket.php';
require_once __DIR__ . '/../models/modelVideo.php';
require_once __DIR__ . '/../models/modelSolicitudClase.php';
require_once __DIR__ . '/../models/modelUser.php';

$idUsuario     = (int)$_SESSION['usuario_id'];
$usados        = Ticket::usadosEsteMes($idUsuario);
$disponibles   = 3 - $usados;
$misTickets    = Ticket::getMisTickets($idUsuario);
$desbloqueados = Ticket::profesoresDesbloqueados($idUsuario);
$profesores    = Video::getTodosProfesores();   // incluye DiasAntMinimo
$solicitudes   = SolicitudClase::getDeEstudiante($idUsuario);

// Mapa idProfesor → DiasAntMinimo para los profesores con ticket (se usa en el JS)
$diasPorProfesor = [];
foreach ($misTickets as $t) {
    $diasPorProfesor[$t['IdProfesor']] = Usuario::getMinDias($t['IdProfesor']);
}

$etiqEstado = [
    'Pendiente'              => 'Pendiente',
    'Aceptada'               => 'Aceptada',
    'Rechazada'              => 'Rechazada',
    'AceptadaConCondiciones' => 'Aceptada con condiciones',
];
?>
<h2>Mis Tickets</h2>

<p>Tickets disponibles este mes: <strong><?= $disponibles ?> / 3</strong></p>

<?php if (!empty($misTickets)): ?>
    <h3>Profesores desbloqueados este mes</h3>
    <ul>
        <?php foreach ($misTickets as $t): ?>
            <li>
                <strong><?= htmlspecialchars($t['Profesor']) ?></strong>
                — usado el <?= htmlspecialchars($t['FechaUso']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- USAR TICKET -->
<?php if ($disponibles > 0 && !empty($profesores)): ?>
    <h3>Usar un ticket</h3>
    <form action="index.php" method="POST">
        <input type="hidden" name="action" value="usarTicket">
        <label for="id_profesor">Selecciona un profesor:</label><br>
        <select id="id_profesor" name="id_profesor" required>
            <option value="">— Elige un profesor —</option>
            <?php foreach ($profesores as $p): ?>
                <?php if (!in_array($p['IdUsuario'], $desbloqueados)): ?>
                    <option value="<?= $p['IdUsuario'] ?>">
                        <?= htmlspecialchars($p['NombreUsuario']) ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select><br><br>
        <input type="submit" value="Usar ticket">
    </form>
<?php elseif ($disponibles <= 0): ?>
    <p><em>Ya usaste tus 3 tickets este mes. Se renuevan el próximo mes.</em></p>
<?php endif; ?>

<hr>

<!-- SOLICITAR CLASE VIRTUAL -->
<?php if (!empty($desbloqueados)): ?>
    <h3>Solicitar clase virtual</h3>
    <p>Solo puedes solicitar clase a los profesores con los que tienes ticket este mes.</p>

    <form action="index.php" method="POST" id="form-solicitud">
        <input type="hidden" name="action" value="solicitarClase">

        <label for="prof_clase">Profesor:</label><br>
        <select id="prof_clase" name="id_profesor" required>
            <option value="" data-dias="1">— Elige un profesor —</option>
            <?php foreach ($misTickets as $t): ?>
                <?php $dias = $diasPorProfesor[$t['IdProfesor']] ?? 1; ?>
                <option value="<?= $t['IdProfesor'] ?>" data-dias="<?= $dias ?>">
                    <?= htmlspecialchars($t['Profesor']) ?>
                    (requiere <?= $dias ?> día<?= $dias !== 1 ? 's' : '' ?> de anticipación)
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <p id="aviso_dias" style="color:#856404; background:#fff3cd;
           padding:6px 10px; border-radius:4px; display:none;"></p>

        <label for="fecha_propuesta">Fecha y hora propuesta:</label><br>
        <input type="datetime-local" id="fecha_propuesta" name="fecha_propuesta"
               min="<?= date('Y-m-d\TH:i') ?>" required><br><br>

        <input type="submit" value="Enviar solicitud">
    </form>

    <script>
    (function () {
        const sel  = document.getElementById('prof_clase');
        const inp  = document.getElementById('fecha_propuesta');
        const aviso = document.getElementById('aviso_dias');

        function actualizarMin() {
            const opt  = sel.options[sel.selectedIndex];
            const dias = parseInt(opt.dataset.dias || 1, 10);

            if (!opt.value) {
                inp.min = new Date().toISOString().slice(0, 16);
                aviso.style.display = 'none';
                return;
            }

            const min = new Date();
            min.setDate(min.getDate() + dias);
            inp.min = min.toISOString().slice(0, 16);

            aviso.textContent = 'Este profesor requiere al menos ' + dias
                + ' día' + (dias !== 1 ? 's' : '') + ' de anticipación. '
                + 'Fecha mínima: ' + min.toLocaleDateString('es-ES',
                  { day: '2-digit', month: '2-digit', year: 'numeric' }) + '.';
            aviso.style.display = 'block';

            if (inp.value && new Date(inp.value) < min) { inp.value = ''; }
        }

        sel.addEventListener('change', actualizarMin);
    })();
    </script>
<?php endif; ?>

<hr>

<!-- MIS SOLICITUDES ENVIADAS -->
<h3>Mis solicitudes de clase</h3>
<?php if (empty($solicitudes)): ?>
    <p>No has enviado solicitudes aún.</p>
<?php else: ?>
    <?php foreach ($solicitudes as $s): ?>
        <div style="border:1px solid #ddd; padding:10px; margin-bottom:8px; border-radius:4px;">
            <strong><?= htmlspecialchars($s['Profesor']) ?></strong> —
            Fecha propuesta: <?= htmlspecialchars($s['FechaPropuesta']) ?> —
            Estado: <strong><?= htmlspecialchars($etiqEstado[$s['Estado']] ?? $s['Estado']) ?></strong>
            <?php if ($s['RespuestaProfesor']): ?>
                <br><em>Respuesta: <?= htmlspecialchars($s['RespuestaProfesor']) ?></em>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
