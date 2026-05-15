<?php
// ============================================================
// modelUser.php — Modelo del usuario.
// Contiene la clase Usuario con métodos para registro y login.
// Todas las consultas usan prepared statements para evitar SQL injection.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Usuario {
    private $id;
    private $nombre; // Corresponde a NombreUsuario en la BD
    private $email;  // Corresponde a Correo en la BD
    private $pass;   // Contraseña en texto plano (solo durante registro/login, nunca se persiste así)
    private $cat;    // Tipo de cuenta: 1 = Suscriptor (alumno), 2 = Creador (profesor)

    public function __construct($id, $nombre, $email, $pass, $cat) {
        $this->id     = $id;
        $this->nombre = $nombre;
        $this->email  = $email;
        $this->pass   = $pass;
        $this->cat    = $cat;
    }

    public function getId()     { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail()  { return $this->email; }

    // ── EXISTENCIA ───────────────────────────────────────────
    // Consulta si ya hay un usuario con el mismo NombreUsuario o Correo.
    // Retorna true si existe (duplicado), false si no.
    public function existUser() {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT COUNT(*) FROM Usuarios WHERE NombreUsuario = ? OR Correo = ?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("ss", $this->nombre, $this->email);
        $stmt->execute();
        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();

        return $count > 0;
    }

    // ── REGISTRO ─────────────────────────────────────────────
    // Inserta el usuario en la BD.
    // Hashea la contraseña con bcrypt (PASSWORD_BCRYPT) antes de guardarla.
    // Retorna true si el insert fue exitoso, false si el usuario ya existe.
    public function alta() {
        if ($this->existUser()) {
            return false; // NombreUsuario o Correo ya registrado
        }

        $db   = new Conexion();
        $conn = $db->getConexion();

        // Mapeo de cat (valor del formulario) al TipoUsuario que espera la BD
        // 1 = EstudianteGratis | 2 = Suscriptor (pagado) | 3 = Creador (profesor)
        $tipoUsuario = match((int)$this->cat) {
            3       => 'Creador',
            2       => 'Suscriptor',
            default => 'EstudianteGratis',
        };

        // bcrypt es el estándar recomendado; es irreversible y resistente a rainbow tables
        $hashPass   = password_hash($this->pass, PASSWORD_BCRYPT);
        $fecha      = date('Y-m-d H:i:s');
        $estado     = 'Activo';
        $privacidad = 'Privado'; // Privado por defecto; el usuario puede cambiarlo luego

        // Backticks en `HashContraseña` porque el identificador contiene ñ
        $query = "INSERT INTO Usuarios
                    (NombreUsuario, Correo, `HashContraseña`, FechaRegistro, EstadoCuenta, TipoUsuario, PreferenciasPrivacidad)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sssssss",
            $this->nombre,
            $this->email,
            $hashPass,
            $fecha,
            $estado,
            $tipoUsuario,
            $privacidad
        );

        if ($stmt->execute()) {
            $this->id = $conn->insert_id; // ID generado por AUTO_INCREMENT
            $stmt->close();
            $db->cerrarConexion();
            return true;
        }

        $stmt->close();
        $db->cerrarConexion();
        return false;
    }

    // ── DÍAS MÍNIMOS (PROFESORES) ────────────────────────────
    // Días de anticipación requeridos para recibir solicitudes de clase.
    public static function getMinDias($idProfesor) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT DiasAntMinimo FROM Usuarios WHERE IdUsuario = ? AND TipoUsuario = 'Creador'"
        );
        $stmt->bind_param("i", $idProfesor);
        $stmt->execute();
        $dias = 2;
        $stmt->bind_result($dias);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return (int)($dias ?? 2);
    }

    // Actualiza los días mínimos de un profesor.
    public static function actualizarDiasMinimos($idProfesor, $dias) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "UPDATE Usuarios SET DiasAntMinimo = ? WHERE IdUsuario = ? AND TipoUsuario = 'Creador'"
        );
        $stmt->bind_param("ii", $dias, $idProfesor);
        $stmt->execute();
        // affected_rows puede ser 0 si el valor no cambió, pero la operación es válida
        $ok = $stmt->errno === 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // ── GESTIÓN DE ROLES (ADMIN) ─────────────────────────────
    // Lista de usuarios activos no-administradores (para el panel de admin).
    public static function getUsuariosActivos() {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "SELECT IdUsuario, NombreUsuario, TipoUsuario
             FROM Usuarios
             WHERE EstadoCuenta = 'Activo' AND TipoUsuario != 'Administrador'
             ORDER BY NombreUsuario"
        );
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $rows;
    }

    // Asigna un nuevo rol a un usuario (no puede aplicarse al Administrador).
    public static function asignarRol($idUsuario, $nuevoRol) {
        $rolesValidos = ['Creador', 'Suscriptor', 'EstudianteGratis', 'Moderador'];
        if (!in_array($nuevoRol, $rolesValidos)) { return false; }

        $db   = new Conexion();
        $conn = $db->getConexion();

        $stmt = $conn->prepare(
            "UPDATE Usuarios SET TipoUsuario = ?
             WHERE IdUsuario = ? AND TipoUsuario != 'Administrador'"
        );
        $stmt->bind_param("si", $nuevoRol, $idUsuario);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        $db->cerrarConexion();
        return $ok;
    }

    // ── LOGIN ────────────────────────────────────────────────
    // Método estático: busca un usuario por NombreUsuario o Correo
    // y verifica la contraseña con password_verify.
    // Retorna array con datos del usuario si las credenciales son correctas,
    // null si no se encuentra, la cuenta no está activa o la contraseña es incorrecta.
    public static function login($identificador, $pass) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $query = "SELECT IdUsuario, NombreUsuario, Correo, `HashContraseña`, TipoUsuario, EstadoCuenta
                  FROM Usuarios
                  WHERE NombreUsuario = ? OR Correo = ?
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $identificador, $identificador);
        $stmt->execute();
        $stmt->bind_result($id, $nombre, $email, $hash, $tipo, $estado);
        $found = $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();

        if (!$found)                        return null; // Usuario no existe
        if ($estado !== 'Activo')           return null; // Cuenta suspendida o inactiva
        if (!password_verify($pass, $hash)) return null; // Contraseña incorrecta

        return [
            'id'     => $id,
            'nombre' => $nombre,
            'email'  => $email,
            'tipo'   => $tipo,  // 'Suscriptor' o 'Creador'
        ];
    }
}
