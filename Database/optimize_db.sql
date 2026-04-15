-- =========================================================================
-- OPTIMIZACIÓN DE ÍNDICES Sabor Perú / SaaS Restaurante
-- 
-- Ejecuta este script una sola vez en tu panel de phpMyAdmin en InfinityFree
-- para acelerar drásticamente las consultas del Dashboard, Cocina y Mesas.
-- =========================================================================

-- Tabla mesas: Mejorar búsqueda de mesas activas y su estado (para atención)
ALTER TABLE mesas 
ADD INDEX idx_mesas_rest_act (restaurante_id, activo, estado);

-- Tabla pedidos: Mejorar la búsqueda de pedidos activos o cobrados por fecha (Dashboard/Cocina/Historial)
ALTER TABLE pedidos 
ADD INDEX idx_pedidos_rest_estado_fecha (restaurante_id, estado, created_at);

-- Tabla pedido_items: Mejorar cruces cuando se buscan items no entregados de un pedido
ALTER TABLE pedido_items 
ADD INDEX idx_pedido_items_estado (pedido_id, estado);
