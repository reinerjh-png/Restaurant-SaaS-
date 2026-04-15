<?php
/**
 * api/marcar_item_listo.php — Cambia el estado de uno o varios items de pedido (cocina)
 * Acepta: { item_id: 5, estado: '...' }
 *      ó  { item_ids: [5,6,7], estado: '...' }
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['cocina', 'admin', 'superadmin', 'atencion']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$estado        = $input['estado'] ?? '';
$restauranteId = $_SESSION['restaurante_id'];

$estadosValidos = ['pendiente', 'en_preparacion', 'listo', 'entregado'];
if (!in_array($estado, $estadosValidos)) {
    jsonResponse(false, null, 'Estado inválido');
}

// Aceptar un único item_id o un array item_ids
if (!empty($input['item_ids']) && is_array($input['item_ids'])) {
    $itemIds = array_map('intval', $input['item_ids']);
    $itemIds = array_filter($itemIds); // quitar ceros
} elseif (!empty($input['item_id'])) {
    $itemIds = [(int)$input['item_id']];
} else {
    jsonResponse(false, null, 'Datos inválidos: falta item_id o item_ids');
}

if (empty($itemIds)) jsonResponse(false, null, 'No se proporcionaron IDs válidos');

$db = getDB();

try {
    $db->beginTransaction();

    foreach ($itemIds as $itemId) {
        // Verificar que el item pertenece al restaurante
        $stCheck = $db->prepare("
            SELECT pi.id FROM pedido_items pi
            JOIN pedidos pe ON pe.id = pi.pedido_id
            WHERE pi.id = ? AND pe.restaurante_id = ?
        ");
        $stCheck->execute([$itemId, $restauranteId]);
        if (!$stCheck->fetch()) continue; // ignorar IDs inválidos

        $st = $db->prepare("UPDATE pedido_items SET estado = ? WHERE id = ?");
        $st->execute([$estado, $itemId]);
    }

    $db->commit();
    jsonResponse(true, ['nuevo_estado' => $estado, 'actualizados' => count($itemIds)], 'Estado actualizado');

} catch (PDOException $e) {
    $db->rollBack();
    jsonResponse(false, null, 'Error al actualizar: ' . $e->getMessage());
}
