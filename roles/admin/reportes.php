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

// Rango de fechas
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');

// Ventas por día
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

// Ventas por producto
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

// Ventas por método de pago
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
            <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
            <li><a href="mesas.php"><span class="menu-icon">🪑</span> Mesas</a></li>
            <li><a href="menu_categorias.php"><span class="menu-icon">📂</span> Categorías</a></li>
            <li><a href="menu_productos.php"><span class="menu-icon">🍽️</span> Productos</a></li>
            <li><a href="usuarios.php"><span class="menu-icon">👥</span> Usuarios</a></li>
            <li><a href="reportes.php" class="active"><span class="menu-icon">📈</span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon">🗂️</span> Historial</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-16">
                <div>
                    <h1>📈 Reportes de Ventas</h1>
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
                    <button type="submit" class="btn btn-primario">🔍 Filtrar</button>
                    <a href="reportes.php" class="btn btn-ghost">↺ Este mes</a>
                </form>
            </div>

            <!-- Total del período -->
            <div class="stats-grid mb-24">
                <div class="stat-card verde">
                    <div class="stat-icon">💰</div>
                    <div class="stat-valor">S/ <?= number_format($totalGeneral, 2) ?></div>
                    <div class="stat-label">Total período</div>
                </div>
                <div class="stat-card rojo">
                    <div class="stat-icon">🧾</div>
                    <div class="stat-valor"><?= array_sum(array_column($ventasDia, 'pedidos')) ?></div>
                    <div class="stat-label">Pedidos totales</div>
                </div>
                <?php
                $metodoMap = array_column($ventasMetodos, 'total', 'metodo');
                $metodos = [
                    ['efectivo','💵','Efectivo'],['yape','📱','Yape'],['transferencia','🏦','Transf.']
                ];
                foreach ($metodos as [$key,$icon,$label]): ?>
                <div class="stat-card naranja">
                    <div class="stat-icon"><?= $icon ?></div>
                    <div class="stat-valor">S/ <?= number_format($metodoMap[$key] ?? 0, 2) ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                <!-- Ventas por día -->
                <div class="card">
                    <div class="card-title">📅 Ventas por día</div>
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
                    <div class="empty-state" style="padding:30px 0;"><div class="icon">📅</div><p>Sin datos en el período</p></div>
                    <?php endif; ?>
                </div>

                <!-- Top productos -->
                <div class="card">
                    <div class="card-title">🏆 Top productos vendidos</div>
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
                    <div class="empty-state" style="padding:30px 0;"><div class="icon">🍽️</div><p>Sin ventas en el período</p></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
