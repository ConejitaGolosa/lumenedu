<?php
// Vista parcial — se incluye dentro del <main> de index.php.
// Se muestra después de un registro exitoso.

// Recupera el nombre guardado por el controlador y lo limpia de la sesión
// para que no reaparezca si el usuario recarga la página.
$nombre = $_SESSION['nuevo_nombre'] ?? 'Usuario';
unset($_SESSION['nuevo_nombre']);
?>

<h2>¡Registro exitoso!</h2>

<p>Bienvenido/a, <strong><?= htmlspecialchars($nombre) ?></strong>. Tu cuenta ha sido creada con éxito.</p>
<p>Ya puedes iniciar sesión y explorar la plataforma.</p>

<a href="index.php?page=viewLogin">Iniciar sesión &rarr;</a>
