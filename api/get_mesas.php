<?php
/**
 * api/get_mesas.php — Estado actual de mesas
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin', 'cocina']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$st = $db->prepare("
    SELECT m.id, m.numero, m.capacidad, m.estado,
           p.id AS pedido_id, p.total, p.tipo, p.created_at AS pedido_inicio,
           COUNT(pi.id) AS num_items
    FROM mesas m
    LEFT JOIN pedidos p ON p.mesa_id = m.id AND p.estado = 'activo'
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    WHERE m.restaurante_id = ? AND m.activo = 1
    GROUP BY m.id, p.id
    ORDER BY m.numero ASC
");
$st->execute([$restauranteId]);
$mesas = $st->fetchAll();

jsonResponse(true, $mesas, 'OK');
