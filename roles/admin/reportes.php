<?php
/**
 * roles/admin/reportes.php — Reportes de ventas
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');

$stDias = $db->prepare("
    SELECT DATE(created_at) AS fecha,
           COUNT(*) AS pedidos,
           SUM(total) AS total
    FROM pedidos
    WHERE restaurante_id = ? AND estado = 'cobrado'
      AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY fecha DESC
");
$stDias->execute([$restauranteId, $fechaInicio, $fechaFin]);
$ventasDia = $stDias->fetchAll();

$stProds = $db->prepare("
    SELECT pr.nombre, SUM(pi.cantidad) AS vendidos, SUM(pi.subtotal) AS ingreso
    FROM pedido_items pi
    JOIN pedidos pe ON pe.id = pi.pedido_id
    JOIN productos pr ON pr.id = pi.producto_id
    WHERE pe.restaurante_id = ? AND pe.estado = 'cobrado'
      AND DATE(pe.created_at) BETWEEN ? AND ?
    GROUP BY pr.id, pr.nombre
    ORDER BY vendidos DESC
    LIMIT 20
");
$stProds->execute([$restauranteId, $fechaInicio, $fechaFin]);
$ventasProds = $stProds->fetchAll();

$stMetodos = $db->prepare("
    SELECT pa.metodo, SUM(pa.monto) AS total, COUNT(*) AS veces
    FROM pagos pa
    JOIN pedidos pe ON pe.id = pa.pedido_id
    WHERE pe.restaurante_id = ? AND DATE(pa.created_at) BETWEEN ? AND ?
    GROUP BY pa.metodo
");
$stMetodos->execute([$restauranteId, $fechaInicio, $fechaFin]);
$ventasMetodos = $stMetodos->fetchAll();

$totalGeneral = array_sum(array_column($ventasDia, 'total'));

$pageTitle  = 'Reportes';
$activeMenu = 'reportes';
require_once '../../includes/header.php';
?>

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon"><i class="fa-solid fa-chart-bar"></i></span> Dashboard</a></li>
            <li><a href="mesas.php"><span class="menu-icon"><i class="fa-solid fa-chair"></i></span> Mesas</a></li>
            <li><a href="menu_categorias.php"><span class="menu-icon"><i class="fa-solid fa-folder"></i></span> Categorías</a></li>
            <li><a href="menu_productos.php"><span class="menu-icon"><i class="fa-solid fa-utensils"></i></span> Productos</a></li>
            <li><a href="usuarios.php"><span class="menu-icon"><i class="fa-solid fa-users"></i></span> Usuarios</a></li>
            <li><a href="reportes.php" class="active"><span class="menu-icon"><i class="fa-solid fa-chart-line"></i></span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon"><i class="fa-solid fa-clock-rotate-left"></i></span> Historial</a></li>
            <li><a href="historial_comprobantes.php"><span class="menu-icon"><i class="fa-solid fa-file-invoice"></i></span> Comprobantes</a></li>
            <li><a href="config_facturacion.php"><span class="menu-icon"><i class="fa-solid fa-gear"></i></span> Facturación</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-16">
                <div>
                    <h1><i class="fa-solid fa-chart-line" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Reportes de Ventas</h1>
                    <p>Del <?= date('d/m/Y', strtotime($fechaInicio)) ?> al <?= date('d/m/Y', strtotime($fechaFin)) ?></p>
                </div>
            </div>

            <!-- Filtro de fechas -->
            <div class="card mb-24">
                <form method="GET" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="form-group" style="margin:0;flex:1;min-width:140px;">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $fechaInicio ?>">
                    </div>
                    <div class="form-group" style="margin:0;flex:1;min-width:140px;">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $fechaFin ?>">
                    </div>
                    <button type="submit" class="btn btn-primario">
                        <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                    </button>
                    <a href="reportes.php" class="btn btn-ghost">
                        <i class="fa-solid fa-rotate-left"></i> Este mes
                    </a>
                </form>
            </div>

            <!-- Total del período -->
            <div class="stats-grid mb-24">
                <div class="stat-card verde">
                    <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="stat-valor">S/ <?= number_format($totalGeneral, 2) ?></div>
                    <div class="stat-label">Total período</div>
                </div>
                <div class="stat-card rojo">
                    <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-valor"><?= array_sum(array_column($ventasDia, 'pedidos')) ?></div>
                    <div class="stat-label">Pedidos totales</div>
                </div>
                <?php
                $metodoMap2 = array_column($ventasMetodos, 'total', 'metodo');
                $metodos2 = [
                    ['efectivo','fa-money-bill-wave','Efectivo'],
                    ['yape','fa-mobile-screen','Yape'],
                    ['transferencia','fa-building-columns','Transferencia'],
                ];
                foreach ($metodos2 as [$key,$icon,$label]): ?>
                <div class="stat-card naranja">
                    <div class="stat-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                    <div class="stat-valor">S/ <?= number_format($metodoMap2[$key] ?? 0, 2) ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                <!-- Ventas por día -->
                <div class="card">
                    <div class="card-title"><i class="fa-solid fa-calendar-days"></i> Ventas por día</div>
                    <?php if ($ventasDia): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Fecha</th><th>Pedidos</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($ventasDia as $v): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                                    <td><?= $v['pedidos'] ?></td>
                                    <td><strong>S/ <?= number_format($v['total'],2) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state" style="padding:30px 0;">
                        <div class="icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <p>Sin datos en el período</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Top productos -->
                <div class="card">
                    <div class="card-title"><i class="fa-solid fa-trophy"></i> Top productos vendidos</div>
                    <?php if ($ventasProds): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Producto</th><th>Cant.</th><th>Ingreso</th></tr></thead>
                            <tbody>
                                <?php foreach ($ventasProds as $i => $p): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td><?= $p['vendidos'] ?></td>
                                    <td>S/ <?= number_format($p['ingreso'],2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state" style="padding:30px 0;">
                        <div class="icon"><i class="fa-solid fa-utensils"></i></div>
                        <p>Sin ventas en el período</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
