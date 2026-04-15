<?php
/**
 * api/get_pedidos_activos.php — Pedidos activos con items y opciones
 * Usado por: cocina (polling) y atención
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['cocina', 'atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// Pedidos activos con items — Solo si tienen al menos un ítem no entregado
// (evita mostrar pedidos vacíos en cocina antes de que atención los envíe)
$stPedidos = $db->prepare("
    SELECT pe.id, pe.tipo, pe.total, pe.created_at,
           m.numero AS mesa_numero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    WHERE pe.restaurante_id = ? AND pe.estado = 'activo'
      AND EXISTS (
          SELECT 1 FROM pedido_items pi
          WHERE pi.pedido_id = pe.id
            AND pi.estado != 'entregado'
      )
    ORDER BY pe.created_at ASC
");
$stPedidos->execute([$restauranteId]);
$pedidos = $stPedidos->fetchAll();

foreach ($pedidos as &$pedido) {
    $stItems = $db->prepare("
        SELECT pi.id, pi.cantidad, pi.precio_unitario, pi.subtotal, pi.notas, pi.estado,
               pr.nombre,
               GROUP_CONCAT(ov.valor SEPARATOR ' · ') AS opciones
        FROM pedido_items pi
        JOIN productos pr ON pr.id = pi.producto_id
        LEFT JOIN pedido_item_opciones pio ON pio.item_id = pi.id
        LEFT JOIN opciones_valor ov ON ov.id = pio.valor_id
        WHERE pi.pedido_id = ? AND pi.estado != 'entregado'
        GROUP BY pi.id
        ORDER BY pi.created_at ASC
    ");
    $stItems->execute([$pedido['id']]);
    $pedido['items'] = $stItems->fetchAll();
}

jsonResponse(true, $pedidos, 'OK');
