<?php
/**
 * api/marcar_item_listo.php — Cambia el estado de un item de pedido (cocina)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['cocina', 'admin', 'superadmin', 'atencion']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$itemId        = (int)($input['item_id'] ?? 0);
$estado        = $input['estado'] ?? '';
$restauranteId = $_SESSION['restaurante_id'];

$estadosValidos = ['pendiente', 'en_preparacion', 'listo', 'entregado'];
if (!$itemId || !in_array($estado, $estadosValidos)) {
    jsonResponse(false, null, 'Datos inválidos');
}

$db = getDB();

// Verificar que el item pertenece al restaurante
$stCheck = $db->prepare("
    SELECT pi.id FROM pedido_items pi
    JOIN pedidos pe ON pe.id = pi.pedido_id
    WHERE pi.id = ? AND pe.restaurante_id = ?
");
$stCheck->execute([$itemId, $restauranteId]);
if (!$stCheck->fetch()) jsonResponse(false, null, 'Item no encontrado');

$st = $db->prepare("UPDATE pedido_items SET estado = ? WHERE id = ?");
$st->execute([$estado, $itemId]);

jsonResponse(true, ['nuevo_estado' => $estado], 'Estado actualizado');
