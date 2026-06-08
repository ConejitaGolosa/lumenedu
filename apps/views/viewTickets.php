<?php
// Vista: gestión de tickets y solicitudes de clase (solo Suscriptores)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Suscriptor') {
    echo '<div class="alert alert-warn">Esta sección es exclusiva para alumnos suscritos.</div>';
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
$profesores    = Video::getTodosProfesores();
$solicitudes   = SolicitudClase::getDeEstudiante($idUsuario);

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
$badgeSolicitud = [
    'Pendiente'              => 'badge-warn',
    'Aceptada'               => 'badge-ok',
    'Rechazada'              => 'badge-error',
    'AceptadaConCondiciones' => 'badge-gold',
];
?>

<div class="page-header">
    <h2>Mis Tickets</h2>
    <p>Gestiona tus tickets mensuales y solicitudes de clase virtual.</p>
</div>

<!-- Contador de tickets -->
<div class="ticket-counter">
    <div>
        <div class="count"><?= $disponibles ?> / 3</div>
        <div class="label">Tickets disponibles este mes</div>
    </div>
    <?php if ($disponibles <= 0): ?>
        <span class="badge badge-warn">Se renuevan el próximo mes</span>
    <?php endif; ?>
</div>

<!-- ── PROFESORES DESBLOQUEADOS ──────────────────────────────── -->
<?php if (!empty($misTickets)): ?>
    <div class="section">
        <h3 class="section-title">Profesores desbloqueados este mes</h3>
        <div class="grid-list">
            <?php foreach ($misTickets as $t): ?>
                <div class="card" style="display:flex; align-items:center; justify-content:space-between; padding:.85rem 1.1rem;">
                    <strong><?= htmlspecialchars($t['Profesor']) ?></strong>
                    <small>Usado el <?= htmlspecialchars($t['FechaUso']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ── USAR TICKET ───────────────────────────────────────────── -->
<?php if ($disponibles > 0 && !empty($profesores)): ?>
    <div class="section">
        <h3 class="section-title">Usar un ticket</h3>
        <div class="card" style="max-width:420px;">
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="usarTicket">
                <div class="form-group">
                    <label for="id_profesor">Selecciona un profesor</label>
                    <select id="id_profesor" name="id_profesor" required>
                        <option value="">— Elige un profesor —</option>
                        <?php foreach ($profesores as $p): ?>
                            <?php if (!in_array($p['IdUsuario'], $desbloqueados)): ?>
                                <option value="<?= $p['IdUsuario'] ?>">
                                    <?= htmlspecialchars($p['NombreUsuario']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Usar ticket</button>
            </form>
        </div>
    </div>
<?php elseif ($disponibles <= 0): ?>
    <div class="alert alert-warn">Ya usaste tus 3 tickets este mes. Se renuevan el próximo mes.</div>
<?php endif; ?>

<!-- ── SOLICITAR CLASE VIRTUAL ───────────────────────────────── -->
<?php if (!empty($desbloqueados)): ?>
    <div class="section">
        <h3 class="section-title">Solicitar clase virtual</h3>
        <p>Solo puedes solicitar clase a los profesores con ticket activo este mes.</p>

        <div class="card" style="max-width:480px;">
            <form action="index.php" method="POST" id="form-solicitud">
                <input type="hidden" name="action" value="solicitarClase">

                <div class="form-group">
                    <label for="prof_clase">Profesor</label>
                    <select id="prof_clase" name="id_profesor" required>
                        <option value="" data-dias="1">— Elige un profesor —</option>
                        <?php foreach ($misTickets as $t): ?>
                            <?php $dias = $diasPorProfesor[$t['IdProfesor']] ?? 1; ?>
                            <option value="<?= $t['IdProfesor'] ?>" data-dias="<?= $dias ?>">
                                <?= htmlspecialchars($t['Profesor']) ?>
                                (<?= $dias ?> día<?= $dias !== 1 ? 's' : '' ?> de anticipación)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="aviso_dias" class="alert alert-warn mb-2" style="display:none;"></div>

                <div class="form-group">
                    <label for="fecha_propuesta">Fecha y hora propuesta</label>
                    <input type="datetime-local" id="fecha_propuesta" name="fecha_propuesta"
                           min="<?= date('Y-m-d\TH:i') ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Enviar solicitud</button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const sel   = document.getElementById('prof_clase');
        const inp   = document.getElementById('fecha_propuesta');
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

<!-- ── MIS SOLICITUDES ENVIADAS ──────────────────────────────── -->
<div class="section">
    <h3 class="section-title">Mis solicitudes de clase</h3>

    <?php if (empty($solicitudes)): ?>
        <div class="empty-state" style="padding:1.5rem;">
            <p>No has enviado solicitudes aún.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Profesor</th>
                        <th>Fecha propuesta</th>
                        <th>Estado</th>
                        <th>Respuesta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['Profesor']) ?></strong></td>
                            <td style="white-space:nowrap;"><?= htmlspecialchars($s['FechaPropuesta']) ?></td>
                            <td>
                                <span class="badge <?= $badgeSolicitud[$s['Estado']] ?? 'badge-muted' ?>">
                                    <?= htmlspecialchars($etiqEstado[$s['Estado']] ?? $s['Estado']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($s['RespuestaProfesor']): ?>
                                    <em><?= htmlspecialchars($s['RespuestaProfesor']) ?></em>
                                <?php else: ?>
                                    <span style="color:var(--text-light);">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
