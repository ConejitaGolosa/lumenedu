<?php
// Vista parcial — se incluye dentro del <main> de index.php
?>

<h2>Iniciar sesión</h2>

<form action="index.php" method="POST">
    <!-- El campo hidden indica al controlador qué acción ejecutar -->
    <input type="hidden" name="action" value="login">

    <label for="email">Usuario o Email:</label><br>
    <!-- type="text" para permitir login con NombreUsuario o Correo -->
    <input type="text" id="email" name="email" required autocomplete="username"><br><br>

    <label for="pass">Contraseña:</label><br>
    <input type="password" id="pass" name="pass" required autocomplete="current-password"><br><br>

    <input type="submit" value="Entrar">
    <input type="reset"  value="Limpiar">
</form>

<p>¿No tienes cuenta? <a href="index.php?page=viewRegistro">Regístrate aquí</a></p>
