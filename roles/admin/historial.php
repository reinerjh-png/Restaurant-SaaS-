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
           u.nombre AS cajero,
           c.id AS comprobante_id,
           c.numero_comprobante,
           c.tipo AS comprobante_tipo
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    JOIN usuarios u ON u.id = pe.usuario_id
    LEFT JOIN comprobantes c ON c.pedido_id = pe.id
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
                                <th>Comprobante</th>
                                <th>Acciones</th>
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
                                    <?php if ($p['comprobante_id']): ?>
                                    <span class="badge badge-verde" style="font-size:0.75rem;"><i class="fa-solid fa-file-invoice"></i> <?= htmlspecialchars($p['numero_comprobante']) ?></span>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <button class="btn btn-ghost btn-sm" onclick="verDetallePedido(<?= $p['id'] ?>)" title="Ver detalle">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <?php if ($p['comprobante_id']): ?>
                                        <a href="ver_comprobante.php?id=<?= $p['comprobante_id'] ?>" class="btn btn-ghost btn-sm" title="Ver / Imprimir Comprobante">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button class="btn btn-ghost btn-sm" style="color:var(--primary);" onclick="reabrirPedido(<?= $p['id'] ?>)" title="Modificar Pedido">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick="eliminarPedido(<?= $p['id'] ?>)" title="Eliminar Venta">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
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

    const metodoLabel = {
        efectivo: 'Efectivo',
        yape: 'Yape / Plin',
        transferencia: 'Transferencia',
        tarjeta: 'Tarjeta',
        otro: 'Otro'
    };

    try {
        const res  = await fetch(`${BASE_URL}/api/get_pedido_detalle.php?id=${id}`);
        const json = await res.json();
        if (!json.success) { document.getElementById('modal-hist-body').innerHTML = '<p>Error al cargar</p>'; return; }
        const p = json.data;

        // Cabecera: badges
        let html = `<div style="margin-bottom:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <span class="badge badge-gris"><i class="fa-solid fa-hashtag"></i>${p.id}</span>
            <span class="badge ${p.tipo==='aqui'?'badge-azul':'badge-naranja'}">
                <i class="fa-solid ${p.tipo==='aqui'?'fa-house':'fa-bag-shopping'}"></i>
                ${p.tipo==='aqui'?'Comer aquí':'Para llevar'}
            </span>
            ${p.mesa_numero ? `<span class="badge badge-gris"><i class="fa-solid fa-chair"></i> Mesa ${p.mesa_numero}</span>` : ''}
        </div>`;

        // Items
        html += `<div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Productos</div>`;
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

        // Subtotal de ítems
        const subtotalItems = p.items.reduce((acc, i) => acc + parseFloat(i.subtotal), 0);
        const descuento   = parseFloat(p.descuento   || 0);
        const cargoExtra  = parseFloat(p.cargo_extra || 0);

        html += `<div style="margin-top:14px;padding-top:10px;border-top:1px dashed var(--border);">`;

        html += `<div style="display:flex;justify-content:space-between;font-size:.875rem;color:var(--text-secondary);margin-bottom:4px;">
            <span>Subtotal productos</span><span>S/ ${subtotalItems.toFixed(2)}</span>
        </div>`;

        if (descuento > 0) {
            html += `<div style="display:flex;justify-content:space-between;font-size:.875rem;color:var(--danger);margin-bottom:4px;">
                <span><i class="fa-solid fa-tag"></i> Descuento</span><span>− S/ ${descuento.toFixed(2)}</span>
            </div>`;
        }

        if (cargoExtra > 0) {
            html += `<div style="display:flex;justify-content:space-between;font-size:.875rem;color:var(--warning,#f59e0b);margin-bottom:4px;">
                <span><i class="fa-solid fa-circle-plus"></i> Cargo extra</span><span>+ S/ ${cargoExtra.toFixed(2)}</span>
            </div>`;
        }

        html += `<div class="pedido-total" style="margin-top:10px;">
            <span>TOTAL</span>
            <span style="color:var(--success);">S/ ${parseFloat(p.total).toFixed(2)}</span>
        </div></div>`;

        // Sección de pagos
        if (p.pagos && p.pagos.length > 0) {
            html += `<div style="margin-top:14px;padding-top:10px;border-top:1px dashed var(--border);">
                <div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Pagos realizados</div>`;
            p.pagos.forEach(pg => {
                html += `<div style="display:flex;justify-content:space-between;align-items:center;font-size:.875rem;margin-bottom:6px;">
                    <span style="color:var(--text-primary);">
                        <i class="fa-solid fa-circle-check" style="color:var(--success);margin-right:5px;"></i>
                        ${metodoLabel[pg.metodo] || pg.metodo}
                        ${pg.referencia ? `<span style="color:var(--text-muted);font-size:.75rem;"> · ${pg.referencia}</span>` : ''}
                    </span>
                    <strong style="color:var(--success);">S/ ${parseFloat(pg.monto).toFixed(2)}</strong>
                </div>`;
            });
            html += `</div>`;
        }

        document.getElementById('modal-hist-body').innerHTML = html;
    } catch(e) {
        document.getElementById('modal-hist-body').innerHTML = '<p>Error de conexión</p>';
    }
}

function reabrirPedido(id) {
    if(!confirm('¿Estás seguro de reabrir este pedido para modificarlo? Se eliminarán los pagos registrados (y si tiene comprobante, se anulará) y volverá a la pantalla de Atención.')) return;
    
    fetch(BASE_URL + '/api/reabrir_venta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(res => {
        if(res.success) {
            Toast.exito('Pedido reabierto.');
            setTimeout(() => location.href = BASE_URL + '/roles/atencion/index.php', 1500);
        } else {
            Toast.error(res.message);
        }
    }).catch(e => Toast.error('Error de conexión'));
}

function eliminarPedido(id) {
    if(!confirm('¿Estás seguro de eliminar esta venta? Se anulará el comprobante y se restará del total del día.')) return;
    
    fetch(BASE_URL + '/api/eliminar_venta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(res => {
        if(res.success) {
            Toast.exito('Venta eliminada.');
            setTimeout(() => location.reload(), 1000);
        } else {
            Toast.error(res.message);
        }
    }).catch(e => Toast.error('Error de conexión'));
}
</script>

<?php require_once '../../includes/footer.php'; ?>
