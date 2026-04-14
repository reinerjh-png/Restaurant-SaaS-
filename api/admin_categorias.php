<?php
/**
 * api/admin_categorias.php — CRUD de categorías del menú
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
        $nombre = trim($input['nombre'] ?? '');
        $icono  = trim($input['icono']  ?? '🍽️');
        $orden  = (int)($input['orden'] ?? 0);
        $activo = (int)($input['activo'] ?? 1);

        if (empty($nombre)) jsonResponse(false, null, 'El nombre es requerido');

        $st = $db->prepare("INSERT INTO categorias (restaurante_id, nombre, icono, orden, activo) VALUES (?,?,?,?,?)");
        $st->execute([$restauranteId, $nombre, $icono, $orden, $activo]);
        jsonResponse(true, ['id' => $db->lastInsertId()], "Categoría \"$nombre\" creada correctamente");

    case 'editar':
        $id     = (int)($input['id']     ?? 0);
        $nombre = trim($input['nombre']  ?? '');
        $icono  = trim($input['icono']   ?? '🍽️');
        $orden  = (int)($input['orden']  ?? 0);
        $activo = (int)($input['activo'] ?? 1);

        if (!$id || empty($nombre)) jsonResponse(false, null, 'Datos inválidos');

        $st = $db->prepare("UPDATE categorias SET nombre=?, icono=?, orden=?, activo=? WHERE id=? AND restaurante_id=?");
        $st->execute([$nombre, $icono, $orden, $activo, $id, $restauranteId]);
        jsonResponse(true, null, "Categoría \"$nombre\" actualizada correctamente");

    case 'eliminar':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'ID requerido');

        // Verificar productos activos en la categoría
        $stProd = $db->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ? AND activo = 1");
        $stProd->execute([$id]);
        if ($stProd->fetchColumn() > 0) {
            jsonResponse(false, null, 'No se puede eliminar: la categoría tiene productos activos');
        }

        $st = $db->prepare("DELETE FROM categorias WHERE id=? AND restaurante_id=?");
        $st->execute([$id, $restauranteId]);
        jsonResponse(true, null, 'Categoría eliminada correctamente');

    default:
        jsonResponse(false, null, 'Acción no reconocida');
}
