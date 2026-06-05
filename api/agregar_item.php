<?php
/**
 * api/agregar_item.php — Agrega productos a un pedido activo
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input    = json_decode(file_get_contents('php://input'), true);
$pedidoId = (int)($input['pedido_id'] ?? 0);
$items    = $input['items'] ?? [];
$restauranteId = $_SESSION['restaurante_id'];

if (!$pedidoId || empty($items)) jsonResponse(false, null, 'Datos incompletos');

$db = getDB();

try {
    $db->beginTransaction();

    // Verificar que el pedido existe y está activo
    $stPed = $db->prepare("SELECT id, total FROM pedidos WHERE id = ? AND restaurante_id = ? AND estado = 'activo'");
    $stPed->execute([$pedidoId, $restauranteId]);
    $pedido = $stPed->fetch();
    if (!$pedido) {
        $db->rollBack();
        jsonResponse(false, null, 'Pedido no encontrado o no activo');
    }

    $totalAgregado = 0;

    foreach ($items as $item) {
        $productoId = (int)($item['producto_id'] ?? 0);
        $cantidad   = max(1, (int)($item['cantidad'] ?? 1));
        $selecciones= $item['selecciones'] ?? [];
        $notas      = trim($item['notas'] ?? '');

        // Obtener precio actual del producto
        $stProd = $db->prepare("SELECT precio FROM productos WHERE id = ? AND restaurante_id = ? AND activo = 1");
        $stProd->execute([$productoId, $restauranteId]);
        $prod = $stProd->fetch();
        if (!$prod) continue;

        $precioUnitario = $prod['precio'];
        $subtotal       = $precioUnitario * $cantidad;

        // Insertar item
        $stItem = $db->prepare("
            INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal, notas, estado)
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stItem->execute([$pedidoId, $productoId, $cantidad, $precioUnitario, $subtotal, $notas ?: null]);
        $itemId = $db->lastInsertId();

        // Insertar opciones seleccionadas (validando que pertenecen a este producto)
        foreach ($selecciones as $sel) {
            $grupoId = (int)($sel['grupo_id'] ?? 0);
            $valorId = (int)($sel['valor_id'] ?? 0);
            if (!$grupoId || !$valorId) continue;

            // Verificar que el grupo pertenece al producto y el valor al grupo
            $stChkOpc = $db->prepare("
                SELECT ov.id
                FROM opciones_valor ov
                JOIN opciones_grupo og ON og.id = ov.grupo_id
                WHERE ov.id = ? AND ov.grupo_id = ? AND og.producto_id = ?
            ");
            $stChkOpc->execute([$valorId, $grupoId, $productoId]);
            if (!$stChkOpc->fetch()) continue; // ignorar opción no válida

            $stOpc = $db->prepare("INSERT INTO pedido_item_opciones (item_id, grupo_id, valor_id) VALUES (?,?,?)");
            $stOpc->execute([$itemId, $grupoId, $valorId]);
        }

        $totalAgregado += $subtotal;
    }

    // Actualizar total del pedido
    $stTotal = $db->prepare("UPDATE pedidos SET total = total + ? WHERE id = ?");
    $stTotal->execute([$totalAgregado, $pedidoId]);

    $db->commit();
    jsonResponse(true, ['total_agregado' => $totalAgregado], 'Items añadidos correctamente');

} catch (PDOException $e) {
    $db->rollBack();
    error_log('Error al agregar items: ' . $e->getMessage());
    jsonResponse(false, null, 'Ocurrió un error interno en el servidor.');
}
