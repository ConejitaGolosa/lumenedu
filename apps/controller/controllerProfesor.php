<?php
// ============================================================
// controllerProfesor.php — Configuración del perfil de profesor.
// Acciones: actualizarDiasMinimos.
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/modelUser.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Creador') {
    $_SESSION['error'] = 'Acceso restringido a profesores.';
    header('Location: index.php');
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$action    = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=viewConfigProfesor');
    exit;
}

switch ($action) {

    // ── ACTUALIZAR DÍAS MÍNIMOS ──────────────────────────────
    case 'actualizarDiasMinimos':
        $dias = (int)($_POST['dias_minimos'] ?? 0);

        if ($dias < 1 || $dias > 30) {
            $_SESSION['error'] = 'El valor debe estar entre 1 y 30 días.';
            header('Location: index.php?page=viewConfigProfesor');
            exit;
        }

        if (Usuario::actualizarDiasMinimos($idUsuario, $dias)) {
            $_SESSION['mensaje'] = 'Configuración guardada: ahora requieres ' . $dias . ' día(s) de anticipación.';
        } else {
            $_SESSION['error'] = 'No se pudo guardar la configuración.';
        }

        header('Location: index.php?page=viewConfigProfesor');
        exit;

    default:
        header('Location: index.php?page=viewConfigProfesor');
        exit;
}
