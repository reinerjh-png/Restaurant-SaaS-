<?php
/**
 * api/cerrar_turno.php
 * Cierra el turno de caja activo y abre automáticamente el siguiente.
 * La caja siempre permanece abierta. Solo disponible para admins.
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.');
}

$data = json_decode(file_get_contents('php://input'), true);
$reabrir = isset($data['reabrir']) ? filter_var($data['reabrir'], FILTER_VALIDATE_BOOLEAN) : false;

$restauranteId = $_SESSION['restaurante_id'];
$usuarioId     = $_SESSION['usuario_id'];
$db = getDB();

try {
    $db->beginTransaction();

    // Buscar turno abierto
    $stTurno = $db->prepare("
        SELECT id, inicio FROM turnos 
        WHERE restaurante_id = ? AND fin IS NULL
        ORDER BY inicio DESC LIMIT 1
        FOR UPDATE
    ");
    $stTurno->execute([$restauranteId]);
    $turno = $stTurno->fetch();

    if (!$turno) {
        $db->rollBack();
        jsonResponse(false, null, 'No hay una caja abierta en este momento.');
    }

    // Recalcular totales desde tabla pagos (excluyendo anulados)
    $stPagos = $db->prepare("
        SELECT p.metodo, SUM(p.monto) as total
        FROM pagos p
        JOIN pedidos pe ON pe.id = p.pedido_id
        WHERE pe.restaurante_id = ? 
          AND p.created_at >= ?
          AND p.anulado = 0
        GROUP BY p.metodo
    ");
    $stPagos->execute([$restauranteId, $turno['inicio']]);
    $pagos = $stPagos->fetchAll();

    $totales = [
        'efectivo' => 0, 'yape' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otros' => 0, 'general' => 0
    ];
    foreach($pagos as $p) {
        $metodo = $p['metodo'];
        $monto = floatval($p['total']);
        if ($metodo === 'efectivo') $totales['efectivo'] += $monto;
        elseif ($metodo === 'yape') $totales['yape'] += $monto;
        elseif ($metodo === 'transferencia') $totales['transferencia'] += $monto;
        elseif ($metodo === 'tarjeta') $totales['tarjeta'] += $monto;
        else $totales['otros'] += $monto;
        $totales['general'] += $monto;
    }

    // Calcular número de pedidos cobrados en este turno (basado en pagos.created_at para consistencia con totales)
    $stPedidos = $db->prepare("
        SELECT COUNT(DISTINCT p.pedido_id) FROM pagos p
        JOIN pedidos pe ON pe.id = p.pedido_id
        WHERE pe.restaurante_id = ? AND p.created_at >= ? AND p.anulado = 0
    ");
    $stPedidos->execute([$restauranteId, $turno['inicio']]);
    $numPedidos = $stPedidos->fetchColumn();
    $totales['num_pedidos'] = $numPedidos;

    // Cerrar turno con totales recalculados
    $stCerrar = $db->prepare("
        UPDATE turnos SET 
            fin = NOW(),
            total_efectivo = ?,
            total_yape = ?,
            total_transferencia = ?,
            total_tarjeta = ?,
            total_otros = ?,
            total_general = ?
        WHERE id = ?
    ");
    $stCerrar->execute([
        $totales['efectivo'],
        $totales['yape'],
        $totales['transferencia'],
        $totales['tarjeta'],
        $totales['otros'],
        $totales['general'],
        $turno['id']
    ]);

    $mensaje = 'Turno cerrado correctamente.';
    if ($reabrir) {
        // Auto-abrir siguiente turno inmediatamente
        $stNuevoTurno = $db->prepare("
            INSERT INTO turnos (restaurante_id, usuario_id, inicio)
            VALUES (?, ?, NOW())
        ");
        $stNuevoTurno->execute([$restauranteId, $usuarioId]);
        $mensaje = 'Turno cerrado y nueva caja abierta inmediatamente.';
    }

    $db->commit();
    jsonResponse(true, $totales, $mensaje);

} catch (PDOException $e) {
    $db->rollBack();
    error_log('Error al cerrar la caja: ' . $e->getMessage());
    jsonResponse(false, null, 'Ocurrió un error interno en el servidor.');
}
