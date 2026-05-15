<?php
// Vista: feed principal de foros comunitarios (accesible a todos)
require_once __DIR__ . '/../models/modelForo.php';

$foros     = Foro::getLista(100);
$idUsuario = $_SESSION['usuario_id'] ?? null;
$categorias = ['General', 'Matemáticas', 'Ciencias', 'Historia', 'Lenguaje', 'Tecnología', 'Arte', 'Otros'];
?>

<h2>Foros de la comunidad</h2>
<p>Debates y preguntas sobre materias, técnicas de estudio y más. Participan alumnos y profesores.</p>

<?php if ($idUsuario): ?>
    <details style="margin-bottom:20px; border:1px solid #ccc; padding:12px; border-radius:4px;">
        <summary style="cursor:pointer; font-weight:bold;">+ Crear nuevo hilo</summary>
        <form action="index.php" method="POST" style="margin-top:12px;">
            <input type="hidden" name="action" value="crearForo">

            <label for="titulo_foro">Título <small>(máx. 128 caracteres)</small>:</label><br>
            <input type="text" id="titulo_foro" name="titulo" maxlength="128" required
                   style="width:100%; max-width:500px;"
                   placeholder="¿Sobre qué quieres hablar?"><br><br>

            <label for="cat_foro">Categoría:</label><br>
            <select id="cat_foro" name="categoria">
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat ?>"><?= $cat ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="contenido_foro">Contenido:</label><br>
            <textarea id="contenido_foro" name="contenido" rows="5" cols="60" maxlength="5000"
                      placeholder="Escribe tu pregunta o tema..." required></textarea><br><br>

            <input type="submit" value="Publicar hilo">
        </form>
    </details>
<?php else: ?>
    <p><a href="index.php?page=viewLogin">Inicia sesión</a> para crear un hilo.</p>
<?php endif; ?>

<?php if (empty($foros)): ?>
    <p>Aún no hay hilos. ¡Sé el primero en crear uno!</p>
<?php else: ?>
    <?php foreach ($foros as $f): ?>
        <div style="border:1px solid #ddd; padding:12px; margin-bottom:10px; border-radius:4px;">
            <a href="index.php?page=viewForo&id=<?= $f['IdForo'] ?>"
               style="font-size:1.05em; font-weight:bold; text-decoration:none;">
                <?= htmlspecialchars($f['Titulo']) ?>
            </a>
            <span style="background:#e9ecef; padding:2px 8px; border-radius:10px;
                         font-size:0.8em; margin-left:8px;">
                <?= htmlspecialchars($f['Categoria']) ?>
            </span>
            <br>
            <small style="color:#666;">
                Por <strong><?= htmlspecialchars($f['Autor']) ?></strong>
                (<?= htmlspecialchars($f['TipoUsuario']) ?>) —
                <?= htmlspecialchars($f['FechaPublicacion']) ?> —
                <?= (int)$f['TotalComentarios'] ?> respuesta(s)
            </small>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
