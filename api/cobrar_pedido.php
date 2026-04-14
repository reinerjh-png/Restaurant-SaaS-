<?php
/**
 * api/cobrar_pedido.php — Registra el cobro de un pedido
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$pedidoId      = (int)($input['pedido_id'] ?? 0);
$pagos         = $input['pagos'] ?? [];
$notas         = trim($input['notas'] ?? '');
$usuarioId     = $_SESSION['usuario_id'];
$restauranteId = $_SESSION['restaurante_id'];

if (!$pedidoId || empty($pagos)) jsonResponse(false, null, 'Datos incompletos');

$db = getDB();

try {
    $db->beginTransaction();

    // Verificar pedido activo
    $stPed = $db->prepare("
        SELECT pe.id, pe.total, pe.mesa_id
        FROM pedidos pe
        WHERE pe.id = ? AND pe.restaurante_id = ? AND pe.estado = 'activo'
        FOR UPDATE
    ");
    $stPed->execute([$pedidoId, $restauranteId]);
    $pedido = $stPed->fetch();
    if (!$pedido) {
        $db->rollBack();
        jsonResponse(false, null, 'Pedido no encontrado o ya cobrado');
    }

    // Métodos de pago válidos
    $metodosValidos = ['efectivo','yape','transferencia','tarjeta','otro'];
    $totalPagado    = 0;

    foreach ($pagos as $pago) {
        $metodo    = $pago['metodo'] ?? '';
        $monto     = floatval($pago['monto'] ?? 0);
        $referencia= trim($pago['referencia'] ?? '');

        if (!in_array($metodo, $metodosValidos) || $monto <= 0) continue;

        $stPago = $db->prepare("
            INSERT INTO pagos (pedido_id, metodo, monto, referencia, usuario_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stPago->execute([$pedidoId, $metodo, $monto, $referencia ?: null, $usuarioId]);
        $totalPagado += $monto;
    }

    if (!$totalPagado) {
        $db->rollBack();
        jsonResponse(false, null, 'No se registraron pagos válidos');
    }

    // Marcar pedido como cobrado
    $stCobrar = $db->prepare("UPDATE pedidos SET estado = 'cobrado', notas = ? WHERE id = ?");
    $stCobrar->execute([$notas ?: null, $pedidoId]);

    // Liberar la mesa
    if ($pedido['mesa_id']) {
        $stMesa = $db->prepare("UPDATE mesas SET estado = 'libre' WHERE id = ?");
        $stMesa->execute([$pedido['mesa_id']]);
    }

    // Actualizar turno activo del usuario
    $stTurno = $db->prepare("
        SELECT id FROM turnos
        WHERE restaurante_id = ? AND usuario_id = ? AND fin IS NULL
        ORDER BY inicio DESC LIMIT 1
    ");
    $stTurno->execute([$restauranteId, $usuarioId]);
    $turno = $stTurno->fetch();

    if ($turno) {
        // Sumar por método al turno
        $totalesMetodo = ['efectivo'=>0,'yape'=>0,'transferencia'=>0,'tarjeta'=>0,'otro'=>0];
        foreach ($pagos as $pg) {
            $m = $pg['metodo'] ?? '';
            if (isset($totalesMetodo[$m])) $totalesMetodo[$m] += floatval($pg['monto'] ?? 0);
        }

        $stActTurno = $db->prepare("
            UPDATE turnos
            SET total_efectivo      = total_efectivo      + ?,
                total_yape          = total_yape          + ?,
                total_transferencia = total_transferencia + ?,
                total_tarjeta       = total_tarjeta       + ?,
                total_otros         = total_otros         + ?,
                total_general       = total_general       + ?
            WHERE id = ?
        ");
        $stActTurno->execute([
            $totalesMetodo['efectivo'],
            $totalesMetodo['yape'],
            $totalesMetodo['transferencia'],
            $totalesMetodo['tarjeta'],
            $totalesMetodo['otro'],
            $totalPagado,
            $turno['id']
        ]);
    }

    $db->commit();
    jsonResponse(true, ['total_cobrado' => $totalPagado], '¡Cobro registrado exitosamente!');

} catch (PDOException $e) {
    $db->rollBack();
    jsonResponse(false, null, 'Error al registrar el cobro: ' . $e->getMessage());
}
