<?php
// Vista: listado de todos los videos publicados + buscador
require_once __DIR__ . '/../models/modelVideo.php';

$tipoUsuario = $_SESSION['usuario_tipo'] ?? null;
$idUsuario   = (int)($_SESSION['usuario_id'] ?? 0);

$q         = trim($_GET['q']         ?? '');
$autor     = trim($_GET['autor']     ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$tipo      = trim($_GET['tipo']      ?? '');
$hasSearch = ($q !== '' || $autor !== '' || $categoria !== '' || $tipo !== '');

$videos = $hasSearch
    ? Video::buscar($q, $autor, $categoria, $tipo)
    : Video::getListaVisible();

$categoriasVideo = ['Matemáticas','Física','Geometría','Química','Biología','Historia','Lenguaje','Tecnología','Otros'];
?>

<div class="page-header page-header-row">
    <div>
        <h2>Videos</h2>
        <p>Contenido publicado por los profesores de la plataforma.</p>
    </div>
</div>

<!-- ── BUSCADOR ─────────────────────────────────────────────── -->
<form class="search-bar" method="GET" action="index.php">
    <input type="hidden" name="page" value="viewVideos">

    <input type="text" name="q" placeholder="Buscar por título o descripción…"
           value="<?= htmlspecialchars($q) ?>">

    <input type="text" name="autor" placeholder="Autor…"
           value="<?= htmlspecialchars($autor) ?>">

    <select name="categoria">
        <option value="">Todas las categorías</option>
        <?php foreach ($categoriasVideo as $cat): ?>
            <option value="<?= $cat ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                <?= $cat ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="tipo">
        <option value="">Todo el contenido</option>
        <option value="Publico"      <?= $tipo === 'Publico'      ? 'selected' : '' ?>>Solo públicos</option>
        <option value="Suscriptores" <?= $tipo === 'Suscriptores' ? 'selected' : '' ?>>Solo suscriptores</option>
    </select>

    <div style="display:flex; gap:.5rem;">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <?php if ($hasSearch): ?>
            <a href="index.php?page=viewVideos" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($hasSearch): ?>
    <p class="text-muted mb-2"><?= count($videos) ?> resultado(s)</p>
<?php endif; ?>

<?php if (empty($videos)): ?>
    <div class="empty-state">
        <p><?= $hasSearch ? 'No se encontraron videos con esos filtros.' : 'No hay videos disponibles por ahora.' ?></p>
        <?php if ($tipoUsuario === 'Creador'): ?>
            <a href="index.php?page=viewSubirVideo" class="btn btn-primary">Subir el primer video</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($videos as $v): ?>
            <div class="card video-card">
                <?php if (!empty($v['Miniatura'])): ?>
                    <img src="<?= htmlspecialchars($v['Miniatura']) ?>"
                         alt="" class="video-card-thumb">
                <?php endif; ?>
                <?php if ($v['Privacidad'] === 'Suscriptores'): ?>
                    <div class="video-access-badge">
                        <span class="badge badge-warn">Solo suscriptores</span>
                    </div>
                <?php endif; ?>

                <div class="card-title">
                    <a href="index.php?page=viewVideo&id=<?= $v['IdVideo'] ?>">
                        <?= htmlspecialchars($v['Titulo']) ?>
                    </a>
                </div>
                <div class="card-meta">
                    <span><?= htmlspecialchars($v['Profesor']) ?></span>
                    <?php if (!empty($v['Categoria']) && $v['Categoria'] !== 'Otros'): ?>
                        <span class="sep">·</span>
                        <span class="badge badge-muted"><?= htmlspecialchars($v['Categoria']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($v['Descripcion']): ?>
                    <div class="card-body">
                        <?= htmlspecialchars(mb_substr($v['Descripcion'], 0, 120)) ?>…
                    </div>
                <?php endif; ?>
                <div class="card-footer">
                    <a href="index.php?page=viewVideo&id=<?= $v['IdVideo'] ?>" class="btn btn-ghost btn-sm">
                        <?= $v['Privacidad'] === 'Suscriptores' ? '🔒 Ver video' : 'Ver video →' ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
