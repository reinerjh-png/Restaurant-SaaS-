<?php
/**
 * api/get_menu.php — Devuelve categorías y productos activos del restaurante
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$stCats = $db->prepare("
    SELECT id, nombre, icono, orden
    FROM categorias
    WHERE restaurante_id = ? AND activo = 1
    ORDER BY orden ASC, id ASC
");
$stCats->execute([$restauranteId]);
$categorias = $stCats->fetchAll();

$stProds = $db->prepare("
    SELECT id, categoria_id, nombre, descripcion, precio, tiene_opciones
    FROM productos
    WHERE restaurante_id = ? AND activo = 1
    ORDER BY categoria_id, nombre ASC
");
$stProds->execute([$restauranteId]);
$productos = $stProds->fetchAll();

// Agrupar productos por categoría
$catMap = [];
foreach ($categorias as &$cat) {
    $cat['productos'] = [];
    $catMap[$cat['id']] = &$cat;
}
foreach ($productos as $p) {
    if (isset($catMap[$p['categoria_id']])) {
        $catMap[$p['categoria_id']]['productos'][] = $p;
    }
}

jsonResponse(true, ['categorias' => $categorias, 'productos' => $productos], 'OK');
