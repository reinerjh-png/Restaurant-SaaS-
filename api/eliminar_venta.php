<?php
/**
 * api/eliminar_venta.php — Elimina un pedido cobrado y anula su comprobante
 * Solo para admin/superadmin
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input = json_decode(file_get_contents('php://input'), true);
$pedidoId      = (int)($input['id'] ?? 0);
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
        jsonResponse(false, null, 'El pedido no está cobrado o ya fue cancelado');
    }

    // 1. Obtener los pagos a eliminar para revertir el turno correspondiente
    $stPagosElim = $db->prepare("
        SELECT metodo, SUM(monto) AS total
        FROM pagos
        WHERE pedido_id = ? AND anulado = 0
        GROUP BY metodo
    ");
    $stPagosElim->execute([$pedidoId]);
    $pagosARevertir = $stPagosElim->fetchAll();
    $totalRevertido = array_sum(array_column($pagosARevertir, 'total'));

    // 2. Ajustar el turno al que pertenecían esos pagos
    if ($totalRevertido > 0) {
        $stTurno = $db->prepare("
            SELECT t.id
            FROM turnos t
            JOIN pagos p ON p.pedido_id = ?
            WHERE t.restaurante_id = ?
              AND p.created_at >= t.inicio
              AND (t.fin IS NULL OR p.created_at <= t.fin)
            LIMIT 1
        ");
        $stTurno->execute([$pedidoId, $restauranteId]);
        $turno = $stTurno->fetch();

        if ($turno) {
            $reversos = ['efectivo' => 0, 'yape' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
            foreach ($pagosARevertir as $pr) {
                $m = $pr['metodo'];
                if (isset($reversos[$m])) $reversos[$m] += floatval($pr['total']);
            }

            $stRev = $db->prepare("
                UPDATE turnos
                SET total_efectivo      = GREATEST(0, total_efectivo      - ?),
                    total_yape          = GREATEST(0, total_yape          - ?),
                    total_transferencia = GREATEST(0, total_transferencia - ?),
                    total_tarjeta       = GREATEST(0, total_tarjeta       - ?),
                    total_otros         = GREATEST(0, total_otros         - ?),
                    total_general       = GREATEST(0, total_general       - ?)
                WHERE id = ?
            ");
            $stRev->execute([
                $reversos['efectivo'],
                $reversos['yape'],
                $reversos['transferencia'],
                $reversos['tarjeta'],
                $reversos['otro'],
                $totalRevertido,
                $turno['id'],
            ]);
        }
    }

    // 3. Anular comprobante asociado si existe
    $stComp = $db->prepare("UPDATE comprobantes SET anulado = 1, motivo_anulacion = 'Venta eliminada desde historial' WHERE pedido_id = ?");
    $stComp->execute([$pedidoId]);

    // 4. Marcar pagos como anulados (borrado lógico) en lugar de eliminarlos
    $stPagos = $db->prepare("UPDATE pagos SET anulado = 1, anulado_por = ?, anulado_en = NOW() WHERE pedido_id = ? AND anulado = 0");
    $stPagos->execute([$_SESSION['usuario_id'], $pedidoId]);

    // 5. Marcar como cancelado
    $stCan = $db->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?");
    $stCan->execute([$pedidoId]);

    $db->commit();
    jsonResponse(true, null, 'Venta eliminada correctamente');

} catch (PDOException $e) {
    $db->rollBack();
    error_log('Error al eliminar venta: ' . $e->getMessage());
    jsonResponse(false, null, 'Ocurrió un error interno en el servidor.');
}
