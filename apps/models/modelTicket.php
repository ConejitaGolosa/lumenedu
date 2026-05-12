<?php
// ============================================================
// modelTicket.php — Sistema de tickets mensuales.
// Cada Suscriptor tiene 3 tickets por mes para desbloquear
// el contenido de hasta 3 profesores distintos.
// ============================================================

require_once __DIR__ . '/configConexion.php';

class Ticket {

    // Cuántos tickets ha usado el estudiante en el mes actual.
    public static function usadosEsteMes($idEstudiante) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $mes  = (int)date('n');
        $anio = (int)date('Y');
        $query = "SELECT COUNT(*) FROM Ticket WHERE IdEstudiante=? AND Mes=? AND Anio=?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("iii", $idEstudiante, $mes, $anio);
        $stmt->execute();
        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->cerrarConexion();
        return $count;
    }

    // IDs de los profesores que el estudiante ha ticketeado este mes.
    // Se usa para filtrar qué contenido 'Suscriptores' puede ver.
    public static function profesoresDesbloqueados($idEstudiante) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $mes  = (int)date('n');
        $anio = (int)date('Y');
        $query = "SELECT IdProfesor FROM Ticket WHERE IdEstudiante=? AND Mes=? AND Anio=?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("iii", $idEstudiante, $mes, $anio);
        $stmt->execute();
        $rows  = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return array_column($rows, 'IdProfesor');
    }

    // Usa un ticket para un profesor dado.
    // Retorna: 'ok' | 'duplicado' | 'limite' | 'error'
    public static function usar($idEstudiante, $idProfesor) {
        $mes  = (int)date('n');
        $anio = (int)date('Y');

        $db   = new Conexion();
        $conn = $db->getConexion();

        // Verifica duplicado (mismo profesor en el mismo mes)
        $stmtDup = $conn->prepare("SELECT COUNT(*) FROM Ticket WHERE IdEstudiante=? AND IdProfesor=? AND Mes=? AND Anio=?");
        $stmtDup->bind_param("iiii", $idEstudiante, $idProfesor, $mes, $anio);
        $stmtDup->execute();
        $dup = 0;
        $stmtDup->bind_result($dup);
        $stmtDup->fetch();
        $stmtDup->close();

        if ($dup > 0) { $db->cerrarConexion(); return 'duplicado'; }

        // Verifica límite de 3 tickets por mes
        $stmtCnt = $conn->prepare("SELECT COUNT(*) FROM Ticket WHERE IdEstudiante=? AND Mes=? AND Anio=?");
        $stmtCnt->bind_param("iii", $idEstudiante, $mes, $anio);
        $stmtCnt->execute();
        $total = 0;
        $stmtCnt->bind_result($total);
        $stmtCnt->fetch();
        $stmtCnt->close();

        if ($total >= 3) { $db->cerrarConexion(); return 'limite'; }

        // Inserta el ticket
        $fecha  = date('Y-m-d H:i:s');
        $stmtIns = $conn->prepare("INSERT INTO Ticket (IdEstudiante, IdProfesor, Mes, Anio, FechaUso) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->bind_param("iiiis", $idEstudiante, $idProfesor, $mes, $anio, $fecha);
        $ok = $stmtIns->execute();
        $stmtIns->close();
        $db->cerrarConexion();

        return $ok ? 'ok' : 'error';
    }

    // Tickets usados este mes con nombre del profesor (para mostrar en el panel del alumno).
    public static function getMisTickets($idEstudiante) {
        $db   = new Conexion();
        $conn = $db->getConexion();

        $mes  = (int)date('n');
        $anio = (int)date('Y');
        $query = "SELECT t.IdTicket, t.FechaUso, u.NombreUsuario AS Profesor, u.IdUsuario AS IdProfesor
                  FROM Ticket t JOIN Usuarios u ON u.IdUsuario = t.IdProfesor
                  WHERE t.IdEstudiante=? AND t.Mes=? AND t.Anio=?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("iii", $idEstudiante, $mes, $anio);
        $stmt->execute();
        $tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->cerrarConexion();
        return $tickets;
    }
}
