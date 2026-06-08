<?php
// API de polling para comentarios/respuestas en tiempo real.
// Devuelve JSON con todos los comentarios (raíz + respuestas) más nuevos que desde_id.
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/modelComentario.php';

$tipo  = $_GET['tipo']     ?? '';
$id    = (int)($_GET['id']      ?? 0);
$desde = (int)($_GET['desde_id'] ?? 0);

if (!in_array($tipo, ['video', 'foro'], true) || !$id) {
    echo json_encode([]);
    exit;
}

echo json_encode(Comentario::getDesde($tipo, $id, $desde));
