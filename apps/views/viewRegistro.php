<?php
// Vista parcial — formulario de registro con los 3 tipos de cuenta
?>
<h2>Crear cuenta</h2>

<form action="index.php" method="POST">
    <input type="hidden" name="action" value="registrar">

    <label for="nombre">Nombre de usuario <small>(3-16 caracteres)</small>:</label>
    <input type="text" id="nombre" name="nombre" maxlength="16" required autocomplete="username"><br><br>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" maxlength="64" required autocomplete="email"><br><br>

    <label for="pass">Contraseña <small>(mínimo 8 caracteres)</small>:</label>
    <input type="password" id="pass" name="pass" minlength="8" required autocomplete="new-password"><br><br>

    <label>Tipo de cuenta:</label><br>
    <input type="radio" id="cat1" name="cat" value="1" required>
    <label for="cat1">Estudiante Gratis — solo acceso a videos públicos</label><br>
    <input type="radio" id="cat2" name="cat" value="2">
    <label for="cat2">Alumno Suscriptor — 3 tickets mensuales + clases virtuales</label><br>
    <input type="radio" id="cat3" name="cat" value="3">
    <label for="cat3">Profesor / Creador de contenido</label><br><br>

    <input type="checkbox" id="terms" name="terms" required>
    <label for="terms">Acepto los <a href="#">Términos y condiciones</a></label><br><br>

    <input type="submit" value="Registrarse">
    <input type="reset"  value="Limpiar">
</form>

<p>¿Ya tienes cuenta? <a href="index.php?page=viewLogin">Inicia sesión aquí</a></p>
