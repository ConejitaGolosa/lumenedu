<?php
// ============================================================
// controllerForo.php — Acciones de foros comunitarios.
// Acciones: crearForo, comentarForo.
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/modelForo.php';
require_once __DIR__ . '/../models/modelComentario.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = 'Debes iniciar sesión para participar en los foros.';
    header('Location: index.php?page=viewLogin');
    exit;
}

$idUsuario = (int)$_SESSION['usuario_id'];
$action    = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=viewForos');
    exit;
}

$categorias = ['General', 'Matemáticas', 'Ciencias', 'Historia', 'Lenguaje', 'Tecnología', 'Arte', 'Otros'];

switch ($action) {

    // ── CREAR FORO ───────────────────────────────────────────
    case 'crearForo':
        $titulo    = trim($_POST['titulo']    ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'General');

        if (empty($titulo) || empty($contenido)) {
            $_SESSION['error'] = 'El título y el contenido son obligatorios.';
            header('Location: index.php?page=viewForos');
            exit;
        }

        if (mb_strlen($titulo) > 128) {
            $_SESSION['error'] = 'El título no puede superar 128 caracteres.';
            header('Location: index.php?page=viewForos');
            exit;
        }

        if (!in_array($categoria, $categorias)) {
            $categoria = 'General';
        }

        $idForo = Foro::crear($idUsuario, $titulo, $contenido, $categoria);

        if ($idForo) {
            $_SESSION['mensaje'] = 'Hilo creado correctamente.';
            header('Location: index.php?page=viewForo&id=' . $idForo);
        } else {
            $_SESSION['error'] = 'Error al crear el hilo. Inténtalo de nuevo.';
            header('Location: index.php?page=viewForos');
        }
        exit;

    // ── COMENTAR FORO ────────────────────────────────────────
    case 'comentarForo':
        $idForo    = (int)($_POST['id_foro']  ?? 0);
        $contenido = trim($_POST['contenido'] ?? '');

        if (!$idForo || empty($contenido)) {
            $_SESSION['error'] = 'El comentario no puede estar vacío.';
            header('Location: index.php?page=viewForo&id=' . $idForo);
            exit;
        }

        if (mb_strlen($contenido) > 1024) {
            $_SESSION['error'] = 'El comentario no puede superar 1024 caracteres.';
            header('Location: index.php?page=viewForo&id=' . $idForo);
            exit;
        }

        if (Comentario::agregar(null, $idUsuario, $contenido, $idForo)) {
            $_SESSION['mensaje'] = 'Comentario publicado.';
        } else {
            $_SESSION['error'] = 'Error al publicar el comentario.';
        }

        header('Location: index.php?page=viewForo&id=' . $idForo);
        exit;

    default:
        header('Location: index.php?page=viewForos');
        exit;
}
