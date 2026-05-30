<?php
/**
 * api/guardar_comprobante.php
 * Guarda el comprobante (boleta o factura) al confirmar un cobro.
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input = json_decode(file_get_contents('php://input'), true);

$pedidoId      = (int)($input['pedido_id']       ?? 0);
$tipo          = strtolower(trim($input['tipo']  ?? '')); // boleta | factura | simple
$tipoDoc       = strtolower(trim($input['tipo_documento'] ?? '')) ?: null; // dni | ruc | null (simple)
$numDoc        = preg_replace('/\D/', '', trim($input['numero_documento'] ?? '')) ?: null;
$nombreCliente = trim($input['nombre_cliente']   ?? '');
$direccion     = trim($input['direccion']         ?? '');
$distrito      = trim($input['distrito']          ?? '');
$provincia     = trim($input['provincia']         ?? '');
$departamento  = trim($input['departamento']      ?? '');
$pagos         = $input['pagos']                  ?? [];
$notas         = trim($input['notas']             ?? '');
$descuento     = max(0, floatval($input['descuento'] ?? 0));
$cargo_extra   = max(0, floatval($input['cargo_extra'] ?? 0));

$usuarioId     = $_SESSION['usuario_id'];
$restauranteId = $_SESSION['restaurante_id'];

// ── Validaciones ────────────────────────────────────────────────
if (!$pedidoId) jsonResponse(false, null, 'Pedido inválido.');
if (!in_array($tipo, ['boleta', 'factura', 'simple'])) jsonResponse(false, null, 'Tipo de comprobante inválido.');
if (empty($pagos))  jsonResponse(false, null, 'No se registraron métodos de pago.');

// Validaciones específicas para boleta/factura (no aplican a comprobante simple)
if ($tipo !== 'simple') {
    if (!in_array($tipoDoc, ['dni', 'ruc'])) jsonResponse(false, null, 'Tipo de documento inválido.');
    if ($tipoDoc === 'dni' && strlen($numDoc) !== 8)  jsonResponse(false, null, 'DNI debe tener 8 dígitos.');
    if ($tipoDoc === 'ruc' && strlen($numDoc) !== 11) jsonResponse(false, null, 'RUC debe tener 11 dígitos.');
    if (empty($nombreCliente)) jsonResponse(false, null, 'El nombre/razón social es requerido.');
}

$db = getDB();

try {
    $db->beginTransaction();

    // 1. Verificar pedido activo
    $stPed = $db->prepare("
        SELECT pe.*, m.numero AS mesa_numero
        FROM pedidos pe
        LEFT JOIN mesas m ON m.id = pe.mesa_id
        WHERE pe.id = ? AND pe.restaurante_id = ? AND pe.estado = 'activo'
        FOR UPDATE
    ");
    $stPed->execute([$pedidoId, $restauranteId]);
    $pedido = $stPed->fetch();
    if (!$pedido) {
        $db->rollBack();
        jsonResponse(false, null, 'Pedido no encontrado o ya cobrado.');
    }

    $totalOriginal = floatval($pedido['total']);
    $totalFinal = max(0, $totalOriginal + $cargo_extra - $descuento);

    // 2. Obtener configuración de facturación (crea si no existe)
    $stCfg = $db->prepare("SELECT * FROM facturacion_config WHERE restaurante_id = ? FOR UPDATE");
    $stCfg->execute([$restauranteId]);
    $cfg = $stCfg->fetch();

    if (!$cfg) {
        $db->prepare("INSERT INTO facturacion_config (restaurante_id) VALUES (?)")->execute([$restauranteId]);
        $stCfg->execute([$restauranteId]);
        $cfg = $stCfg->fetch();
    }

    // 3. Incrementar correlativo y obtener número de comprobante
    // El comprobante simple comparte la serie/correlativo de boleta
    $campoCorrel  = $tipo === 'factura' ? 'correlativo_factura' : 'correlativo_boleta';
    $campoSerie   = $tipo === 'factura' ? 'serie_factura'       : 'serie_boleta';
    $nuevoCorrel  = intval($cfg[$campoCorrel]) + 1;
    $serie        = $cfg[$campoSerie] ?: ($tipo === 'factura' ? 'F001' : 'B001');
    $numComp      = $serie . '-' . str_pad($nuevoCorrel, 5, '0', STR_PAD_LEFT);

    $stActCfg = $db->prepare("UPDATE facturacion_config SET $campoCorrel = ? WHERE restaurante_id = ?");
    $stActCfg->execute([$nuevoCorrel, $restauranteId]);

    // 4. Obtener items del pedido para snapshot JSON
    $stItems = $db->prepare("
        SELECT pi.cantidad, pi.precio_unitario, pi.subtotal,
               pr.nombre AS producto_nombre,
               GROUP_CONCAT(ov.valor SEPARATOR ' · ') AS opciones_texto
        FROM pedido_items pi
        JOIN productos pr ON pr.id = pi.producto_id
        LEFT JOIN pedido_item_opciones pio ON pio.item_id = pi.id
        LEFT JOIN opciones_valor ov ON ov.id = pio.valor_id
        WHERE pi.pedido_id = ?
        GROUP BY pi.id
        ORDER BY pi.created_at ASC
    ");
    $stItems->execute([$pedidoId]);
    $items = $stItems->fetchAll();

    // 5. Calcular subtotal e IGV (18%)
    $subtotal = round($totalFinal / 1.18, 2);
    $igv      = round($totalFinal - $subtotal, 2);

    // 6. Insertar comprobante
    $stComp = $db->prepare("
        INSERT INTO comprobantes
            (restaurante_id, pedido_id, usuario_id, tipo, serie, correlativo, numero_comprobante,
             tipo_documento, numero_documento, nombre_cliente, direccion_cliente,
             distrito, provincia, departamento,
             subtotal, igv, total, descuento, cargo_extra, items_json, pagos_json)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stComp->execute([
        $restauranteId,
        $pedidoId,
        $usuarioId,
        $tipo,
        $serie,
        $nuevoCorrel,
        $numComp,
        $tipoDoc  ?: null,
        $numDoc   ?: null,
        $nombreCliente ?: 'Cliente',
        $direccion    ?: null,
        $distrito     ?: null,
        $provincia    ?: null,
        $departamento ?: null,
        $subtotal,
        $igv,
        $totalFinal,
        $descuento,
        $cargo_extra,
        json_encode($items, JSON_UNESCAPED_UNICODE),
        json_encode($pagos, JSON_UNESCAPED_UNICODE),
    ]);
    $comprobanteId = $db->lastInsertId();

    // 7. Registrar pagos
    $metodosValidos = ['efectivo','yape','transferencia','tarjeta','otro'];
    $totalPagado    = 0;
    foreach ($pagos as $pago) {
        $metodo    = $pago['metodo']    ?? '';
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
        jsonResponse(false, null, 'No se registraron pagos válidos.');
    }

    // 8. Marcar pedido como cobrado, guardando descuento y total final
    $stCobrar = $db->prepare("UPDATE pedidos SET estado = 'cobrado', notas = ?, descuento = ?, cargo_extra = ?, total = ? WHERE id = ?");
    $stCobrar->execute([$notas ?: null, $descuento, $cargo_extra, $totalFinal, $pedidoId]);

    if ($pedido['mesa_id']) {
        $db->prepare("UPDATE mesas SET estado = 'libre' WHERE id = ?")->execute([$pedido['mesa_id']]);
    }

    // 9. Actualizar turno activo
    $stTurno = $db->prepare("
        SELECT id FROM turnos
        WHERE restaurante_id = ? AND usuario_id = ? AND fin IS NULL
        ORDER BY inicio DESC LIMIT 1
    ");
    $stTurno->execute([$restauranteId, $usuarioId]);
    $turno = $stTurno->fetch();
    if ($turno) {
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
            $turno['id'],
        ]);
    }

    $db->commit();

    jsonResponse(true, [
        'comprobante_id'    => $comprobanteId,
        'numero_comprobante'=> $numComp,
        'tipo'              => $tipo,
        'total_cobrado'     => $totalPagado,
    ], '¡Comprobante emitido y cobro registrado!');

} catch (PDOException $e) {
    $db->rollBack();
    jsonResponse(false, null, 'Error al guardar el comprobante: ' . $e->getMessage());
}
