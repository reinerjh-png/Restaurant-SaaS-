-- ============================================================
-- 002_BRANDING.SQL — Campos de identidad de marca por restaurante
-- Sistema SaaS Restaurante | R.DEV
-- Ejecutar en phpMyAdmin o MySQL CLI sobre restaurante_db
-- ============================================================

USE restaurante_db;

-- Agregar nombre_comercial (nombre visible en dashboard y comprobantes)
ALTER TABLE facturacion_config
    ADD COLUMN IF NOT EXISTS nombre_comercial VARCHAR(150) DEFAULT NULL
    AFTER razon_social;

-- Agregar teléfono del restaurante (aparece en comprobantes)
ALTER TABLE facturacion_config
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) DEFAULT NULL
    AFTER direccion_fiscal;
