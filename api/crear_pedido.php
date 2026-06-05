<?php
/**
 * api/crear_pedido.php — Crea un nuevo pedido y ocupa la mesa
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input    = json_decode(file_get_contents('php://input'), true);
$mesaId   = (int)($input['mesa_id'] ?? 0);
$tipo     = $input['tipo'] ?? '';
$usuarioId     = $_SESSION['usuario_id'];
$restauranteId = $_SESSION['restaurante_id'];

if (!in_array($tipo, ['aqui','llevar'])) jsonResponse(false, null, 'Tipo inválido');

$db = getDB();

try {
    $db->beginTransaction();

    // Verificar que haya un turno de caja activo
    $stTurno = $db->prepare("SELECT id FROM turnos WHERE restaurante_id = ? AND fin IS NULL LIMIT 1");
    $stTurno->execute([$restauranteId]);
    if (!$stTurno->fetch()) {
        $db->rollBack();
        jsonResponse(false, null, 'No se puede crear un pedido porque la caja está cerrada.');
    }

    // Verificar que la mesa esté libre
    if ($mesaId) {
        $stMesa = $db->prepare("SELECT estado FROM mesas WHERE id = ? AND restaurante_id = ? FOR UPDATE");
        $stMesa->execute([$mesaId, $restauranteId]);
        $mesa = $stMesa->fetch();
        if (!$mesa) {
            $db->rollBack();
            jsonResponse(false, null, 'Mesa no encontrada');
        }
        if ($mesa['estado'] === 'ocupada') {
            $db->rollBack();
            jsonResponse(false, null, 'La mesa ya tiene un pedido activo');
        }
    }

    // Crear pedido
    $stPed = $db->prepare("
        INSERT INTO pedidos (restaurante_id, mesa_id, usuario_id, tipo, estado, total)
        VALUES (?, ?, ?, ?, 'activo', 0.00)
    ");
    $stPed->execute([$restauranteId, $mesaId ?: null, $usuarioId, $tipo]);
    $pedidoId = $db->lastInsertId();

    // Marcar mesa como ocupada
    if ($mesaId) {
        $stActualizar = $db->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?");
        $stActualizar->execute([$mesaId]);
    }

    $db->commit();
    jsonResponse(true, ['pedido_id' => $pedidoId], 'Pedido creado correctamente');

} catch (PDOException $e) {
    $db->rollBack();
    error_log('Error al crear el pedido: ' . $e->getMessage());
    jsonResponse(false, null, 'Ocurrió un error interno en el servidor.');
}
