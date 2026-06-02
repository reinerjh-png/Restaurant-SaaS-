-- Script para limpiar datos de prueba y reiniciar el sistema
-- Se eliminan pedidos, pagos, comprobantes, gastos, turnos y logs de acceso usando DELETE para evitar errores de claves foráneas en phpMyAdmin.

-- 1. Eliminar datos en orden (hijos primero, luego padres) para respetar dependencias
DELETE FROM `pedido_item_opciones`;
DELETE FROM `pedido_items`;
DELETE FROM `pagos`;
DELETE FROM `comprobantes`;
DELETE FROM `pedidos`;

DELETE FROM `gastos`;
DELETE FROM `turnos`;
DELETE FROM `logs_acceso`;

-- 2. Reiniciar los contadores de Auto Increment a 1
ALTER TABLE `pedido_item_opciones` AUTO_INCREMENT = 1;
ALTER TABLE `pedido_items` AUTO_INCREMENT = 1;
ALTER TABLE `pagos` AUTO_INCREMENT = 1;
ALTER TABLE `comprobantes` AUTO_INCREMENT = 1;
ALTER TABLE `pedidos` AUTO_INCREMENT = 1;
ALTER TABLE `gastos` AUTO_INCREMENT = 1;
ALTER TABLE `turnos` AUTO_INCREMENT = 1;
ALTER TABLE `logs_acceso` AUTO_INCREMENT = 1;

-- 3. Reiniciar correlativos de facturación a 0
UPDATE `facturacion_config` SET `correlativo_boleta` = 0, `correlativo_factura` = 0;
