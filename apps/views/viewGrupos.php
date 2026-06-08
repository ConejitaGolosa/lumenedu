<?php
// Vista: lista de grupos + crear grupo
require_once __DIR__ . '/../models/modelGrupo.php';
require_once __DIR__ . '/../models/modelPerfil.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?page=viewLogin');
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$grupos    = Grupo::getMisGrupos($idUsuario);
$usuarios  = Perfil::getUsuariosActivos($idUsuario);
?>

<div class="page-header-row">
    <div class="page-header" style="margin-bottom:0; border:none; padding:0;">
        <h2>Grupos</h2>
        <p>Chats grupales con otros miembros.</p>
    </div>
</div>

<!-- ── CREAR GRUPO ───────────────────────────────────────────── -->
<div class="create-panel mb-3">
    <button class="create-panel-toggle" id="crearGrupoToggle" type="button">
        <span>+ Crear nuevo grupo</span>
        <span class="chevron">▾</span>
    </button>
    <div class="create-panel-body" id="crearGrupoBody">
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="crearGrupo">

            <div class="form-group">
                <label for="nombre_grupo">Nombre del grupo</label>
                <input type="text" id="nombre_grupo" name="nombre" required
                       maxlength="64" placeholder="Ej: Estudio de Cálculo">
            </div>

            <div class="form-group">
                <label>Agregar miembros</label>
                <div class="members-picker">
                    <?php foreach ($usuarios as $u): ?>
                        <label class="check-item">
                            <input type="checkbox" name="miembros[]" value="<?= $u['IdUsuario'] ?>">
                            <span>
                                <?= htmlspecialchars($u['NombreUsuario']) ?>
                                <?= rolBadge($u['TipoUsuario']) ?>
                                <small class="text-muted">&nbsp;<?= htmlspecialchars($u['TipoUsuario']) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Crear grupo</button>
        </form>
    </div>
</div>

<!-- ── LISTA DE GRUPOS ───────────────────────────────────────── -->
<?php if (empty($grupos)): ?>
    <div class="empty-state">
        <p>Aún no perteneces a ningún grupo.</p>
        <p>Crea uno o pide a alguien que te añada.</p>
    </div>
<?php else: ?>
    <div class="grid-list">
        <?php foreach ($grupos as $g): ?>
            <a href="index.php?page=viewGrupo&id=<?= $g['IdGrupo'] ?>" class="card grupo-card">
                <div class="grupo-icon">G</div>
                <div style="flex:1; min-width:0;">
                    <div class="card-title"><?= htmlspecialchars($g['Nombre']) ?></div>
                    <div class="card-meta">
                        <span><?= $g['TotalMiembros'] ?> miembro<?= $g['TotalMiembros'] != 1 ? 's' : '' ?></span>
                        <?php if ($g['UltimoMensaje']): ?>
                            <span>·</span>
                            <span>Último mensaje: <?= htmlspecialchars(substr($g['UltimoMensaje'], 0, 16)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="btn btn-ghost btn-sm">Abrir →</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
