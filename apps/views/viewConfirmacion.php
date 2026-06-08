<?php
// Vista parcial — confirmación post-registro
$nombre = $_SESSION['nuevo_nombre'] ?? 'Usuario';
unset($_SESSION['nuevo_nombre']);
?>

<div class="hero" style="padding:3rem 1rem 2rem;">
    <p class="hero-eyebrow">Registro completado</p>
    <h2>¡Bienvenido/a, <?= htmlspecialchars($nombre) ?>!</h2>
    <p>Tu cuenta ha sido creada con éxito. Ya puedes iniciar sesión y explorar la plataforma.</p>
    <div class="hero-actions">
        <a href="index.php?page=viewLogin"  class="btn btn-primary btn-lg">Iniciar sesión →</a>
        <a href="index.php?page=viewVideos" class="btn btn-secondary btn-lg">Ver videos públicos</a>
    </div>
</div>
