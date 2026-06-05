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
$pedidoId      = (int)($input['id'] ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$pedidoId) jsonResponse(false, null, 'ID requerido');

$db = getDB();

try {
    $db->beginTransaction();

    // Cargar pedido con bloqueo (incluye mesa_id para restaurarla)
    $stPed = $db->prepare("SELECT id, estado, mesa_id FROM pedidos WHERE id = ? AND restaurante_id = ? FOR UPDATE");
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

    // 1. Obtener los pagos que se van a anular para revertir el turno
    $stPagosElim = $db->prepare("
        SELECT metodo, SUM(monto) AS total
        FROM pagos
        WHERE pedido_id = ? AND anulado = 0
        GROUP BY metodo
    ");
    $stPagosElim->execute([$pedidoId]);
    $pagosARevertir = $stPagosElim->fetchAll();
    $totalRevertido = array_sum(array_column($pagosARevertir, 'total'));

    // 2. Buscar el turno al que pertenecen estos pagos (el turno cuyo rango de tiempo incluye los pagos)
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
            // Restar los montos del turno correspondiente por método
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

    // 3. Marcar pagos registrados como anulados (borrado lógico) (se volverá a cobrar)
    $stPagos = $db->prepare("UPDATE pagos SET anulado = 1, anulado_por = ?, anulado_en = NOW() WHERE pedido_id = ? AND anulado = 0");
    $stPagos->execute([$_SESSION['usuario_id'], $pedidoId]);

    // 4. Anular comprobante asociado si existe
    $stComp = $db->prepare("UPDATE comprobantes SET anulado = 1, motivo_anulacion = 'Pedido reabierto para modificación' WHERE pedido_id = ? AND anulado = 0");
    $stComp->execute([$pedidoId]);

    // 5. Cambiar estado del pedido a activo
    $stAct = $db->prepare("UPDATE pedidos SET estado = 'activo' WHERE id = ?");
    $stAct->execute([$pedidoId]);

    // 6. Reocupar la mesa si el pedido tiene una asignada
    if ($pedido['mesa_id']) {
        // Verificar que la mesa esté libre antes de ocuparla (podría haberse reasignado)
        $stMesa = $db->prepare("SELECT estado FROM mesas WHERE id = ? AND restaurante_id = ?");
        $stMesa->execute([$pedido['mesa_id'], $restauranteId]);
        $mesa = $stMesa->fetch();

        if ($mesa && $mesa['estado'] === 'libre') {
            $db->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?")
               ->execute([$pedido['mesa_id']]);
        }
        // Si la mesa ya está ocupada por otro pedido, no se bloquea la reapertura,
        // pero la inconsistencia queda registrada en el estado del pedido activo.
    }

    $db->commit();
    jsonResponse(true, null, 'Pedido reabierto correctamente');

} catch (PDOException $e) {
    $db->rollBack();
    error_log('Error al reabrir pedido: ' . $e->getMessage());
    jsonResponse(false, null, 'Ocurrió un error interno en el servidor.');
}
