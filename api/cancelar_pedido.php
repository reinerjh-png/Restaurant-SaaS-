<?php
/**
 * api/cancelar_pedido.php — Cancela un pedido activo y libera la mesa
 * Solo para admin/superadmin
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$pedidoId      = (int)($input['id'] ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$pedidoId) jsonResponse(false, null, 'ID requerido');

$db = getDB();

try {
    $db->beginTransaction();

    $stPed = $db->prepare("SELECT mesa_id FROM pedidos WHERE id = ? AND restaurante_id = ? AND estado = 'activo' FOR UPDATE");
    $stPed->execute([$pedidoId, $restauranteId]);
    $pedido = $stPed->fetch();
    if (!$pedido) {
        $db->rollBack();
        jsonResponse(false, null, 'Pedido no encontrado o ya procesado');
    }

    // Cancelar pedido
    $stCan = $db->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?");
    $stCan->execute([$pedidoId]);

    // Liberar mesa
    if ($pedido['mesa_id']) {
        $stMesa = $db->prepare("UPDATE mesas SET estado = 'libre' WHERE id = ?");
        $stMesa->execute([$pedido['mesa_id']]);
    }

    $db->commit();
    jsonResponse(true, null, 'Pedido cancelado y mesa liberada');

} catch (PDOException $e) {
    $db->rollBack();
    jsonResponse(false, null, 'Error al cancelar: ' . $e->getMessage());
}
