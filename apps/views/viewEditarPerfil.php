<?php
// Vista: editar el propio perfil
require_once __DIR__ . '/../models/modelPerfil.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?page=viewLogin');
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$perfil    = Perfil::getByUsuario($idUsuario);
$foto      = $perfil['FotoPerfil'] ? htmlspecialchars($perfil['FotoPerfil']) : null;
?>

<div class="page-header">
    <h2>Editar perfil</h2>
    <p>Administra tu cuenta y personalización.</p>
</div>

<div class="edit-profile-grid">

    <!-- ══ COLUMNA IZQUIERDA: foto ══════════════════════════════ -->
    <div class="edit-col-photo">
        <div class="card">
            <h3 style="margin-bottom:1.25rem;">Foto de perfil</h3>

            <div class="photo-preview-wrap">
                <?php if ($foto): ?>
                    <img src="<?= $foto ?>" alt="Foto actual" class="photo-preview" id="photoPreview">
                <?php else: ?>
                    <div class="photo-preview photo-default" id="photoPreview">
                        <?= mb_strtoupper(mb_substr($_SESSION['usuario_nombre'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <form action="index.php" method="POST" enctype="multipart/form-data" class="mt-2">
                <input type="hidden" name="action" value="subirFotoPerfil">
                <div class="form-group">
                    <label for="foto">Nueva foto (PNG o JPG, máx 2 MB)</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Subir foto</button>
            </form>

            <?php if ($foto): ?>
                <form action="index.php" method="POST" class="mt-1">
                    <input type="hidden" name="action" value="eliminarFotoPerfil">
                    <button type="submit" class="btn btn-secondary" style="width:100%;"
                            onclick="return confirm('¿Eliminar la foto de perfil?')">
                        Eliminar foto
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ COLUMNA DERECHA: formularios ════════════════════════ -->
    <div class="edit-col-forms">

        <!-- Datos de cuenta -->
        <details class="panel" open>
            <summary>Datos de cuenta</summary>
            <div class="panel-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="actualizarCuenta">

                    <div class="form-group">
                        <label for="nombre">Nombre de usuario</label>
                        <input type="text" id="nombre" name="nombre" required
                               minlength="3" maxlength="16"
                               value="<?= htmlspecialchars($perfil['NombreUsuario'] ?? '') ?>">
                        <span class="form-hint">Entre 3 y 16 caracteres.</span>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" required
                               value="<?= htmlspecialchars($perfil['Correo'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </form>
            </div>
        </details>

        <!-- Cambiar contraseña -->
        <details class="panel">
            <summary>Cambiar contraseña</summary>
            <div class="panel-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="cambiarPassword">

                    <div class="form-group">
                        <label for="pass_actual">Contraseña actual</label>
                        <input type="password" id="pass_actual" name="pass_actual" required
                               autocomplete="current-password" placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label for="pass_nueva">Nueva contraseña</label>
                        <input type="password" id="pass_nueva" name="pass_nueva" required
                               minlength="8" autocomplete="new-password"
                               placeholder="Mínimo 8 caracteres">
                    </div>

                    <div class="form-group">
                        <label for="pass_conf">Confirmar nueva contraseña</label>
                        <input type="password" id="pass_conf" name="pass_conf" required
                               minlength="8" autocomplete="new-password"
                               placeholder="Repite la nueva contraseña">
                    </div>

                    <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                </form>
            </div>
        </details>

        <!-- Personalización del perfil -->
        <details class="panel">
            <summary>Personalización</summary>
            <div class="panel-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="actualizarPerfil">

                    <div class="form-group">
                        <label for="bio">Biografía</label>
                        <textarea id="bio" name="bio" rows="4" maxlength="512"
                                  placeholder="Cuéntanos algo sobre ti…"><?= htmlspecialchars($perfil['Biografia'] ?? '') ?></textarea>
                        <span class="form-hint">Máximo 512 caracteres.</span>
                    </div>

                    <div class="form-group">
                        <label for="enlace">Enlace personal</label>
                        <input type="url" id="enlace" name="enlace"
                               placeholder="https://linkedin.com/in/tu-perfil"
                               value="<?= htmlspecialchars($perfil['EnlacePersonal'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="privacidad">Privacidad del perfil</label>
                        <select id="privacidad" name="privacidad">
                            <option value="Publico"  <?= ($perfil['PreferenciasPrivacidad'] ?? '') === 'Publico'  ? 'selected' : '' ?>>Público — todos pueden ver</option>
                            <option value="Amigos"   <?= ($perfil['PreferenciasPrivacidad'] ?? '') === 'Amigos'   ? 'selected' : '' ?>>Amigos — solo tus amigos</option>
                            <option value="Privado"  <?= ($perfil['PreferenciasPrivacidad'] ?? '') === 'Privado'  ? 'selected' : '' ?>>Privado — solo tú</option>
                        </select>
                        <span class="form-hint">Los moderadores y administradores siempre pueden ver perfiles.</span>
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar personalización</button>
                </form>
            </div>
        </details>

    </div><!-- /.edit-col-forms -->

</div><!-- /.edit-profile-grid -->
