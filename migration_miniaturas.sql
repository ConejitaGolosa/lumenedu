-- Agrega columna de miniatura a la tabla Video.
-- Ejecutar una sola vez sobre la base de datos "project".
ALTER TABLE Video ADD COLUMN IF NOT EXISTS Miniatura VARCHAR(255) NULL DEFAULT NULL;
