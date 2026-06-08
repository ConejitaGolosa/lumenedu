<?php
// Vista: página de suscripción con pago PayPal Sandbox.
// Accesible para EstudianteGratis (upgrade) y Suscriptores recién registrados.

if (!isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-warn">Debes <a href="index.php?page=viewLogin">iniciar sesión</a> para suscribirte.</div>';
    return;
}

// Bloquear acceso a roles que no deben pagar
$tipo = $_SESSION['usuario_tipo'] ?? '';
if (in_array($tipo, ['Creador', 'Administrador', 'Moderador'])) {
    echo '<div class="alert alert-warn">Esta página es solo para alumnos.</div>';
    return;
}

require_once __DIR__ . '/../config/config.php';

$credencialesOk = PAYPAL_CLIENT_ID !== 'TU_CLIENT_ID_SANDBOX_AQUI';
$yaEsSuscriptor = $tipo === 'Suscriptor';
?>

<div class="subs-page">

    <div class="page-header text-center" style="border:none; padding-bottom:.5rem;">
        <p class="hero-eyebrow">Suscripción mensual</p>
        <h2>Acceso completo a LumenEdu</h2>
        <p>Desbloquea videos exclusivos, clases virtuales y contenido premium de todos los profesores.</p>
    </div>

    <!-- Tarjeta del plan -->
    <div class="plan-card">
        <div class="plan-header">
            <span class="plan-badge">Plan Suscriptor</span>
            <div class="plan-price">
                <span class="plan-currency"><?= SUSCRIPCION_MONEDA ?></span>
                <span class="plan-amount"><?= SUSCRIPCION_MONTO ?></span>
                <span class="plan-period">/ mes</span>
            </div>
        </div>

        <ul class="plan-features">
            <li><span class="plan-check">✓</span> 3 tickets mensuales para acceder a profesores</li>
            <li><span class="plan-check">✓</span> Videos exclusivos para suscriptores</li>
            <li><span class="plan-check">✓</span> Solicitudes de clase virtual personalizada</li>
            <li><span class="plan-check">✓</span> Participación en foros de la comunidad</li>
            <li><span class="plan-check">✓</span> Notificaciones y seguimiento de tus clases</li>
        </ul>

        <div class="plan-divider"></div>

        <?php if ($yaEsSuscriptor): ?>
            <!-- El usuario ya es Suscriptor pero quizás viene del registro -->
            <div id="pago-pendiente-info">
                <p style="text-align:center; color:var(--text-muted); margin-bottom:1rem;">
                    Completa el pago para activar oficialmente tu suscripción.
                </p>
            </div>
        <?php endif; ?>

        <!-- Zona del botón PayPal -->
        <div id="paypal-zone">
            <?php if (!$credencialesOk): ?>
                <div class="alert alert-warn" style="text-align:center;">
                    <strong>PayPal Sandbox no configurado.</strong><br>
                    Agrega tu Client ID y Secret en <code>apps/config/config.php</code>.
                    <br><br>
                    <a href="https://developer.paypal.com" target="_blank" rel="noopener"
                       class="btn btn-secondary btn-sm">Ir a PayPal Developer →</a>
                </div>
            <?php else: ?>
                <div id="paypal-button-container"></div>
                <div id="paypal-spinner" style="display:none;" class="paypal-spinner-wrap">
                    <div class="paypal-spinner"></div>
                    <p>Procesando pago…</p>
                </div>
                <div id="paypal-error" class="alert alert-error" style="display:none;"></div>
            <?php endif; ?>
        </div>

        <p class="plan-secure-note">
            🔒 Pago seguro procesado por PayPal Sandbox (modo de prueba)
        </p>
    </div>

    <p class="text-center mt-2" style="font-size:.82rem;">
        <a href="index.php?page=viewHome">← Volver al inicio</a>
    </p>

</div>

<?php if ($credencialesOk): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars(PAYPAL_CLIENT_ID) ?>&currency=<?= SUSCRIPCION_MONEDA ?>"></script>
<script>
(function () {
    const monto     = '<?= SUSCRIPCION_MONTO ?>';
    const moneda    = '<?= SUSCRIPCION_MONEDA ?>';
    const container = document.getElementById('paypal-button-container');
    const spinner   = document.getElementById('paypal-spinner');
    const errorBox  = document.getElementById('paypal-error');

    function showError(msg) {
        spinner.style.display  = 'none';
        container.style.display = 'none';
        errorBox.style.display  = 'flex';
        errorBox.textContent   = msg;
    }

    paypal.Buttons({
        style: {
            layout: 'vertical',
            color:  'gold',
            shape:  'rect',
            label:  'pay',
            height: 44,
        },

        createOrder: function (data, actions) {
            return actions.order.create({
                purchase_units: [{
                    description: 'Suscripción mensual LumenEdu',
                    amount: {
                        currency_code: moneda,
                        value: monto,
                    },
                }],
            });
        },

        onApprove: function (data, actions) {
            container.style.display = 'none';
            spinner.style.display   = 'flex';

            return fetch('index.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'action=capturarPago&orderID=' + encodeURIComponent(data.orderID),
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    spinner.style.display = 'none';
                    // Mostrar confirmación breve antes de redirigir
                    const ok = document.createElement('div');
                    ok.className = 'alert alert-ok';
                    ok.innerHTML = '<strong>¡Pago exitoso!</strong> Tu suscripción está activa. Redirigiendo…';
                    container.parentNode.insertBefore(ok, container);
                    setTimeout(() => { window.location.href = 'index.php?page=viewHome'; }, 2000);
                } else {
                    showError('Error al procesar el pago: ' + (result.error || 'intenta de nuevo.'));
                }
            })
            .catch(() => {
                showError('Error de red al confirmar el pago. Recarga e intenta de nuevo.');
            });
        },

        onCancel: function () {
            errorBox.style.display  = 'flex';
            errorBox.textContent = 'Pago cancelado. Puedes intentarlo cuando quieras.';
        },

        onError: function (err) {
            showError('Error en PayPal: ' + err);
        },

    }).render('#paypal-button-container');
})();
</script>
<?php endif; ?>
