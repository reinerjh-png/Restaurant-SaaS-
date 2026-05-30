<?php
/**
 * api/reabrir_venta.php — Reabre un pedido cobrado para ser modificado
 * Solo para admin/superadmin
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input = json_decode(file_get_contents('php://input'), true);
$pedidoId = (int)($input['id'] ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$pedidoId) jsonResponse(false, null, 'ID requerido');

$db = getDB();

try {
    $db->beginTransaction();

    $stPed = $db->prepare("SELECT estado FROM pedidos WHERE id = ? AND restaurante_id = ? FOR UPDATE");
    $stPed->execute([$pedidoId, $restauranteId]);
    $pedido = $stPed->fetch();

    if (!$pedido) {
        $db->rollBack();
        jsonResponse(false, null, 'Pedido no encontrado');
    }
    
    if ($pedido['estado'] !== 'cobrado') {
        $db->rollBack();
        jsonResponse(false, null, 'Solo se pueden modificar pedidos cobrados');
    }

    // 1. Cambiar estado a activo
    $stAct = $db->prepare("UPDATE pedidos SET estado = 'activo' WHERE id = ?");
    $stAct->execute([$pedidoId]);

    // 2. Anular comprobante asociado si existe
    $stComp = $db->prepare("UPDATE comprobantes SET anulado = 1, motivo_anulacion = 'Pedido reabierto para modificación' WHERE pedido_id = ? AND anulado = 0");
    $stComp->execute([$pedidoId]);

    // 3. Eliminar los pagos registrados (ya que se volverá a cobrar)
    $stPagos = $db->prepare("DELETE FROM pagos WHERE pedido_id = ?");
    $stPagos->execute([$pedidoId]);

    $db->commit();
    jsonResponse(true, null, 'Pedido reabierto correctamente');

} catch (PDOException $e) {
    $db->rollBack();
    jsonResponse(false, null, 'Error al reabrir pedido: ' . $e->getMessage());
}
