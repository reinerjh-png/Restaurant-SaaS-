<?php
/**
 * api/get_reportes.php — Datos de reportes filtrados por fecha
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$fechaInicio   = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin      = $_GET['fecha_fin']    ?? date('Y-m-d');
$db = getDB();

// Validar fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) $fechaInicio = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin))    $fechaFin    = date('Y-m-d');

// Ventas por día
$stDias = $db->prepare("
    SELECT DATE(created_at) AS fecha, COUNT(*) AS pedidos, SUM(total) AS total
    FROM pedidos
    WHERE restaurante_id = ? AND estado = 'cobrado' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY fecha ASC
");
$stDias->execute([$restauranteId, $fechaInicio, $fechaFin]);
$ventasDia = $stDias->fetchAll();

// Ventas por producto
$stProds = $db->prepare("
    SELECT pr.nombre, SUM(pi.cantidad) AS vendidos, SUM(pi.subtotal) AS ingreso
    FROM pedido_items pi
    JOIN pedidos pe ON pe.id = pi.pedido_id
    JOIN productos pr ON pr.id = pi.producto_id
    WHERE pe.restaurante_id = ? AND pe.estado = 'cobrado' AND DATE(pe.created_at) BETWEEN ? AND ?
    GROUP BY pr.id, pr.nombre
    ORDER BY vendidos DESC
");
$stProds->execute([$restauranteId, $fechaInicio, $fechaFin]);
$ventasProds = $stProds->fetchAll();

// Ventas por método de pago
$stMetodos = $db->prepare("
    SELECT pa.metodo, SUM(pa.monto) AS total, COUNT(*) AS operaciones
    FROM pagos pa
    JOIN pedidos pe ON pe.id = pa.pedido_id
    WHERE pe.restaurante_id = ? AND DATE(pa.created_at) BETWEEN ? AND ?
    GROUP BY pa.metodo
    ORDER BY total DESC
");
$stMetodos->execute([$restauranteId, $fechaInicio, $fechaFin]);
$ventasMetodos = $stMetodos->fetchAll();

// Turno actual del usuario (si existe)
$stTurno = $db->prepare("
    SELECT * FROM turnos
    WHERE restaurante_id = ? AND usuario_id = ? AND fin IS NULL
    ORDER BY inicio DESC LIMIT 1
");
$stTurno->execute([$restauranteId, $_SESSION['usuario_id']]);
$turnoActual = $stTurno->fetch();

jsonResponse(true, [
    'ventas_dia'    => $ventasDia,
    'ventas_prods'  => $ventasProds,
    'ventas_metodos'=> $ventasMetodos,
    'total_general' => array_sum(array_column($ventasDia, 'total')),
    'turno_actual'  => $turnoActual,
], 'OK');
