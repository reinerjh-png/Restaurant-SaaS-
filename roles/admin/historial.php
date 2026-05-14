<?php
/**
 * roles/admin/historial.php — Historial de pedidos cobrados
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$fecha  = $_GET['fecha']   ?? date('Y-m-d');
$buscar = trim($_GET['q']  ?? '');
$tipo   = $_GET['tipo']    ?? '';

$sql = "
    SELECT pe.id, pe.tipo, pe.total, pe.created_at, pe.updated_at,
           m.numero AS mesa_numero,
           u.nombre AS cajero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    JOIN usuarios u ON u.id = pe.usuario_id
    WHERE pe.restaurante_id = ? AND pe.estado = 'cobrado'
      AND DATE(pe.created_at) = ?
";
$params = [$restauranteId, $fecha];
if ($tipo) { $sql .= " AND pe.tipo = ?"; $params[] = $tipo; }
if ($buscar && is_numeric($buscar)) { $sql .= " AND pe.id = ?"; $params[] = (int)$buscar; }
$sql .= " ORDER BY pe.updated_at DESC LIMIT 100";

$st = $db->prepare($sql);
$st->execute($params);
$pedidos = $st->fetchAll();

$pageTitle  = 'Historial';
$activeMenu = 'historial';
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
            <li><a href="historial.php" class="active"><span class="menu-icon"><i class="fa-solid fa-clock-rotate-left"></i></span> Historial</a></li>
            <li><a href="historial_comprobantes.php"><span class="menu-icon"><i class="fa-solid fa-file-invoice"></i></span> Comprobantes</a></li>
            <li><a href="config_facturacion.php"><span class="menu-icon"><i class="fa-solid fa-store"></i></span> Mi Restaurante</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-16">
                <div>
                    <h1><i class="fa-solid fa-clock-rotate-left" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Historial de Pedidos</h1>
                    <p><?= count($pedidos) ?> pedido(s) encontrados</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-16">
                <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                    <div class="form-group" style="margin:0;flex:1;min-width:130px;">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?= $fecha ?>">
                    </div>
                    <div class="form-group" style="margin:0;width:140px;">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="aqui"  <?= $tipo==='aqui'?'selected':'' ?>>Comer aquí</option>
                            <option value="llevar" <?= $tipo==='llevar'?'selected':'' ?>>Para llevar</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;flex:1;min-width:130px;">
                        <label class="form-label">Buscar # pedido</label>
                        <input type="text" name="q" class="form-control" placeholder="N° de pedido" value="<?= htmlspecialchars($buscar) ?>">
                    </div>
                    <button type="submit" class="btn btn-primario">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                    </button>
                    <a href="historial.php" class="btn btn-ghost">
                        <i class="fa-solid fa-rotate-left"></i> Hoy
                    </a>
                </form>
            </div>

            <!-- Tabla -->
            <div class="card">
                <?php if ($pedidos): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tipo</th>
                                <th>Mesa</th>
                                <th>Atendido por</th>
                                <th>Cobrado</th>
                                <th>Total</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td><strong>#<?= $p['id'] ?></strong></td>
                                <td>
                                    <span class="badge <?= $p['tipo']==='aqui' ? 'badge-azul' : 'badge-naranja' ?>">
                                        <i class="fa-solid <?= $p['tipo']==='aqui' ? 'fa-house' : 'fa-bag-shopping' ?>"></i>
                                        <?= $p['tipo']==='aqui' ? 'Aquí' : 'Llevar' ?>
                                    </span>
                                </td>
                                <td><?= $p['mesa_numero'] ? 'Mesa '.$p['mesa_numero'] : '—' ?></td>
                                <td style="font-size:.875rem;color:var(--text-secondary);"><?= htmlspecialchars($p['cajero']) ?></td>
                                <td style="font-size:.8rem;color:var(--text-secondary);"><?= date('H:i', strtotime($p['updated_at'])) ?></td>
                                <td><strong style="color:var(--success);">S/ <?= number_format($p['total'], 2) ?></strong></td>
                                <td>
                                    <button class="btn btn-ghost btn-sm" onclick="verDetallePedido(<?= $p['id'] ?>)" title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon"><i class="fa-solid fa-receipt"></i></div>
                    <h3>Sin pedidos cobrados</h3>
                    <p>No hay registros para los filtros seleccionados</p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Modal detalle -->
<div class="modal-overlay" id="modal-detalle-hist">
    <div class="modal" style="max-height:80vh;display:flex;flex-direction:column;">
        <div class="modal-header">
            <div class="modal-title" id="modal-hist-titulo">
                <i class="fa-solid fa-receipt"></i> Detalle del Pedido
            </div>
            <button class="modal-close" onclick="Modal.cerrar('modal-detalle-hist')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="modal-hist-body" style="overflow-y:auto;">
            <div class="text-center"><div class="spinner"></div></div>
        </div>
    </div>
</div>

<script>
async function verDetallePedido(id) {
    Modal.abrir('modal-detalle-hist');
    document.getElementById('modal-hist-titulo').innerHTML = '<i class="fa-solid fa-receipt"></i> Pedido #' + id;
    document.getElementById('modal-hist-body').innerHTML = '<div class="text-center"><div class="spinner"></div></div>';

    try {
        const res  = await fetch(`${BASE_URL}/api/get_pedido_detalle.php?id=${id}`);
        const json = await res.json();
        if (!json.success) { document.getElementById('modal-hist-body').innerHTML = '<p>Error al cargar</p>'; return; }
        const p = json.data;

        let html = `<div style="margin-bottom:14px;display:flex;gap:10px;align-items:center;">
            <span class="badge badge-gris"><i class="fa-solid fa-hashtag"></i>${p.id}</span>
            <span class="badge ${p.tipo==='aqui'?'badge-azul':'badge-naranja'}">
                <i class="fa-solid ${p.tipo==='aqui'?'fa-house':'fa-bag-shopping'}"></i>
                ${p.tipo==='aqui'?'Comer aquí':'Para llevar'}
            </span>
            ${p.mesa_numero ? `<span class="badge badge-gris"><i class="fa-solid fa-chair"></i> Mesa ${p.mesa_numero}</span>` : ''}
        </div>`;

        p.items.forEach(item => {
            html += `<div class="pedido-item">
                <div style="flex:1;">
                    <div class="pedido-item-nombre">${item.cantidad}x ${item.nombre}</div>
                    ${item.opciones ? `<div class="pedido-item-opciones">· ${item.opciones}</div>` : ''}
                    ${item.notas   ? `<div class="pedido-item-opciones"><i class="fa-solid fa-note-sticky"></i> ${item.notas}</div>` : ''}
                </div>
                <div class="pedido-item-precio">S/ ${parseFloat(item.subtotal).toFixed(2)}</div>
            </div>`;
        });

        html += `<div class="pedido-total" style="margin-top:16px;padding-top:12px;border-top:2px solid var(--border);">
            <span>TOTAL</span>
            <span style="color:var(--success);">S/ ${parseFloat(p.total).toFixed(2)}</span>
        </div>`;

        document.getElementById('modal-hist-body').innerHTML = html;
    } catch(e) {
        document.getElementById('modal-hist-body').innerHTML = '<p>Error de conexión</p>';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
