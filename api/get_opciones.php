<?php
/**
 * api/get_opciones.php — Grupos de opciones de un producto
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$productoId = (int)($_GET['producto_id'] ?? 0);
if (!$productoId) jsonResponse(false, null, 'producto_id requerido');

$db = getDB();

// Grupos de opciones ordenados
$stGrupos = $db->prepare("
    SELECT id, nombre, orden, requerido
    FROM opciones_grupo
    WHERE producto_id = ?
    ORDER BY orden ASC
");
$stGrupos->execute([$productoId]);
$grupos = $stGrupos->fetchAll();

foreach ($grupos as &$grupo) {
    $stVal = $db->prepare("SELECT id, valor FROM opciones_valor WHERE grupo_id = ? ORDER BY id ASC");
    $stVal->execute([$grupo['id']]);
    $grupo['valores'] = $stVal->fetchAll();
}

jsonResponse(true, $grupos, 'OK');
