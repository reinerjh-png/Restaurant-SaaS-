<?php
/**
 * api/admin_mesas.php — CRUD de mesas (admin)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$accion        = $input['accion'] ?? '';
$restauranteId = $_SESSION['restaurante_id'];
$db            = getDB();

switch ($accion) {

    case 'crear':
        $numero    = (int)($input['numero']    ?? 0);
        $capacidad = (int)($input['capacidad'] ?? 4);
        $activo    = !empty($input['activo']) ? 1 : 0;

        if ($numero < 1 || $numero > 9999) jsonResponse(false, null, 'Número de mesa inválido');
        if ($capacidad < 1 || $capacidad > 200) jsonResponse(false, null, 'Capacidad inválida');

        // Verificar duplicado
        $stDup = $db->prepare("SELECT id FROM mesas WHERE restaurante_id = ? AND numero = ?");
        $stDup->execute([$restauranteId, $numero]);
        if ($stDup->fetch()) jsonResponse(false, null, "Ya existe la Mesa $numero");

        $st = $db->prepare("INSERT INTO mesas (restaurante_id, numero, capacidad, activo) VALUES (?,?,?,?)");
        $st->execute([$restauranteId, $numero, $capacidad, $activo]);
        jsonResponse(true, ['id' => $db->lastInsertId()], "Mesa $numero creada correctamente");

    case 'editar':
        $id        = (int)($input['id']        ?? 0);
        $numero    = (int)($input['numero']    ?? 0);
        $capacidad = (int)($input['capacidad'] ?? 4);
        $activo    = !empty($input['activo']) ? 1 : 0;

        if (!$id || $numero < 1 || $numero > 9999) jsonResponse(false, null, 'Datos inválidos');
        if ($capacidad < 1 || $capacidad > 200) jsonResponse(false, null, 'Capacidad inválida');

        // Verificar duplicado excluyendo la propia mesa
        $stDup = $db->prepare("SELECT id FROM mesas WHERE restaurante_id = ? AND numero = ? AND id != ?");
        $stDup->execute([$restauranteId, $numero, $id]);
        if ($stDup->fetch()) jsonResponse(false, null, "Ya existe otra Mesa con el número $numero");

        $st = $db->prepare("UPDATE mesas SET numero=?, capacidad=?, activo=? WHERE id=? AND restaurante_id=?");
        $st->execute([$numero, $capacidad, $activo, $id, $restauranteId]);
        jsonResponse(true, null, "Mesa $numero actualizada correctamente");

    case 'eliminar':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'ID requerido');

        // Verificar que no tenga pedidos activos
        $stPed = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE mesa_id = ? AND restaurante_id = ? AND estado = 'activo'");
        $stPed->execute([$id, $restauranteId]);
        if ($stPed->fetchColumn() > 0) jsonResponse(false, null, 'No se puede eliminar: la mesa tiene un pedido activo');

        $st = $db->prepare("DELETE FROM mesas WHERE id=? AND restaurante_id=?");
        $st->execute([$id, $restauranteId]);
        jsonResponse(true, null, 'Mesa eliminada correctamente');

    default:
        jsonResponse(false, null, 'Acción no reconocida');
}
