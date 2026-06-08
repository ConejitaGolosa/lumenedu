<?php
// Vista: feed principal de foros comunitarios
require_once __DIR__ . '/../models/modelForo.php';

$foros      = Foro::getLista(100);
$idUsuario  = $_SESSION['usuario_id'] ?? null;
$categorias = ['General', 'Matemáticas', 'Ciencias', 'Historia', 'Lenguaje', 'Tecnología', 'Arte', 'Otros'];
?>

<div class="page-header page-header-row">
    <div>
        <h2>Foros de la comunidad</h2>
        <p>Debates y preguntas sobre materias, técnicas de estudio y más.</p>
    </div>
</div>

<?php if ($idUsuario): ?>
    <div class="create-panel">
        <button class="create-panel-toggle" type="button">
            <span>+ Crear nuevo hilo</span>
            <span class="chevron">▾</span>
        </button>
        <div class="create-panel-body">
            <form action="index.php" method="POST" class="form-wide">
                <input type="hidden" name="action" value="crearForo">

                <div class="form-group">
                    <label for="titulo_foro">Título <small>(máx. 128 caracteres)</small></label>
                    <input type="text" id="titulo_foro" name="titulo" maxlength="128" required
                           placeholder="¿Sobre qué quieres hablar?">
                </div>

                <div class="form-group">
                    <label for="cat_foro">Categoría</label>
                    <select id="cat_foro" name="categoria">
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contenido_foro">Contenido</label>
                    <textarea id="contenido_foro" name="contenido" rows="5" maxlength="5000"
                              placeholder="Escribe tu pregunta o tema…" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Publicar hilo</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warn mb-3">
        <a href="index.php?page=viewLogin">Inicia sesión</a> para crear un hilo.
    </div>
<?php endif; ?>

<?php if (empty($foros)): ?>
    <div class="empty-state">
        <p>Aún no hay hilos. ¡Sé el primero en crear uno!</p>
    </div>
<?php else: ?>
    <div class="grid-list">
        <?php foreach ($foros as $f): ?>
            <div class="forum-card">
                <a href="index.php?page=viewForo&id=<?= $f['IdForo'] ?>">
                    <span class="forum-title"><?= htmlspecialchars($f['Titulo']) ?></span>
                </a>
                <div class="forum-meta">
                    <span class="badge badge-gold"><?= htmlspecialchars($f['Categoria']) ?></span>
                    &nbsp;·&nbsp;
                    Por <strong><?= htmlspecialchars($f['Autor']) ?></strong>
                    (<?= htmlspecialchars($f['TipoUsuario']) ?>)
                    &nbsp;·&nbsp;
                    <?= htmlspecialchars($f['FechaPublicacion']) ?>
                    &nbsp;·&nbsp;
                    <?= (int)$f['TotalComentarios'] ?> respuesta(s)
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
