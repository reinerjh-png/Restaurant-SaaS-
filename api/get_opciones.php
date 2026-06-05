<?php
/**
 * api/get_opciones.php — Grupos de opciones de un producto
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$productoId    = (int)($_GET['producto_id'] ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$productoId) jsonResponse(false, null, 'producto_id requerido');

$db = getDB();

// Verificar que el producto pertenece al restaurante actual
$stOwn = $db->prepare("SELECT id FROM productos WHERE id = ? AND restaurante_id = ? AND activo = 1");
$stOwn->execute([$productoId, $restauranteId]);
if (!$stOwn->fetch()) jsonResponse(false, null, 'Producto no válido');

// Grupos de opciones ordenados (join implícito ya garantizado por validación previa)
$stGrupos = $db->prepare("
    SELECT og.id, og.nombre, og.orden, og.requerido
    FROM opciones_grupo og
    JOIN productos p ON p.id = og.producto_id
    WHERE og.producto_id = ? AND p.restaurante_id = ?
    ORDER BY og.orden ASC
");
$stGrupos->execute([$productoId, $restauranteId]);
$grupos = $stGrupos->fetchAll();

foreach ($grupos as &$grupo) {
    $stVal = $db->prepare("SELECT id, valor FROM opciones_valor WHERE grupo_id = ? ORDER BY id ASC");
    $stVal->execute([$grupo['id']]);
    $grupo['valores'] = $stVal->fetchAll();
}

jsonResponse(true, $grupos, 'OK');
