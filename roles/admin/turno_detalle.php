<?php
/**
 * roles/admin/turno_detalle.php
 * Vista detallada de un turno cerrado.
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$turnoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$turnoId) {
    header('Location: caja.php');
    exit;
}

// Obtener detalles del turno
$stTurno = $db->prepare("
    SELECT t.*, u.nombre AS abierto_por
    FROM turnos t
    JOIN usuarios u ON u.id = t.usuario_id
    WHERE t.id = ? AND t.restaurante_id = ? AND t.fin IS NOT NULL
");
$stTurno->execute([$turnoId, $restauranteId]);
$turno = $stTurno->fetch();

if (!$turno) {
    header('Location: caja.php');
    exit;
}

// Obtener gastos del turno
$stGastos = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE restaurante_id = ? AND created_at BETWEEN ? AND ? AND activo = 1");
$stGastos->execute([$restauranteId, $turno['inicio'], $turno['fin']]);
$gastosTotal = $stGastos->fetchColumn();

// Obtener num_pedidos
$stPedidos = $db->prepare("SELECT COUNT(id) FROM pedidos WHERE restaurante_id = ? AND estado = 'cobrado' AND updated_at BETWEEN ? AND ?");
$stPedidos->execute([$restauranteId, $turno['inicio'], $turno['fin']]);
$numPedidos = $stPedidos->fetchColumn();

// Desglose por usuario
$stUsuarios = $db->prepare("
    SELECT 
        u.id,
        u.nombre AS usuario,
        SUM(CASE WHEN p.metodo = 'efectivo' THEN p.monto ELSE 0 END) AS total_efectivo,
        SUM(CASE WHEN p.metodo = 'yape' THEN p.monto ELSE 0 END) AS total_yape,
        SUM(CASE WHEN p.metodo = 'transferencia' THEN p.monto ELSE 0 END) AS total_transferencia,
        SUM(CASE WHEN p.metodo = 'tarjeta' THEN p.monto ELSE 0 END) AS total_tarjeta,
        SUM(CASE WHEN p.metodo = 'otros' THEN p.monto ELSE 0 END) AS total_otros,
        SUM(p.monto) AS total_general
    FROM pagos p
    JOIN pedidos pe ON pe.id = p.pedido_id
    JOIN usuarios u ON u.id = pe.usuario_id
    WHERE pe.restaurante_id = ? AND p.created_at BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_general DESC
");
$stUsuarios->execute([$restauranteId, $turno['inicio'], $turno['fin']]);
$desgloseUsuarios = $stUsuarios->fetchAll();

// Gastos por usuario
$stGastosUsuarios = $db->prepare("
    SELECT usuario_id, COALESCE(SUM(monto), 0) AS total_gastos 
    FROM gastos 
    WHERE restaurante_id = ? AND created_at BETWEEN ? AND ? AND activo = 1 
    GROUP BY usuario_id
");
$stGastosUsuarios->execute([$restauranteId, $turno['inicio'], $turno['fin']]);
$gastosPorUsuario = $stGastosUsuarios->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Detalles del Turno';
$activeMenu = 'caja';
require_once '../../includes/header.php';
?>

<div class="page-content">
    <div style="margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <a href="caja.php" class="btn btn-ghost" style="padding:8px;border-radius:50%;"><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <h1 style="font-size:1.55rem;font-weight:800;margin-bottom:4px;">
                <i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary);margin-right:8px;"></i>Detalles del Turno #<?= $turno['id'] ?>
            </h1>
            <p style="color:var(--text-secondary);font-size:.88rem;">
                <?= date('d/m/Y H:i', strtotime($turno['inicio'])) ?> &mdash; <?= date('d/m/Y H:i', strtotime($turno['fin'])) ?> (Abierto por <?= htmlspecialchars($turno['abierto_por']) ?>)
            </p>
        </div>
    </div>

    <!-- Panel de Resumen -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px;">
        <!-- Ingresos -->
        <div class="card" style="border-left:4px solid var(--success);padding:16px;">
            <div style="color:var(--success);margin-bottom:8px;"><i class="fa-solid fa-sack-dollar"></i></div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">S/ <?= number_format($turno['total_general'], 2) ?></div>
            <div style="font-size:.85rem;color:var(--text-secondary);">Ingresos Totales (<?= $numPedidos ?> pedidos)</div>
        </div>
        <!-- Gastos -->
        <div class="card" style="border-left:4px solid var(--danger);padding:16px;">
            <div style="color:var(--danger);margin-bottom:8px;"><i class="fa-solid fa-money-bill-transfer"></i></div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">S/ <?= number_format($gastosTotal, 2) ?></div>
            <div style="font-size:.85rem;color:var(--text-secondary);">Gastos del Turno</div>
        </div>
        <!-- Utilidad -->
        <div class="card" style="border-left:4px solid var(--primary);padding:16px;background:var(--bg-secondary);">
            <div style="color:var(--primary);margin-bottom:8px;"><i class="fa-solid fa-piggy-bank"></i></div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">S/ <?= number_format($turno['total_general'] - $gastosTotal, 2) ?></div>
            <div style="font-size:.85rem;color:var(--text-secondary);">Utilidad del Turno</div>
        </div>
    </div>

    <div class="card mb-24" style="padding:16px;">
        <div style="font-size:.85rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
            <i class="fa-solid fa-credit-card"></i> Ingresos por método de pago (General)
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(120px, 1fr));gap:12px;">
            <?php
            $listaMetodos = [
                'total_efectivo'      => ['Efectivo', 'fa-money-bill-1'],
                'total_yape'          => ['Yape / Plin', 'fa-mobile-screen'],
                'total_transferencia' => ['Transferencia', 'fa-building-columns'],
                'total_tarjeta'       => ['Tarjeta', 'fa-credit-card'],
                'total_otros'         => ['Otro', 'fa-wallet']
            ];
            foreach ($listaMetodos as $k => $m):
                $val = floatval($turno[$k]);
            ?>
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;text-align:center;">
                <i class="fa-solid <?= $m[1] ?>" style="color:var(--text-secondary);font-size:1.2rem;margin-bottom:6px;"></i>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">S/ <?= number_format($val, 2) ?></div>
                <div style="font-size:.75rem;color:var(--text-muted);"><?= $m[0] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-title"><i class="fa-solid fa-users"></i> Desglose por Usuario</div>
        <?php if ($desgloseUsuarios): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Cajero / Mesero</th>
                        <th class="text-right">Efectivo</th>
                        <th class="text-right">Yape/Plin</th>
                        <th class="text-right">Transferencia</th>
                        <th class="text-right">Tarjeta</th>
                        <th class="text-right">Otros</th>
                        <th class="text-right" style="color:var(--success);">Ingresos</th>
                        <th class="text-right" style="color:var(--danger);">Gastos</th>
                        <th class="text-right" style="color:#0ea5e9;">Utilidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($desgloseUsuarios as $du): 
                        $gastoU = floatval($gastosPorUsuario[$du['id']] ?? 0);
                        $utilidadU = floatval($du['total_general']) - $gastoU;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($du['usuario']) ?></strong></td>
                        <td class="text-right">S/ <?= number_format($du['total_efectivo'], 2) ?></td>
                        <td class="text-right">S/ <?= number_format($du['total_yape'], 2) ?></td>
                        <td class="text-right">S/ <?= number_format($du['total_transferencia'], 2) ?></td>
                        <td class="text-right">S/ <?= number_format($du['total_tarjeta'], 2) ?></td>
                        <td class="text-right">S/ <?= number_format($du['total_otros'], 2) ?></td>
                        <td class="text-right fw-700" style="color:var(--success);">S/ <?= number_format($du['total_general'], 2) ?></td>
                        <td class="text-right fw-700" style="color:var(--danger);">S/ <?= number_format($gastoU, 2) ?></td>
                        <td class="text-right fw-700" style="color:#0ea5e9;">S/ <?= number_format($utilidadU, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon"><i class="fa-solid fa-users-slash"></i></div>
            <p>No se registraron ventas en este turno.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>
