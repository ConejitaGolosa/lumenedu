-- ============================================================
-- migration_v3.sql — Features: moderador, tiempo mínimo clases,
-- foros comunitarios y respuestas a comentarios.
-- Ejecutar UNA VEZ sobre "Project" después de migration_v2.sql.
-- ============================================================

USE Project;

-- ── FEATURE 1: Tiempo mínimo de espera para clases virtuales ──
ALTER TABLE Usuarios
    ADD COLUMN DiasAntMinimo TINYINT UNSIGNED NOT NULL DEFAULT 2;

-- ── FEATURE 3: Foros / Hilos de comunidad ─────────────────────
CREATE TABLE IF NOT EXISTS Foro (
    IdForo           INT          PRIMARY KEY AUTO_INCREMENT,
    IdAutor          INT          NOT NULL,
    Titulo           VARCHAR(128) NOT NULL,
    Contenido        TEXT         NOT NULL,
    Categoria        VARCHAR(64)  NOT NULL DEFAULT 'General',
    FechaPublicacion DATETIME     NOT NULL,
    FOREIGN KEY (IdAutor) REFERENCES Usuarios(IdUsuario)
);

-- Comentarios ahora pueden pertenecer a un video O a un foro;
-- IdVideo pasa a nullable para soportar ambos contextos.
ALTER TABLE Comentario MODIFY COLUMN IdVideo INT NULL;
ALTER TABLE Comentario ADD COLUMN IdForo INT NULL AFTER IdVideo;
ALTER TABLE Comentario ADD COLUMN IdComentarioPadre INT NULL AFTER IdForo;
ALTER TABLE Comentario ADD CONSTRAINT fk_com_foro  FOREIGN KEY (IdForo)            REFERENCES Foro(IdForo);
ALTER TABLE Comentario ADD CONSTRAINT fk_com_padre FOREIGN KEY (IdComentarioPadre) REFERENCES Comentario(IdComentario);
