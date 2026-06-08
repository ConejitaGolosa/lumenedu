<?php
// API de polling para mensajes en tiempo real.
// Devuelve JSON con los mensajes nuevos desde cierto IdMensaje.
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/modelMensaje.php';
require_once __DIR__ . '/../models/modelGrupo.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$tipo      = $_GET['tipo'] ?? 'dm';
$desdeId   = (int)($_GET['desde_id'] ?? 0);

if ($tipo === 'grupo') {
    $idGrupo = (int)($_GET['id'] ?? 0);
    if (!$idGrupo) { echo json_encode([]); exit; }
    $grupo = Grupo::getById($idGrupo, $idUsuario);
    if (!$grupo) { echo json_encode([]); exit; }
    echo json_encode(Grupo::getMensajesDesde($idGrupo, $desdeId));
} else {
    $idOtro = (int)($_GET['usuario'] ?? 0);
    if (!$idOtro) { echo json_encode([]); exit; }
    echo json_encode(Mensaje::getMensajesDesde($idUsuario, $idOtro, $desdeId));
}
