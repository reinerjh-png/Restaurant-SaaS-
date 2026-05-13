-- ============================================================
-- 001_MODULO_FACTURACION.SQL — Módulo de Comprobantes
-- Sistema SaaS Restaurante | R.DEV
-- Ejecutar en phpMyAdmin o MySQL CLI sobre restaurante_db
-- ============================================================

USE restaurante_db;

-- ─────────────────────────────────────────
-- TABLA: facturacion_config
-- Configuración de comprobantes por restaurante
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS facturacion_config (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id      INT          NOT NULL UNIQUE,
    ruc                 VARCHAR(11)  DEFAULT NULL,
    razon_social        VARCHAR(200) DEFAULT NULL,
    direccion_fiscal    VARCHAR(300) DEFAULT NULL,
    serie_boleta        VARCHAR(4)   DEFAULT 'B001',
    serie_factura       VARCHAR(4)   DEFAULT 'F001',
    correlativo_boleta  INT          DEFAULT 0,
    correlativo_factura INT          DEFAULT 0,
    pie_mensaje         VARCHAR(300) DEFAULT '¡Gracias por su visita!',
    logo                VARCHAR(255) DEFAULT NULL,
    updated_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: comprobantes
-- Registro de boletas y facturas emitidas
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comprobantes (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id      INT          NOT NULL,
    pedido_id           INT          NOT NULL,
    usuario_id          INT          NOT NULL,
    tipo                ENUM('boleta','factura') NOT NULL,
    serie               VARCHAR(4)   NOT NULL,
    correlativo         INT          NOT NULL,
    numero_comprobante  VARCHAR(20)  NOT NULL,       -- ej: B001-00001
    -- Datos del cliente
    tipo_documento      ENUM('dni','ruc') NOT NULL,
    numero_documento    VARCHAR(11)  NOT NULL,
    nombre_cliente      VARCHAR(200) NOT NULL,
    direccion_cliente   VARCHAR(300) DEFAULT NULL,   -- solo facturas
    distrito            VARCHAR(100) DEFAULT NULL,
    provincia           VARCHAR(100) DEFAULT NULL,
    departamento        VARCHAR(100) DEFAULT NULL,
    -- Montos
    subtotal            DECIMAL(10,2) NOT NULL,      -- antes de IGV
    igv                 DECIMAL(10,2) NOT NULL,      -- 18%
    total               DECIMAL(10,2) NOT NULL,
    -- Estado
    anulado             TINYINT(1)   DEFAULT 0,
    motivo_anulacion    VARCHAR(200) DEFAULT NULL,
    -- Snapshot JSON de items (para reimpresión exacta)
    items_json          JSON          DEFAULT NULL,
    -- Snapshot JSON de pagos
    pagos_json          JSON          DEFAULT NULL,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id)      REFERENCES pedidos(id)      ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)     ON DELETE RESTRICT,
    UNIQUE KEY uk_comprobante (restaurante_id, serie, correlativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- Índices adicionales
-- ─────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_comp_pedido     ON comprobantes (pedido_id);
CREATE INDEX IF NOT EXISTS idx_comp_documento  ON comprobantes (numero_documento);
CREATE INDEX IF NOT EXISTS idx_comp_fecha      ON comprobantes (created_at);
CREATE INDEX IF NOT EXISTS idx_comp_anulado    ON comprobantes (anulado);

-- ─────────────────────────────────────────
-- Registro inicial de config para restaurante demo (id=1)
-- ─────────────────────────────────────────
INSERT IGNORE INTO facturacion_config
    (restaurante_id, ruc, razon_social, direccion_fiscal, serie_boleta, serie_factura, pie_mensaje)
VALUES
    (1, NULL, 'Sabor Perú', NULL, 'B001', 'F001', '¡Gracias por su visita! Vuelva pronto 😊');
