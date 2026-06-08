<?php
// Vista parcial — formulario de login
?>

<div class="form-card">
    <h2>Iniciar sesión</h2>

    <form action="index.php" method="POST">
        <input type="hidden" name="action" value="login">

        <div class="form-group">
            <label for="email">Usuario o correo electrónico</label>
            <input type="text" id="email" name="email" required autocomplete="username"
                   placeholder="tu@correo.com o nombre_usuario">
        </div>

        <div class="form-group">
            <label for="pass">Contraseña</label>
            <input type="password" id="pass" name="pass" required autocomplete="current-password"
                   placeholder="••••••••">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Entrar</button>
            <button type="reset"  class="btn btn-secondary">Limpiar</button>
        </div>
    </form>

    <p class="form-footer">
        ¿No tienes cuenta? <a href="index.php?page=viewRegistro">Regístrate aquí</a>
    </p>
</div>
