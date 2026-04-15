<?php
/**
 * roles/admin/historial.php — Historial de pedidos
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$fecha  = $_GET['fecha']  ?? date('Y-m-d');
$estado = $_GET['estado'] ?? 'cobrado';

$st = $db->prepare("
    SELECT pe.id, pe.tipo, pe.estado, pe.total, pe.created_at, pe.updated_at,
           m.numero AS mesa_numero,
           u.nombre AS cajero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    JOIN usuarios u ON u.id = pe.usuario_id
    WHERE pe.restaurante_id = ? AND DATE(pe.created_at) = ?
      AND (? = 'todos' OR pe.estado = ?)
    ORDER BY pe.updated_at DESC
");
$st->execute([$restauranteId, $fecha, $estado, $estado]);
$pedidos = $st->fetchAll();

$pageTitle  = 'Historial de Pedidos';
$activeMenu = 'historial';
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
            <li><a href="reportes.php"><span class="menu-icon">📈</span> Reportes</a></li>
            <li><a href="historial.php" class="active"><span class="menu-icon">🗂️</span> Historial</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-16">
                <h1>🗂️ Historial de Pedidos</h1>
            </div>

            <!-- Filtros -->
            <div class="card mb-16">
                <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?= $fecha ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-control">
                            <option value="cobrado"   <?= $estado==='cobrado'   ?'selected':'' ?>>✅ Cobrados</option>
                            <option value="cancelado" <?= $estado==='cancelado' ?'selected':'' ?>>❌ Cancelados</option>
                            <option value="activo"    <?= $estado==='activo'    ?'selected':'' ?>>⏳ Activos</option>
                            <option value="todos"     <?= $estado==='todos'     ?'selected':'' ?>>📋 Todos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primario">🔍 Filtrar</button>
                </form>
            </div>

            <!-- Tabla -->
            <div class="card">
                <div class="card-title">
                    <?= count($pedidos) ?> pedidos para el <?= date('d/m/Y', strtotime($fecha)) ?>
                </div>
                <?php if ($pedidos): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mesa / Tipo</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Cajero</th>
                                <th>Hora</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td>#<?= $p['id'] ?></td>
                                <td>
                                    <?= $p['tipo']==='aqui' ? '🏠' : '🛍️' ?>
                                    <?= $p['mesa_numero'] ? 'Mesa '.$p['mesa_numero'] : 'Para llevar' ?>
                                </td>
                                <td>
                                    <span class="badge <?= ['cobrado'=>'badge-verde','cancelado'=>'badge-rojo','activo'=>'badge-naranja'][$p['estado']] ?>">
                                        <?= ucfirst($p['estado']) ?>
                                    </span>
                                </td>
                                <td><strong>S/ <?= number_format($p['total'],2) ?></strong></td>
                                <td><?= htmlspecialchars($p['cajero']) ?></td>
                                <td><?= date('H:i', strtotime($p['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-ghost btn-sm" onclick="verDetalle(<?= $p['id'] ?>)">👁️ Ver</button>
                                    <?php if ($p['estado'] === 'activo'): ?>
                                    <button class="btn btn-peligro btn-sm" onclick="cancelarPedido(<?= $p['id'] ?>)">❌ Cancelar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state"><div class="icon">🗂️</div><h3>No hay pedidos</h3><p>Prueba con otro filtro</p></div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Modal detalle pedido -->
<div class="modal-overlay" id="modal-detalle">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">📋 Detalle del Pedido</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-detalle')">✕</button>
        </div>
        <div class="modal-body" id="modal-detalle-body">
            <div class="text-center"><div class="spinner"></div></div>
        </div>
    </div>
</div>

<script>
async function verDetalle(pedidoId) {
    Modal.abrir('modal-detalle');
    document.getElementById('modal-detalle-body').innerHTML = '<div class="text-center"><div class="spinner"></div></div>';
    try {
        const res  = await fetch(`${BASE_URL}/api/get_pedido_detalle.php?id=${pedidoId}`);
        const json = await res.json();
        if (!json.success) { document.getElementById('modal-detalle-body').innerHTML = '<p>Error al cargar</p>'; return; }
        const p = json.data;
        let html = `<div style="margin-bottom:14px;">
            <span class="badge badge-azul">#${p.id}</span>
            ${p.tipo === 'aqui' ? '🏠 Aquí' : '🛍️ Para llevar'}
            ${p.mesa_numero ? '· Mesa ' + p.mesa_numero : ''}
        </div>`;
        p.items.forEach(item => {
            html += `<div class="pedido-item">
                <div style="flex:1;">
                    <div class="pedido-item-nombre">${item.cantidad}x ${item.nombre}</div>
                    ${item.opciones ? `<div class="pedido-item-opciones">· ${item.opciones}</div>` : ''}
                    ${item.notas ? `<div class="pedido-item-opciones">📝 ${item.notas}</div>` : ''}
                </div>
                <div class="pedido-item-precio">S/ ${parseFloat(item.subtotal).toFixed(2)}</div>
            </div>`;
        });
        html += `<div class="pedido-total mt-12"><span>Total</span><span class="text-rojo">S/ ${parseFloat(p.total).toFixed(2)}</span></div>`;
        if (p.pagos && p.pagos.length) {
            html += '<div class="mt-12"><strong style="font-size:.82rem;">Pagos registrados:</strong>';
            p.pagos.forEach(pg => {
                html += `<div class="resumen-row"><span>💳 ${pg.metodo}</span><span>S/ ${parseFloat(pg.monto).toFixed(2)}</span></div>`;
            });
            html += '</div>';
        }
        document.getElementById('modal-detalle-body').innerHTML = html;
    } catch(e) { document.getElementById('modal-detalle-body').innerHTML = '<p>Error de conexión</p>'; }
}

function cancelarPedido(id) {
    confirmar('¿Cancelar este pedido? La mesa volverá a estado libre.', async () => {
        try {
            const res  = await fetch(BASE_URL + '/api/cancelar_pedido.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({ id })
            });
            const json = await res.json();
            if (json.success) { Toast.exito(json.message); setTimeout(() => location.reload(), 700); }
            else Toast.error(json.message);
        } catch(e) { Toast.error('Error de conexión'); }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
