-- Script para agregar columnas de auditoría (soft delete) a la tabla pagos
-- Ejecutar en phpMyAdmin

ALTER TABLE `pagos` 
ADD COLUMN `anulado` TINYINT(1) DEFAULT 0,
ADD COLUMN `anulado_por` INT(11) DEFAULT NULL,
ADD COLUMN `anulado_en` TIMESTAMP NULL;
