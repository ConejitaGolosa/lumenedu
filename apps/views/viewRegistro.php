<?php
// Vista parcial — formulario de registro con los 3 tipos de cuenta
?>

<div class="form-card" style="max-width:520px;">
    <h2>Crear cuenta</h2>

    <form action="index.php" method="POST">
        <input type="hidden" name="action" value="registrar">

        <div class="form-group">
            <label for="nombre">Nombre de usuario <small>(3–16 caracteres)</small></label>
            <input type="text" id="nombre" name="nombre" maxlength="16" required
                   autocomplete="username" placeholder="mi_nombre">
        </div>

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" maxlength="64" required
                   autocomplete="email" placeholder="tu@correo.com">
        </div>

        <div class="form-group">
            <label for="pass">Contraseña <small>(mínimo 8 caracteres)</small></label>
            <input type="password" id="pass" name="pass" minlength="8" required
                   autocomplete="new-password" placeholder="••••••••">
        </div>

        <div class="form-group">
            <label>Tipo de cuenta</label>
            <div class="radio-group">
                <label class="radio-item">
                    <input type="radio" name="cat" value="1" required>
                    <span>
                        <strong>Estudiante gratis</strong>
                        Solo acceso a videos públicos
                    </span>
                </label>
                <label class="radio-item">
                    <input type="radio" name="cat" value="2">
                    <span>
                        <strong>Alumno suscriptor</strong>
                        3 tickets mensuales + clases virtuales
                    </span>
                </label>
                <label class="radio-item">
                    <input type="radio" name="cat" value="3">
                    <span>
                        <strong>Profesor / Creador de contenido</strong>
                        Publica videos y da clases
                    </span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="check-item">
                <input type="checkbox" name="terms" required>
                <span>Acepto los <a href="#">Términos y condiciones</a></span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Crear cuenta</button>
            <button type="reset"  class="btn btn-secondary">Limpiar</button>
        </div>
    </form>

    <p class="form-footer">
        ¿Ya tienes cuenta? <a href="index.php?page=viewLogin">Inicia sesión aquí</a>
    </p>
</div>
