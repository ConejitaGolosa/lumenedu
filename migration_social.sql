-- ============================================================
-- Ejecuta este archivo en phpMyAdmin (base de datos: project)
-- para crear las tablas del sistema social.
-- ============================================================

CREATE TABLE IF NOT EXISTS Amistad (
    IdAmistad      INT PRIMARY KEY AUTO_INCREMENT,
    IdSolicitante  INT NOT NULL,
    IdReceptor     INT NOT NULL,
    Estado         VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    FechaSolicitud DATETIME NOT NULL,
    FechaRespuesta DATETIME,
    FOREIGN KEY (IdSolicitante) REFERENCES Usuarios(IdUsuario),
    FOREIGN KEY (IdReceptor)    REFERENCES Usuarios(IdUsuario),
    UNIQUE KEY uq_amistad (IdSolicitante, IdReceptor)
);

CREATE TABLE IF NOT EXISTS Grupo (
    IdGrupo       INT PRIMARY KEY AUTO_INCREMENT,
    Nombre        VARCHAR(64) NOT NULL,
    IdCreador     INT NOT NULL,
    FechaCreacion DATETIME NOT NULL,
    FOREIGN KEY (IdCreador) REFERENCES Usuarios(IdUsuario)
);

CREATE TABLE IF NOT EXISTS MiembroGrupo (
    IdGrupo    INT NOT NULL,
    IdUsuario  INT NOT NULL,
    FechaUnion DATETIME NOT NULL,
    PRIMARY KEY (IdGrupo, IdUsuario),
    FOREIGN KEY (IdGrupo)   REFERENCES Grupo(IdGrupo),
    FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario)
);

CREATE TABLE IF NOT EXISTS MensajeGrupo (
    IdMensaje  INT PRIMARY KEY AUTO_INCREMENT,
    IdGrupo    INT NOT NULL,
    IdEmisor   INT NOT NULL,
    Contenido  VARCHAR(1024) NOT NULL,
    FechaEnvio DATETIME NOT NULL,
    FOREIGN KEY (IdGrupo)  REFERENCES Grupo(IdGrupo),
    FOREIGN KEY (IdEmisor) REFERENCES Usuarios(IdUsuario)
);

CREATE TABLE IF NOT EXISTS RecuperacionPassword (
    IdRecuperacion  INT PRIMARY KEY AUTO_INCREMENT,
    IdUsuario       INT NOT NULL,
    Codigo          VARCHAR(8) NOT NULL,
    FechaExpiracion DATETIME NOT NULL,
    Usado           TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario)
);
