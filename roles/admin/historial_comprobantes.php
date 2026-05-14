<?php
/**
 * roles/admin/historial_comprobantes.php
 * Historial de comprobantes con filtros, búsqueda y anulación.
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// ── Filtros ──────────────────────────────────────────────────────
$fechaDesde  = $_GET['desde']   ?? date('Y-m-01');
$fechaHasta  = $_GET['hasta']   ?? date('Y-m-d');
$tipoFiltro  = $_GET['tipo']    ?? '';    // boleta | factura | ''
$busqDoc     = trim($_GET['doc'] ?? '');  // búsqueda por nro documento
$soloAnulados = !empty($_GET['anulados']);

// ── Consulta ─────────────────────────────────────────────────────
$where = ['c.restaurante_id = ?'];
$params = [$restauranteId];

$where[] = 'DATE(c.created_at) >= ?'; $params[] = $fechaDesde;
$where[] = 'DATE(c.created_at) <= ?'; $params[] = $fechaHasta;

if (in_array($tipoFiltro, ['boleta','factura'])) {
    $where[] = 'c.tipo = ?'; $params[] = $tipoFiltro;
}
if ($busqDoc) {
    $where[] = 'c.numero_documento LIKE ?'; $params[] = '%' . $busqDoc . '%';
}
if ($soloAnulados) {
    $where[] = 'c.anulado = 1';
} else {
    // por defecto no filtrar por estado
}

$sql = "
    SELECT c.*, u.nombre AS cajero_nombre
    FROM comprobantes c
    JOIN usuarios u ON u.id = c.usuario_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.created_at DESC
    LIMIT 200
";
$st = $db->prepare($sql);
$st->execute($params);
$comprobantes = $st->fetchAll();

// ── Totales del período ──────────────────────────────────────────
$stTotales = $db->prepare("
    SELECT
        COUNT(*) AS total_comp,
        COALESCE(SUM(CASE WHEN anulado = 0 THEN total ELSE 0 END), 0) AS total_monto,
        COALESCE(SUM(CASE WHEN tipo = 'boleta'  AND anulado = 0 THEN 1 ELSE 0 END), 0) AS total_boletas,
        COALESCE(SUM(CASE WHEN tipo = 'factura' AND anulado = 0 THEN 1 ELSE 0 END), 0) AS total_facturas
    FROM comprobantes
    WHERE restaurante_id = ? AND DATE(created_at) BETWEEN ? AND ? AND (" . (!empty($busqDoc) ? "numero_documento LIKE ?" : "1=1") . ")
");
$pTotales = [$restauranteId, $fechaDesde, $fechaHasta];
if ($busqDoc) $pTotales[] = '%' . $busqDoc . '%';
$stTotales->execute($pTotales);
$totales = $stTotales->fetch();

$pageTitle  = 'Historial de Comprobantes';
$activeMenu = 'comprobantes';
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
            <li><a href="reportes.php"><span class="menu-icon"><i class="fa-solid fa-chart-line"></i></span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon"><i class="fa-solid fa-clock-rotate-left"></i></span> Historial</a></li>
            <li><a href="historial_comprobantes.php" class="active"><span class="menu-icon"><i class="fa-solid fa-file-invoice"></i></span> Comprobantes</a></li>
            <li><a href="config_facturacion.php"><span class="menu-icon"><i class="fa-solid fa-store"></i></span> Mi Restaurante</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-20">
                <div>
                    <h1><i class="fa-solid fa-file-invoice" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Comprobantes</h1>
                    <p>Boletas y facturas emitidas</p>
                </div>
                <a href="config_facturacion.php" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-gear"></i> Configurar
                </a>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="stats-grid mb-20" style="grid-template-columns:repeat(4,1fr);">
                <div class="stat-card verde">
                    <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="stat-valor">S/ <?= number_format($totales['total_monto'], 2) ?></div>
                    <div class="stat-label">Total emitido</div>
                </div>
                <div class="stat-card dorado">
                    <div class="stat-icon"><i class="fa-solid fa-file-invoice"></i></div>
                    <div class="stat-valor"><?= $totales['total_comp'] ?></div>
                    <div class="stat-label">Comprobantes</div>
                </div>
                <div class="stat-card naranja">
                    <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-valor"><?= $totales['total_boletas'] ?></div>
                    <div class="stat-label">Boletas</div>
                </div>
                <div class="stat-card rojo">
                    <div class="stat-icon"><i class="fa-solid fa-file-contract"></i></div>
                    <div class="stat-valor"><?= $totales['total_facturas'] ?></div>
                    <div class="stat-label">Facturas</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-20">
                <form method="GET" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;">
                    <div>
                        <label class="form-label">Desde</label>
                        <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fechaDesde) ?>">
                    </div>
                    <div>
                        <label class="form-label">Hasta</label>
                        <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fechaHasta) ?>">
                    </div>
                    <div>
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="boleta"  <?= $tipoFiltro === 'boleta'  ? 'selected' : '' ?>>Boleta</option>
                            <option value="factura" <?= $tipoFiltro === 'factura' ? 'selected' : '' ?>>Factura</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">DNI / RUC cliente</label>
                        <input type="text" name="doc" class="form-control" value="<?= htmlspecialchars($busqDoc) ?>" placeholder="Buscar por documento...">
                    </div>
                    <button type="submit" class="btn btn-primario" style="height:40px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>

            <!-- Tabla -->
            <div class="card">
                <?php if ($comprobantes): ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border);">
                                <th style="text-align:left;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Nº Comprobante</th>
                                <th style="text-align:left;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Tipo</th>
                                <th style="text-align:left;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Cliente</th>
                                <th style="text-align:left;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Documento</th>
                                <th style="text-align:right;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Total</th>
                                <th style="text-align:left;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Cajero</th>
                                <th style="text-align:left;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Fecha</th>
                                <th style="text-align:center;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Estado</th>
                                <th style="text-align:center;padding:10px 12px;color:var(--text-secondary);font-weight:600;font-size:.8rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comprobantes as $c): ?>
                            <tr style="border-bottom:1px solid var(--border);<?= $c['anulado'] ? 'opacity:.5;' : '' ?>">
                                <td style="padding:10px 12px;font-weight:700;color:var(--primary);">
                                    <?= htmlspecialchars($c['numero_comprobante']) ?>
                                </td>
                                <td style="padding:10px 12px;">
                                    <span style="padding:3px 8px;border-radius:20px;font-size:.75rem;font-weight:600;
                                        background:<?= $c['tipo']==='boleta' ? 'rgba(34,197,94,.15)' : 'rgba(99,102,241,.15)' ?>;
                                        color:<?= $c['tipo']==='boleta' ? 'var(--success)' : 'var(--primary)' ?>;">
                                        <?= ucfirst($c['tipo']) ?>
                                    </span>
                                </td>
                                <td style="padding:10px 12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars($c['nombre_cliente']) ?>
                                </td>
                                <td style="padding:10px 12px;font-family:monospace;font-size:.82rem;">
                                    <span style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;"><?= $c['tipo_documento'] ?>:</span>
                                    <?= htmlspecialchars($c['numero_documento']) ?>
                                </td>
                                <td style="padding:10px 12px;text-align:right;font-weight:700;color:var(--success);">
                                    S/ <?= number_format($c['total'], 2) ?>
                                </td>
                                <td style="padding:10px 12px;color:var(--text-secondary);font-size:.82rem;">
                                    <?= htmlspecialchars($c['cajero_nombre']) ?>
                                </td>
                                <td style="padding:10px 12px;color:var(--text-secondary);font-size:.82rem;white-space:nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                                </td>
                                <td style="padding:10px 12px;text-align:center;">
                                    <?php if ($c['anulado']): ?>
                                    <span style="color:var(--danger);font-size:.75rem;font-weight:700;">ANULADO</span>
                                    <?php else: ?>
                                    <span style="color:var(--success);font-size:.75rem;font-weight:700;">Válido</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px 12px;text-align:center;">
                                    <div style="display:flex;gap:6px;justify-content:center;">
                                        <a href="ver_comprobante.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm" title="Ver detalle">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <?php if (!$c['anulado']): ?>
                                        <button class="btn btn-peligro btn-sm"
                                            onclick="anularComprobante(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['numero_comprobante'])) ?>')"
                                            title="Anular">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state" style="padding:48px 0;">
                    <div class="icon"><i class="fa-solid fa-file-invoice"></i></div>
                    <p>No se encontraron comprobantes con los filtros aplicados.</p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Modal anulación -->
<div class="modal-overlay" id="modal-anular">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-ban" style="color:var(--danger);"></i> Anular comprobante</div>
        </div>
        <div class="modal-body">
            <p id="anular-msg" style="color:var(--text-primary);margin-bottom:12px;"></p>
            <div>
                <label class="form-label">Motivo de anulación <span style="color:var(--danger);">*</span></label>
                <input type="text" id="anular-motivo" class="form-control" placeholder="Ej: Error en datos del cliente, duplicado...">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-anular')">Cancelar</button>
            <button class="btn btn-peligro btn-full" id="btn-confirmar-anular" onclick="confirmarAnulacion()">
                <i class="fa-solid fa-ban"></i> Anular
            </button>
        </div>
    </div>
</div>

<script>
let anularId = null;

function anularComprobante(id, numero) {
    anularId = id;
    document.getElementById('anular-msg').textContent = `¿Anular el comprobante ${numero}? Esta acción no puede deshacerse. El registro quedará marcado como ANULADO.`;
    document.getElementById('anular-motivo').value = '';
    Modal.abrir('modal-anular');
}

async function confirmarAnulacion() {
    const motivo = document.getElementById('anular-motivo').value.trim();
    if (!motivo) { Toast.advertencia('Ingresa un motivo de anulación.'); return; }

    const btn = document.getElementById('btn-confirmar-anular');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Anulando...';

    try {
        const res  = await fetch(BASE_URL + '/api/anular_comprobante.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ comprobante_id: anularId, motivo })
        });
        const json = await res.json();
        if (json.success) {
            Modal.cerrar('modal-anular');
            Toast.exito('Comprobante anulado correctamente.');
            setTimeout(() => location.reload(), 900);
        } else {
            Toast.error(json.message || 'Error al anular.');
        }
    } catch(e) {
        Toast.error('Error de conexión.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-ban"></i> Anular';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
