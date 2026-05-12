-- ============================================================
-- migration_v2.sql — Extiende la BD con videos, tickets,
-- solicitudes de clase, comentarios y notificaciones.
-- Ejecutar UNA VEZ sobre la BD "Project" ya existente.
-- ============================================================

USE Project;

-- ── VIDEOS ───────────────────────────────────────────────────
-- El ciclo de vida es: Pendiente → Aprobado/Rechazado → Publicado
CREATE TABLE IF NOT EXISTS Video (
    IdVideo          INT PRIMARY KEY AUTO_INCREMENT,
    IdProfesor       INT NOT NULL,
    ArchivoVideo     VARCHAR(256) NOT NULL,         -- ruta relativa en public/uploads/videos/
    Estado           VARCHAR(20)  NOT NULL DEFAULT 'Pendiente',
    -- Campos que el profesor completa DESPUÉS de la aprobación del admin
    Titulo           VARCHAR(128),
    Descripcion      VARCHAR(2048),
    Privacidad       VARCHAR(20)  DEFAULT 'Publico', -- Publico | Suscriptores | Privado
    FechaSubida      DATETIME     NOT NULL,
    FechaPublicacion DATETIME,
    FOREIGN KEY (IdProfesor) REFERENCES Usuarios(IdUsuario)
);

-- ── REVISION DE VIDEO ─────────────────────────────────────────
-- El administrador aprueba o rechaza cada video pendiente
CREATE TABLE IF NOT EXISTS RevisionVideo (
    IdRevision      INT PRIMARY KEY AUTO_INCREMENT,
    IdVideo         INT          NOT NULL,
    IdAdmin         INT          NOT NULL,
    Validado        TINYINT(1)   NOT NULL,           -- 1 = aprobado, 0 = rechazado
    MotivoRechazo   VARCHAR(512),                    -- obligatorio si Validado = 0
    FechaRevision   DATETIME     NOT NULL,
    FOREIGN KEY (IdVideo) REFERENCES Video(IdVideo),
    FOREIGN KEY (IdAdmin) REFERENCES Usuarios(IdUsuario)
);

-- ── COMENTARIOS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Comentario (
    IdComentario    INT PRIMARY KEY AUTO_INCREMENT,
    IdVideo         INT           NOT NULL,
    IdUsuario       INT           NOT NULL,
    Contenido       VARCHAR(1024) NOT NULL,
    FechaComentario DATETIME      NOT NULL,
    FOREIGN KEY (IdVideo)    REFERENCES Video(IdVideo),
    FOREIGN KEY (IdUsuario)  REFERENCES Usuarios(IdUsuario)
);

-- ── TICKETS ──────────────────────────────────────────────────
-- Los Suscriptores tienen 3 tickets por mes para acceder al
-- contenido de hasta 3 profesores distintos ese mes.
CREATE TABLE IF NOT EXISTS Ticket (
    IdTicket     INT      PRIMARY KEY AUTO_INCREMENT,
    IdEstudiante INT      NOT NULL,
    IdProfesor   INT      NOT NULL,
    Mes          TINYINT  NOT NULL,  -- 1-12
    Anio         SMALLINT NOT NULL,
    FechaUso     DATETIME NOT NULL,
    -- Un alumno no puede ticketear al mismo profesor dos veces en el mismo mes
    UNIQUE KEY uq_ticket (IdEstudiante, IdProfesor, Mes, Anio),
    FOREIGN KEY (IdEstudiante) REFERENCES Usuarios(IdUsuario),
    FOREIGN KEY (IdProfesor)   REFERENCES Usuarios(IdUsuario)
);

-- ── SOLICITUDES DE CLASE VIRTUAL ─────────────────────────────
CREATE TABLE IF NOT EXISTS SolicitudClase (
    IdSolicitud        INT          PRIMARY KEY AUTO_INCREMENT,
    IdEstudiante       INT          NOT NULL,
    IdProfesor         INT          NOT NULL,
    FechaPropuesta     DATETIME     NOT NULL,   -- fecha/hora propuesta por el alumno
    Estado             VARCHAR(30)  NOT NULL DEFAULT 'Pendiente',
    RespuestaProfesor  VARCHAR(512),            -- condiciones o motivo de rechazo
    FechaSolicitud     DATETIME     NOT NULL,
    FOREIGN KEY (IdEstudiante) REFERENCES Usuarios(IdUsuario),
    FOREIGN KEY (IdProfesor)   REFERENCES Usuarios(IdUsuario)
);

-- ── NOTIFICACIONES ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Notificacion (
    IdNotificacion    INT          PRIMARY KEY AUTO_INCREMENT,
    IdUsuario         INT          NOT NULL,    -- destinatario
    Tipo              VARCHAR(50)  NOT NULL,    -- VideoAprobado | VideoRechazado | SolicitudClase | RespuestaSolicitud
    Mensaje           VARCHAR(1024) NOT NULL,
    Leida             TINYINT(1)   NOT NULL DEFAULT 0,
    FechaNotificacion DATETIME     NOT NULL,
    IdReferencia      INT,                      -- IdVideo o IdSolicitud relacionado
    FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario)
);

-- ── ADMINISTRADOR ─────────────────────────────────────────────
-- Contraseña: 1234 (hash bcrypt generado con PHP password_hash)
INSERT IGNORE INTO Usuarios
    (NombreUsuario, Correo, `HashContraseña`, FechaRegistro, EstadoCuenta, TipoUsuario, PreferenciasPrivacidad)
VALUES
    ('Mariano', 'mariano@lumenedu.com',
     '$2y$10$Y0CQ4Y3jalDx62YpPMwErOn9bI4l.Y0nMZdObcYzlWwdoCtAOc6BS',
     NOW(), 'Activo', 'Administrador', 'Privado');
