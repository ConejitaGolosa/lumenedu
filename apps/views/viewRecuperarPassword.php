<?php
// Vista: recuperación de contraseña en dos pasos
$step    = (int)($_GET['step'] ?? 1);
$devCode = $_SESSION['reset_codigo_dev'] ?? null;
?>

<div class="form-card">

<?php if ($step === 2): ?>

    <h2>Restablecer contraseña</h2>
    <p class="form-hint" style="margin-bottom:1.25rem;">
        Ingresa el código que enviamos a tu correo y tu nueva contraseña.
    </p>

    <?php if ($devCode): ?>
        <div class="alert alert-warn" style="font-family:monospace; font-size:.9rem;">
            <strong>Modo desarrollo:</strong> código = <strong><?= htmlspecialchars($devCode) ?></strong>
        </div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <input type="hidden" name="action" value="resetPassword">

        <div class="form-group">
            <label for="codigo">Código de verificación</label>
            <input type="text" id="codigo" name="codigo" required
                   maxlength="8" autocomplete="one-time-code"
                   placeholder="ABC123" style="text-transform:uppercase; letter-spacing:.2em;">
        </div>

        <div class="form-group">
            <label for="pass_nueva">Nueva contraseña</label>
            <input type="password" id="pass_nueva" name="pass_nueva" required
                   minlength="8" autocomplete="new-password"
                   placeholder="Mínimo 8 caracteres">
        </div>

        <div class="form-group">
            <label for="pass_conf">Confirmar contraseña</label>
            <input type="password" id="pass_conf" name="pass_conf" required
                   minlength="8" autocomplete="new-password"
                   placeholder="Repite la contraseña">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
            <a href="index.php?page=viewRecuperarPassword" class="btn btn-secondary">Volver</a>
        </div>
    </form>

<?php else: ?>

    <h2>Recuperar contraseña</h2>
    <p class="form-hint" style="margin-bottom:1.25rem;">
        Ingresa tu correo registrado y te enviaremos un código de verificación.
    </p>

    <form action="index.php" method="POST">
        <input type="hidden" name="action" value="solicitarCodigo">

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required
                   autocomplete="email" placeholder="tu@correo.com">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enviar código</button>
            <a href="index.php?page=viewLogin" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

<?php endif; ?>

    <p class="form-footer">
        <a href="index.php?page=viewLogin">← Volver al inicio de sesión</a>
    </p>
</div>
