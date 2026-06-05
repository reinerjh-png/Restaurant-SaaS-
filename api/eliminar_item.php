<?php
/**
 * api/eliminar_item.php — Elimina un ítem de un pedido activo
 * Solo para admin / superadmin
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);   // Solo administradores

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$itemId        = (int)($input['item_id']    ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$itemId) jsonResponse(false, null, 'item_id requerido');

$db = getDB();

try {
    $db->beginTransaction();

    // Verificar que el ítem pertenece a un pedido activo de este restaurante
    $stItem = $db->prepare("
        SELECT pi.id, pi.subtotal, pi.pedido_id, p.mesa_id
        FROM pedido_items pi
        JOIN pedidos p ON p.id = pi.pedido_id
        WHERE pi.id = ?
          AND p.restaurante_id = ?
          AND p.estado = 'activo'
        FOR UPDATE
    ");
    $stItem->execute([$itemId, $restauranteId]);
    $item = $stItem->fetch();

    if (!$item) {
        $db->rollBack();
        jsonResponse(false, null, 'Ítem no encontrado o pedido ya no activo');
    }

    // Eliminar opciones relacionadas primero (FK)
    $db->prepare("DELETE FROM pedido_item_opciones WHERE item_id = ?")->execute([$itemId]);

    // Eliminar el ítem
    $db->prepare("DELETE FROM pedido_items WHERE id = ?")->execute([$itemId]);

    // Recalcular total del pedido
    $stTotal = $db->prepare("SELECT COALESCE(SUM(subtotal),0) FROM pedido_items WHERE pedido_id = ?");
    $stTotal->execute([$item['pedido_id']]);
    $nuevoTotal = $stTotal->fetchColumn();

    $db->prepare("UPDATE pedidos SET total = ? WHERE id = ?")->execute([$nuevoTotal, $item['pedido_id']]);

    $db->commit();
    jsonResponse(true, ['nuevo_total' => $nuevoTotal], 'Ítem eliminado correctamente');

} catch (PDOException $e) {
    $db->rollBack();
    error_log('Error al eliminar: ' . $e->getMessage());
    jsonResponse(false, null, 'Ocurrió un error interno en el servidor.');
}
