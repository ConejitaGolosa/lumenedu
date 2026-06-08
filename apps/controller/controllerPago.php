<?php
// ============================================================
// controllerPago.php — Captura y verificación de pago PayPal.
// Llamado por fetch() desde viewSuscribirse via POST.
// Retorna siempre JSON; no imprime HTML.
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Solo acepta la acción capturarPago
$action = $_POST['action'] ?? '';
if ($action !== 'capturarPago') {
    echo json_encode(['success' => false, 'error' => 'Acción inválida.']);
    exit;
}

// Debe haber sesión activa
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión expirada. Inicia sesión nuevamente.']);
    exit;
}

$orderID  = trim($_POST['orderID'] ?? '');
if (!$orderID) {
    echo json_encode(['success' => false, 'error' => 'Order ID no recibido.']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/modelPago.php';

// Verificar que las credenciales estén configuradas
if (PAYPAL_CLIENT_ID === 'TU_CLIENT_ID_SANDBOX_AQUI') {
    echo json_encode(['success' => false, 'error' => 'PayPal Sandbox no configurado aún (ver apps/config/config.php).']);
    exit;
}

// ── PASO 1: obtener token de acceso PayPal ───────────────────
$token = _paypal_get_token();
if (!$token) {
    echo json_encode(['success' => false, 'error' => 'No se pudo autenticar con PayPal. Verifica tus credenciales.']);
    exit;
}

// ── PASO 2: capturar la orden ────────────────────────────────
$capture = _paypal_capture_order($orderID, $token);

if (!$capture || ($capture['status'] ?? '') !== 'COMPLETED') {
    $detail = $capture['details'][0]['description'] ?? 'sin detalle';
    echo json_encode(['success' => false, 'error' => "Pago no completado: $detail"]);
    exit;
}

// ── PASO 3: verificar monto ──────────────────────────────────
$montoCapturado = (float)(
    $capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0
);
if ($montoCapturado < (float)SUSCRIPCION_MONTO) {
    echo json_encode(['success' => false, 'error' => 'El monto del pago no coincide.']);
    exit;
}

$idUsuario  = (int)$_SESSION['usuario_id'];
$tipoActual = $_SESSION['usuario_tipo'] ?? '';

// ── PASO 4: upgrade si es EstudianteGratis ───────────────────
if ($tipoActual === 'EstudianteGratis') {
    Pago::upgradeASuscriptor($idUsuario);
    $_SESSION['usuario_tipo'] = 'Suscriptor';
}

// ── PASO 5: registrar pago en BD ─────────────────────────────
Pago::registrar($idUsuario, $montoCapturado, 'PayPal');

echo json_encode(['success' => true]);
exit;

// ── HELPERS PAYPAL ───────────────────────────────────────────

function _paypal_get_token() {
    $ch = curl_init(PAYPAL_API_BASE . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_USERPWD        => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    curl_close($ch);

    if ($errno) return null;
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function _paypal_capture_order($orderID, $token) {
    $ch = curl_init(PAYPAL_API_BASE . "/v2/checkout/orders/$orderID/capture");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $token",
            "Content-Type: application/json",
            "PayPal-Request-Id: lumen_" . session_id() . "_" . preg_replace('/[^a-zA-Z0-9]/', '', $orderID),
        ],
    ]);
    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    curl_close($ch);

    if ($errno) return null;
    return json_decode($response, true);
}
