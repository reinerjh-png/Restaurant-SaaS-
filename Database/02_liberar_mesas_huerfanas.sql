-- ============================================================
-- 007_LIBERAR_MESAS_HUERFANAS.SQL
-- Liberar mesas que se quedaron en estado 'ocupada' o 'reservada'
-- después de eliminar los pedidos manualmente de la base de datos.
-- Sistema SaaS Restaurante | R.DEV
-- Ejecutar en phpMyAdmin o MySQL CLI sobre restaurante_db
-- ============================================================

USE restaurante_db;

-- Actualizar a 'libre' todas las mesas que no tengan actualmente un pedido 'activo'
UPDATE mesas
SET estado = 'libre'
WHERE id NOT IN (
    SELECT mesa_id 
    FROM pedidos 
    WHERE estado = 'activo' AND mesa_id IS NOT NULL
);
