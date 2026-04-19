<?php
/**
 * roles/admin/dashboard.php — Panel del Administrador
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// ── Métricas del día ─────────────────────────────────────────
$hoy = date('Y-m-d');

$stVentas = $db->prepare("
    SELECT COALESCE(SUM(total),0) AS total_dia, COUNT(*) AS pedidos_dia
    FROM pedidos
    WHERE restaurante_id = ? AND estado = 'cobrado' AND DATE(created_at) = ?
");
$stVentas->execute([$restauranteId, $hoy]);
$ventas = $stVentas->fetch();

$stMesas = $db->prepare("SELECT COUNT(*) AS ocupadas FROM mesas WHERE restaurante_id = ? AND estado = 'ocupada' AND activo = 1");
$stMesas->execute([$restauranteId]);
$mesasOcupadas = $stMesas->fetchColumn();

$stTotal = $db->prepare("SELECT COUNT(*) FROM mesas WHERE restaurante_id = ? AND activo = 1");
$stTotal->execute([$restauranteId]);
$totalMesas = $stTotal->fetchColumn();

$stActivos = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE restaurante_id = ? AND estado = 'activo'");
$stActivos->execute([$restauranteId]);
$pedidosActivos = $stActivos->fetchColumn();

$stMetodos = $db->prepare("
    SELECT pa.metodo, COALESCE(SUM(pa.monto),0) AS total
    FROM pagos pa
    JOIN pedidos pe ON pe.id = pa.pedido_id
    WHERE pe.restaurante_id = ? AND DATE(pa.created_at) = ?
    GROUP BY pa.metodo
");
$stMetodos->execute([$restauranteId, $hoy]);
$metodos = $stMetodos->fetchAll();
$metodoMap = array_column($metodos, 'total', 'metodo');

$stTop = $db->prepare("
    SELECT pr.nombre, SUM(pi.cantidad) AS vendidos, SUM(pi.subtotal) AS ingreso
    FROM pedido_items pi
    JOIN pedidos pe ON pe.id = pi.pedido_id
    JOIN productos pr ON pr.id = pi.producto_id
    WHERE pe.restaurante_id = ? AND pe.estado = 'cobrado' AND DATE(pe.created_at) = ?
    GROUP BY pr.id, pr.nombre
    ORDER BY vendidos DESC
    LIMIT 5
");
$stTop->execute([$restauranteId, $hoy]);
$topProductos = $stTop->fetchAll();

$stUltimos = $db->prepare("
    SELECT pe.id, pe.tipo, pe.total, pe.created_at,
           m.numero AS mesa_numero,
           u.nombre AS cajero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    JOIN usuarios u ON u.id = pe.usuario_id
    WHERE pe.restaurante_id = ? AND pe.estado = 'cobrado'
    ORDER BY pe.updated_at DESC
    LIMIT 8
");
$stUltimos->execute([$restauranteId]);
$ultimosPedidos = $stUltimos->fetchAll();

$stRest = $db->prepare("SELECT nombre FROM restaurantes WHERE id = ?");
$stRest->execute([$restauranteId]);
$restaurante = $stRest->fetch();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require_once '../../includes/header.php';
?>

<div class="layout-admin">
    <!-- Sidebar -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><span class="menu-icon"><i class="fa-solid fa-chart-bar"></i></span> Dashboard</a></li>
            <li><a href="mesas.php"><span class="menu-icon"><i class="fa-solid fa-chair"></i></span> Mesas</a></li>
            <li><a href="menu_categorias.php"><span class="menu-icon"><i class="fa-solid fa-folder"></i></span> Categorías</a></li>
            <li><a href="menu_productos.php"><span class="menu-icon"><i class="fa-solid fa-utensils"></i></span> Productos</a></li>
            <li><a href="usuarios.php"><span class="menu-icon"><i class="fa-solid fa-users"></i></span> Usuarios</a></li>
            <li><a href="reportes.php"><span class="menu-icon"><i class="fa-solid fa-chart-line"></i></span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon"><i class="fa-solid fa-clock-rotate-left"></i></span> Historial</a></li>
        </ul>
    </aside>

    <!-- Contenido principal -->
    <div class="main-content">
        <div class="page-content">

            <!-- Encabezado -->
            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1><i class="fa-solid fa-chart-bar" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Dashboard</h1>
                    <p><?= htmlspecialchars($restaurante['nombre'] ?? 'Restaurante') ?> · <?= date('d/m/Y') ?></p>
                </div>
                <div class="d-flex gap-8">
                    <a href="reportes.php" class="btn btn-ghost btn-sm">
                        <i class="fa-solid fa-chart-line"></i> Ver reportes
                    </a>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card verde">
                    <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="stat-valor">S/ <?= number_format($ventas['total_dia'], 2) ?></div>
                    <div class="stat-label">Ventas hoy</div>
                </div>
                <div class="stat-card rojo">
                    <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-valor"><?= $ventas['pedidos_dia'] ?></div>
                    <div class="stat-label">Pedidos cobrados</div>
                </div>
                <div class="stat-card naranja">
                    <div class="stat-icon"><i class="fa-solid fa-chair"></i></div>
                    <div class="stat-valor"><?= $mesasOcupadas ?> / <?= $totalMesas ?></div>
                    <div class="stat-label">Mesas ocupadas</div>
                </div>
                <div class="stat-card dorado">
                    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-valor"><?= $pedidosActivos ?></div>
                    <div class="stat-label">Pedidos activos</div>
                </div>
            </div>

            <!-- Ingresos por método de pago -->
            <div class="card mb-24">
                <div class="card-title"><i class="fa-solid fa-credit-card"></i> Ingresos por método de pago — hoy</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                    <?php
                    $metodoInfo = [
                        'efectivo'      => ['fa-money-bill-wave', 'Efectivo'],
                        'yape'          => ['fa-mobile-screen',   'Yape'],
                        'transferencia' => ['fa-building-columns','Transferencia'],
                        'tarjeta'       => ['fa-credit-card',     'Tarjeta'],
                        'otro'          => ['fa-rotate',          'Otro'],
                    ];
                    foreach ($metodoInfo as $key => [$icon, $label]): ?>
                    <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:14px 12px;text-align:center;border:1px solid var(--border);">
                        <div style="font-size:1.4rem;color:var(--primary);margin-bottom:6px;"><i class="fa-solid <?= $icon ?>"></i></div>
                        <div style="font-weight:800;font-size:1rem;margin:4px 0;color:var(--text-primary);">S/ <?= number_format($metodoMap[$key] ?? 0, 2) ?></div>
                        <div style="font-size:.72rem;color:var(--text-secondary);font-weight:500;"><?= $label ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="mb-24">
                <!-- Top productos -->
                <div class="card">
                    <div class="card-title"><i class="fa-solid fa-trophy"></i> Top productos hoy</div>
                    <?php if ($topProductos): ?>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <?php foreach ($topProductos as $i => $p): ?>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:26px;height:26px;background:<?= $i===0?'var(--warning)':($i===1?'var(--border)':'var(--bg-secondary)') ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:var(--text-primary);flex-shrink:0;"><?= $i+1 ?></div>
                            <div style="flex:1;">
                                <div style="font-size:.875rem;font-weight:600;color:var(--text-primary);"><?= htmlspecialchars($p['nombre']) ?></div>
                                <div style="font-size:.72rem;color:var(--text-secondary);"><?= $p['vendidos'] ?> vendidos</div>
                            </div>
                            <div style="font-size:.875rem;font-weight:700;color:var(--primary);">S/ <?= number_format($p['ingreso'],2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state" style="padding:30px 0;">
                        <div class="icon"><i class="fa-solid fa-utensils"></i></div>
                        <p>Sin ventas hoy aún</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Últimos pedidos -->
                <div class="card">
                    <div class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Últimos pedidos</div>
                    <?php if ($ultimosPedidos): ?>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($ultimosPedidos as $p): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);">
                            <span style="color:var(--text-muted);font-size:.875rem;">
                                <i class="fa-solid <?= $p['tipo']==='aqui' ? 'fa-house' : 'fa-bag-shopping' ?>"></i>
                            </span>
                            <div style="flex:1;">
                                <div style="font-size:.82rem;font-weight:600;color:var(--text-primary);">
                                    <?= $p['mesa_numero'] ? 'Mesa '.$p['mesa_numero'] : 'Para Llevar' ?>
                                </div>
                                <div style="font-size:.7rem;color:var(--text-secondary);"><?= htmlspecialchars($p['cajero']) ?> · <?= date('H:i', strtotime($p['created_at'])) ?></div>
                            </div>
                            <div style="font-weight:700;font-size:.88rem;color:var(--success);">S/ <?= number_format($p['total'],2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state" style="padding:30px 0;">
                        <div class="icon"><i class="fa-solid fa-receipt"></i></div>
                        <p>Sin pedidos cobrados hoy</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
