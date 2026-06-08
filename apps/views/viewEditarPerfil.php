<?php
// Vista: editar el propio perfil
require_once __DIR__ . '/../models/modelPerfil.php';

$idUsuario = (int)$_SESSION['usuario_id'];
$perfil    = Perfil::getByUsuario($idUsuario);
$foto      = $perfil['FotoPerfil'] ? htmlspecialchars($perfil['FotoPerfil']) : null;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

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

            <!-- Hidden file input — triggered by button below -->
            <input type="file" id="fotoInput" name="foto" accept="image/png,image/jpeg"
                   style="display:none;">

            <button type="button" class="btn btn-primary mt-2" style="width:100%;"
                    onclick="document.getElementById('fotoInput').click()">
                Cambiar foto
            </button>

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

<!-- ══ MODAL DE RECORTE ══════════════════════════════════════════ -->
<div id="cropModal" class="crop-modal" style="display:none;" role="dialog" aria-modal="true" aria-label="Recortar foto">
    <div class="crop-modal-box">
        <h3 style="margin-bottom:1rem;">Recortar foto de perfil</h3>
        <p class="text-muted" style="font-size:.85rem; margin-bottom:.75rem;">
            Arrastra para mover · Usa la rueda del ratón para ampliar/reducir
        </p>
        <div class="crop-preview">
            <img id="cropImage" src="" alt="Vista previa para recorte">
        </div>
        <div class="crop-actions">
            <button type="button" id="cropCancel" class="btn btn-secondary">Cancelar</button>
            <button type="button" id="cropSave"   class="btn btn-primary">Recortar y subir</button>
        </div>
        <p id="cropStatus" style="font-size:.82rem; margin-top:.5rem; color:var(--text-light);"></p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
(function () {
    const fileInput  = document.getElementById('fotoInput');
    const modal      = document.getElementById('cropModal');
    const cropImg    = document.getElementById('cropImage');
    const btnCancel  = document.getElementById('cropCancel');
    const btnSave    = document.getElementById('cropSave');
    const statusEl   = document.getElementById('cropStatus');
    let   cropper    = null;

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no puede superar los 2 MB.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            cropImg.src = e.target.result;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            if (cropper) { cropper.destroy(); cropper = null; }
            cropper = new Cropper(cropImg, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.9,
                restore: false,
                guides: false,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        };
        reader.readAsDataURL(file);
        this.value = '';
    });

    btnCancel.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    btnSave.addEventListener('click', function (e) {
        e.stopImmediatePropagation();
        if (!cropper) return;

        btnSave.disabled = true;
        statusEl.textContent = 'Subiendo…';

        const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
        canvas.toBlob(function (blob) {
            const fd = new FormData();
            fd.append('action', 'subirFotoPerfil');
            fd.append('foto', blob, 'perfil.jpg');

            fetch('index.php', { method: 'POST', body: fd })
                .then(function (resp) {
                    if (resp.redirected || resp.ok) {
                        statusEl.textContent = '¡Foto actualizada!';
                        setTimeout(function () {
                            window.location.href = 'index.php?page=viewEditarPerfil';
                        }, 700);
                    } else {
                        statusEl.textContent = 'Error al subir. Intenta de nuevo.';
                        btnSave.disabled = false;
                    }
                })
                .catch(function () {
                    statusEl.textContent = 'Error de red. Intenta de nuevo.';
                    btnSave.disabled = false;
                });
        }, 'image/jpeg', 0.9);
    });

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (cropper) { cropper.destroy(); cropper = null; }
        statusEl.textContent = '';
        btnSave.disabled = false;
    }
})();
</script>
