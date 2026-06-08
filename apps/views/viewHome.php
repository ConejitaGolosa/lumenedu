<?php
// Vista parcial — dashboard de inicio según tipo de usuario
require_once __DIR__ . '/../models/modelNotificacion.php';
require_once __DIR__ . '/../config/config.php';

$tipo      = $_SESSION['usuario_tipo']   ?? null;
$idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
?>

<?php if (!$tipo): ?>
    <div class="hero">
        <p class="hero-eyebrow">Plataforma educativa</p>
        <h2>Aprende y enseña sin límites</h2>
        <p>Profesores comparten conocimiento. Alumnos aprenden a su ritmo. Una comunidad seria, enfocada en el estudio.</p>
        <div class="hero-actions">
            <a href="index.php?page=viewRegistro" class="btn btn-primary btn-lg">Crear cuenta gratis</a>
            <a href="index.php?page=viewVideos"   class="btn btn-secondary btn-lg">Ver videos públicos</a>
        </div>
        <div class="hero-divider"></div>
    </div>

    <div class="feature-strip">
        <div class="feature-item">
            <div class="feature-icon">🎓</div>
            <h4>Aprende a tu ritmo</h4>
            <p>Videos y materiales organizados por materia, disponibles cuando los necesitas.</p>
        </div>
        <div class="feature-item">
            <div class="feature-icon">💬</div>
            <h4>Foros de comunidad</h4>
            <p>Debates, preguntas y respuestas entre alumnos y profesores.</p>
        </div>
        <div class="feature-item">
            <div class="feature-icon">📅</div>
            <h4>Clases virtuales</h4>
            <p>Solicita sesiones personalizadas directamente con tu profesor.</p>
        </div>
    </div>

<?php elseif ($tipo === 'Administrador'): ?>
    <div class="dashboard-greeting">
        <h2>Panel de administración</h2>
        <p>Bienvenido. Desde aquí gestionas la plataforma completa.</p>
    </div>
    <div class="dashboard-grid">
        <a href="index.php?page=viewAdminPanel"     class="dashboard-link">Revisar videos<small>Videos pendientes de aprobación</small></a>
        <a href="index.php?page=viewNotificaciones" class="dashboard-link">Notificaciones<small>Alertas y novedades</small></a>
    </div>

<?php elseif ($tipo === 'Moderador'): ?>
    <div class="dashboard-greeting">
        <h2>Panel de moderación</h2>
        <p>Bienvenido. Revisa el contenido pendiente de la plataforma.</p>
    </div>
    <div class="dashboard-grid">
        <a href="index.php?page=viewAdminPanel"     class="dashboard-link">Moderación<small>Videos pendientes de revisión</small></a>
        <a href="index.php?page=viewNotificaciones" class="dashboard-link">Notificaciones<small>Alertas y novedades</small></a>
    </div>

<?php elseif ($tipo === 'Creador'): ?>
    <div class="dashboard-greeting">
        <h2>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></h2>
        <p>Panel del profesor. Gestiona tu contenido y atiende a tus alumnos.</p>
    </div>
    <div class="dashboard-grid">
        <a href="index.php?page=viewSubirVideo"     class="dashboard-link">Subir video<small>Nuevo contenido para tus alumnos</small></a>
        <a href="index.php?page=viewMisVideos"      class="dashboard-link">Mis videos<small>Gestiona tu biblioteca</small></a>
        <a href="index.php?page=viewSolicitudes"    class="dashboard-link">Solicitudes<small>Peticiones de clase recibidas</small></a>
        <a href="index.php?page=viewVideos"         class="dashboard-link">Explorar<small>Videos de la plataforma</small></a>
        <a href="index.php?page=viewForos"          class="dashboard-link">Foros<small>Participa en la comunidad</small></a>
        <a href="index.php?page=viewNotificaciones" class="dashboard-link">Notificaciones<small>Alertas y respuestas</small></a>
    </div>

<?php elseif ($tipo === 'Suscriptor'): ?>
    <div class="dashboard-greeting">
        <h2>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></h2>
        <p>Panel del alumno. Accede a tu contenido y gestiona tus clases.</p>
    </div>
    <div class="dashboard-grid">
        <a href="index.php?page=viewVideos"         class="dashboard-link">Ver videos<small>Todo el contenido disponible</small></a>
        <a href="index.php?page=viewTickets"        class="dashboard-link">Mis tickets<small>Clases y suscripciones</small></a>
        <a href="index.php?page=viewForos"          class="dashboard-link">Foros<small>Comunidad de estudio</small></a>
        <a href="index.php?page=viewNotificaciones" class="dashboard-link">Notificaciones<small>Alertas y respuestas</small></a>
    </div>

<?php elseif ($tipo === 'EstudianteGratis'): ?>
    <div class="dashboard-greeting">
        <h2>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></h2>
        <p>Bienvenido a LumenEdu. Estás explorando con una cuenta gratuita.</p>
    </div>
    <div class="dashboard-grid">
        <a href="index.php?page=viewVideos"         class="dashboard-link">Videos públicos<small>Contenido abierto a todos</small></a>
        <a href="index.php?page=viewForos"          class="dashboard-link">Foros<small>Comunidad de estudio</small></a>
        <a href="index.php?page=viewNotificaciones" class="dashboard-link">Notificaciones<small>Alertas y novedades</small></a>
    </div>
    <div class="alert alert-warn mt-3">
        Con una suscripción accedes a más contenido, clases virtuales y 3 tickets mensuales.
        <a href="index.php?page=viewSuscribirse" class="btn btn-primary btn-sm" style="margin-left:.75rem;">Suscribirse — USD <?= SUSCRIPCION_MONTO ?>/mes</a>
    </div>
<?php endif; ?>
