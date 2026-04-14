<?php
/**
 * api/get_pedido_detalle.php — Detalle completo de un pedido
 * Usado por historial.php para el modal de detalle
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin', 'atencion']);

$pedidoId      = (int)($_GET['id'] ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$pedidoId) jsonResponse(false, null, 'ID requerido');

$db = getDB();

// Pedido
$stPed = $db->prepare("
    SELECT pe.id, pe.tipo, pe.estado, pe.total, pe.created_at,
           m.numero AS mesa_numero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    WHERE pe.id = ? AND pe.restaurante_id = ?
");
$stPed->execute([$pedidoId, $restauranteId]);
$pedido = $stPed->fetch();
if (!$pedido) jsonResponse(false, null, 'Pedido no encontrado');

// Items
$stItems = $db->prepare("
    SELECT pi.id, pi.cantidad, pi.precio_unitario, pi.subtotal, pi.notas, pi.estado,
           pr.nombre,
           GROUP_CONCAT(ov.valor SEPARATOR ' · ') AS opciones
    FROM pedido_items pi
    JOIN productos pr ON pr.id = pi.producto_id
    LEFT JOIN pedido_item_opciones pio ON pio.item_id = pi.id
    LEFT JOIN opciones_valor ov ON ov.id = pio.valor_id
    WHERE pi.pedido_id = ?
    GROUP BY pi.id
    ORDER BY pi.created_at ASC
");
$stItems->execute([$pedidoId]);
$pedido['items'] = $stItems->fetchAll();

// Pagos
$stPagos = $db->prepare("SELECT metodo, monto, referencia FROM pagos WHERE pedido_id = ? ORDER BY id");
$stPagos->execute([$pedidoId]);
$pedido['pagos'] = $stPagos->fetchAll();

jsonResponse(true, $pedido, 'OK');
