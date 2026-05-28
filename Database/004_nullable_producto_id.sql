-- ============================================================
-- 004_NULLABLE_PRODUCTO_ID.SQL
-- Permite eliminar productos que tienen historial en pedidos
-- cerrados, sin perder los datos históricos.
--
-- Cambios:
--   1. Agrega columna `nombre_producto` (snapshot) a pedido_items
--   2. Llena el snapshot con el nombre actual de cada producto
--   3. Hace `producto_id` nullable
--   4. Cambia el FK a ON DELETE SET NULL
--
-- Ejecutar UNA sola vez en phpMyAdmin o MySQL CLI.
-- ============================================================

USE restaurante_db;

-- Paso 1: Agregar columna de snapshot si no existe
ALTER TABLE pedido_items
    ADD COLUMN IF NOT EXISTS nombre_producto VARCHAR(120) DEFAULT NULL
    AFTER producto_id;

-- Paso 2: Llenar el snapshot para todos los registros existentes
UPDATE pedido_items pi
    INNER JOIN productos p ON p.id = pi.producto_id
    SET pi.nombre_producto = p.nombre
WHERE pi.nombre_producto IS NULL;

-- Paso 3: Eliminar el FK existente (ON DELETE RESTRICT) de forma dinámica
-- Buscamos el nombre del constraint en information_schema
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'pedido_items'
      AND COLUMN_NAME  = 'producto_id'
      AND REFERENCED_TABLE_NAME = 'productos'
    LIMIT 1
);

-- Solo ejecutar el DROP si existe el FK
SET @drop_fk = IF(
    @fk_name IS NOT NULL,
    CONCAT('ALTER TABLE pedido_items DROP FOREIGN KEY `', @fk_name, '`'),
    'SELECT 1' -- no-op si ya fue eliminado
);
PREPARE stmt FROM @drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 4: Modificar la columna para que acepte NULL
ALTER TABLE pedido_items
    MODIFY COLUMN producto_id INT DEFAULT NULL;

-- Paso 5: Recrear el FK con ON DELETE SET NULL
ALTER TABLE pedido_items
    ADD CONSTRAINT fk_pi_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL;
