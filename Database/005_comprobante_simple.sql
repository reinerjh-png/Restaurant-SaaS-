-- ============================================================
-- 005_COMPROBANTE_SIMPLE.SQL
-- Agrega soporte para "Comprobante Simple" (sin DNI ni RUC).
--
-- Cambios:
--   1. Agrega 'simple' al ENUM tipo de comprobantes
--   2. Hace nullable tipo_documento y numero_documento
--      (no se requieren para comprobante simple)
--
-- Ejecutar UNA sola vez en phpMyAdmin.
-- ============================================================

USE restaurante_db;

-- Paso 1: Ampliar el ENUM tipo para incluir 'simple'
ALTER TABLE comprobantes
    MODIFY COLUMN tipo ENUM('boleta','factura','simple') NOT NULL;

-- Paso 2: Hacer nullable tipo_documento
ALTER TABLE comprobantes
    MODIFY COLUMN tipo_documento ENUM('dni','ruc') DEFAULT NULL;

-- Paso 3: Hacer nullable numero_documento
ALTER TABLE comprobantes
    MODIFY COLUMN numero_documento VARCHAR(11) DEFAULT NULL;
