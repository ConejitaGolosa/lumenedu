<?php
// ============================================================
// controllerUser.php — Controlador de usuarios.
// Maneja las acciones POST (registrar, login) y GET (logout).
// Se incluye desde index.php, que ya inició la sesión.
// ============================================================

// Por si el controlador se llama directamente sin pasar por index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/modelUser.php';

// La acción viene del campo hidden <input name="action"> en los formularios,
// o del parámetro ?action=logout en el enlace de cierre de sesión.
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── ACCIONES POST (formularios) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    switch ($action) {

        // ── REGISTRO ─────────────────────────────────────────
        case 'registrar':

            $nombre = trim($_POST['nombre'] ?? '');
            $email  = trim($_POST['email']  ?? '');
            $pass   =      $_POST['pass']   ?? '';
            $cat    =      $_POST['cat']    ?? '';
            $terms  = isset($_POST['terms']); // checkbox de términos y condiciones

            // Validaciones del lado servidor (el HTML tiene required, pero
            // cualquiera puede enviar un POST sin pasar por el formulario).
            if (!$terms) {
                $_SESSION['error'] = 'Debes aceptar los términos y condiciones.';
                header('Location: index.php?page=viewRegistro');
                exit;
            }
            if (strlen($nombre) < 3 || strlen($nombre) > 16) {
                $_SESSION['error'] = 'El nombre de usuario debe tener entre 3 y 16 caracteres.';
                header('Location: index.php?page=viewRegistro');
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'El email ingresado no es válido.';
                header('Location: index.php?page=viewRegistro');
                exit;
            }
            if (strlen($pass) < 8) {
                $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres.';
                header('Location: index.php?page=viewRegistro');
                exit;
            }
            if (!in_array($cat, ['1', '2', '3'])) {
                $_SESSION['error'] = 'Selecciona un tipo de cuenta válido.';
                header('Location: index.php?page=viewRegistro');
                exit;
            }

            $usuario = new Usuario(null, $nombre, $email, $pass, $cat);

            if ($usuario->alta()) {
                // Guarda el nombre para mostrarlo en la página de confirmación
                $_SESSION['nuevo_nombre'] = $nombre;
                header('Location: index.php?page=viewConfirmacion');
            } else {
                $_SESSION['error'] = 'El nombre de usuario o email ya están registrados.';
                header('Location: index.php?page=viewRegistro');
            }
            exit;

        // ── LOGIN ─────────────────────────────────────────────
        case 'login':

            $identificador = trim($_POST['email'] ?? '');
            $pass          =      $_POST['pass']  ?? '';

            if (empty($identificador) || empty($pass)) {
                $_SESSION['error'] = 'Ingresa tu usuario/email y contraseña.';
                header('Location: index.php?page=viewLogin');
                exit;
            }

            // El modelo acepta NombreUsuario o Correo como identificador
            $datos = Usuario::login($identificador, $pass);

            if ($datos) {
                // Persiste los datos del usuario en la sesión
                $_SESSION['usuario_id']     = $datos['id'];
                $_SESSION['usuario_nombre'] = $datos['nombre'];
                $_SESSION['usuario_tipo']   = $datos['tipo']; // 'Suscriptor' o 'Creador'
                header('Location: index.php?page=viewHome');
            } else {
                $_SESSION['error'] = 'Credenciales incorrectas o cuenta inactiva.';
                header('Location: index.php?page=viewLogin');
            }
            exit;

        default:
            header('Location: index.php');
            exit;
    }
}

// ── LOGOUT (GET) ─────────────────────────────────────────────
// Se accede vía ?action=logout desde el enlace en el header.
if ($action === 'logout') {
    session_unset();    // Borra todas las variables de sesión
    session_destroy();  // Destruye la sesión en el servidor
    header('Location: index.php?page=viewHome');
    exit;
}
