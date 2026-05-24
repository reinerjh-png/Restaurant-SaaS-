-- ============================================================
-- 003_GASTOS.SQL — Módulo de Gastos
-- Sistema SaaS Restaurante | R.DEV
-- Ejecutar en phpMyAdmin o MySQL CLI sobre restaurante_db
-- ============================================================

USE restaurante_db;

CREATE TABLE IF NOT EXISTS gastos (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id  INT          NOT NULL,
    usuario_id      INT          NOT NULL,
    categoria       VARCHAR(100) NOT NULL,
    descripcion     VARCHAR(255) NOT NULL,
    monto           DECIMAL(10,2) NOT NULL,
    fecha           DATE         NOT NULL,
    activo          TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índice para búsquedas rápidas por rango de fechas en dashboard y reportes
CREATE INDEX idx_gastos_rest_fecha ON gastos (restaurante_id, fecha);
