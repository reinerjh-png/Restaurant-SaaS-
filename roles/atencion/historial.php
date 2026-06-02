<?php
/**
 * roles/atencion/historial.php — Historial de pedidos cobrados (solo lectura)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$fechaParam  = $_GET['fecha']   ?? null;
$buscar = trim($_GET['q']  ?? '');
$tipo   = $_GET['tipo']    ?? '';
$turnoIdParam = isset($_GET['turno_id']) ? (int)$_GET['turno_id'] : null;

$filtroTurno = false;
$inicioRango = null;
$finRango = null;
$tituloTurno = '';
$turnoId = null;
$turnoAnteriorId = null;
$turnoSiguienteId = null;
$esHistorico = false; // Modo visualización de turno pasado

if ($turnoIdParam) {
    // Modo: ver un turno específico por ID
    $stTurnoEsp = $db->prepare("
        SELECT t.*, u.nombre AS abierto_por
        FROM turnos t
        JOIN usuarios u ON u.id = t.usuario_id
        WHERE t.id = ? AND t.restaurante_id = ? AND t.fin IS NOT NULL
    ");
    $stTurnoEsp->execute([$turnoIdParam, $restauranteId]);
    $turnoEsp = $stTurnoEsp->fetch();

    if ($turnoEsp) {
        $filtroTurno = true;
        $esHistorico = true;
        $inicioRango = $turnoEsp['inicio'];
        $finRango = $turnoEsp['fin'];
        $turnoId = $turnoEsp['id'];
        $tituloTurno = "Turno cerrado &mdash; " . date('d/m H:i', strtotime($inicioRango)) . " &rarr; " . date('H:i', strtotime($finRango));
        $fecha = date('Y-m-d', strtotime($inicioRango));

        // Buscar turno anterior (ID menor más cercano cerrado)
        $stPrev = $db->prepare("SELECT id FROM turnos WHERE restaurante_id = ? AND fin IS NOT NULL AND id < ? ORDER BY id DESC LIMIT 1");
        $stPrev->execute([$restauranteId, $turnoId]);
        $turnoAnteriorId = $stPrev->fetchColumn();

        // Buscar turno siguiente (ID mayor más cercano)
        $stNext = $db->prepare("SELECT id FROM turnos WHERE restaurante_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
        $stNext->execute([$restauranteId, $turnoId]);
        $turnoSiguienteId = $stNext->fetchColumn();
    } else {
        // ID inválido, ir al modo por defecto
        header('Location: historial.php');
        exit;
    }
} elseif ($fechaParam) {
    $fecha = $fechaParam;
} else {
    $filtroTurno = true;
    
    // Buscar turno activo
    $stTurno = $db->prepare("SELECT id, inicio FROM turnos WHERE restaurante_id = ? AND fin IS NULL ORDER BY inicio DESC LIMIT 1");
    $stTurno->execute([$restauranteId]);
    $turnoActivo = $stTurno->fetch();
    
    if ($turnoActivo) {
        $turnoId = $turnoActivo['id'];
        $inicioRango = $turnoActivo['inicio'];
        $finRango = date('Y-m-d H:i:s');
        $tituloTurno = "Turno actual &mdash; desde " . date('H:i', strtotime($inicioRango));
        $fecha = date('Y-m-d', strtotime($inicioRango));

        // Botón "Ver turno anterior" -> el último turno cerrado
        $stPrev2 = $db->prepare("SELECT id FROM turnos WHERE restaurante_id = ? AND fin IS NOT NULL ORDER BY fin DESC LIMIT 1");
        $stPrev2->execute([$restauranteId]);
        $turnoAnteriorId = $stPrev2->fetchColumn();
    } else {
        // Buscar último turno cerrado
        $stUltimo = $db->prepare("SELECT id, inicio, fin FROM turnos WHERE restaurante_id = ? AND fin IS NOT NULL ORDER BY fin DESC LIMIT 1");
        $stUltimo->execute([$restauranteId]);
        $ultimoTurno = $stUltimo->fetch();
        
        if ($ultimoTurno) {
            $filtroTurno = true;
            $esHistorico = true;
            $turnoId = $ultimoTurno['id'];
            $inicioRango = $ultimoTurno['inicio'];
            $finRango = $ultimoTurno['fin'];
            $tituloTurno = "Último turno cerrado &mdash; " . date('d/m H:i', strtotime($inicioRango)) . " &rarr; " . date('H:i', strtotime($finRango));
            $fecha = date('Y-m-d', strtotime($finRango));

            // Turno anterior al último
            $stPrev3 = $db->prepare("SELECT id FROM turnos WHERE restaurante_id = ? AND fin IS NOT NULL AND id < ? ORDER BY id DESC LIMIT 1");
            $stPrev3->execute([$restauranteId, $turnoId]);
            $turnoAnteriorId = $stPrev3->fetchColumn();
        } else {
            $fecha = date('Y-m-d');
            $filtroTurno = false;
        }
    }
}

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
";
$params = [$restauranteId];

if ($filtroTurno) {
    $sql .= " AND pe.updated_at BETWEEN ? AND ?";
    $params[] = $inicioRango;
    $params[] = $finRango;
} else {
    $sql .= " AND DATE(pe.created_at) = ?";
    $params[] = $fecha;
}

if ($tipo) { $sql .= " AND pe.tipo = ?"; $params[] = $tipo; }
if ($buscar && is_numeric($buscar)) { $sql .= " AND pe.id = ?"; $params[] = (int)$buscar; }
$sql .= " ORDER BY pe.updated_at DESC LIMIT 100";

$st = $db->prepare($sql);
$st->execute($params);
$pedidos = $st->fetchAll();

// Métricas
if ($filtroTurno) {
    $stVentas = $db->prepare("SELECT COALESCE(SUM(total),0) AS total_dia FROM pedidos WHERE restaurante_id = ? AND estado = 'cobrado' AND updated_at BETWEEN ? AND ?");
    $stVentas->execute([$restauranteId, $inicioRango, $finRango]);
} else {
    $stVentas = $db->prepare("SELECT COALESCE(SUM(total),0) AS total_dia FROM pedidos WHERE restaurante_id = ? AND estado = 'cobrado' AND DATE(created_at) = ?");
    $stVentas->execute([$restauranteId, $fecha]);
}
$ventas = $stVentas->fetch();
$ingresosHoy = $ventas['total_dia'];

if ($filtroTurno) {
    $stGastos = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE restaurante_id = ? AND created_at BETWEEN ? AND ? AND activo = 1");
    $stGastos->execute([$restauranteId, $inicioRango, $finRango]);
} else {
    $stGastos = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE restaurante_id = ? AND fecha = ? AND activo = 1");
    $stGastos->execute([$restauranteId, $fecha]);
}
$gastosHoy = $stGastos->fetchColumn();

$utilidadHoy = $ingresosHoy - $gastosHoy;

if ($filtroTurno) {
    $stMetodos = $db->prepare("
        SELECT pa.metodo, COALESCE(SUM(pa.monto),0) AS total
        FROM pagos pa
        JOIN pedidos pe ON pe.id = pa.pedido_id
        WHERE pe.restaurante_id = ? AND pa.created_at BETWEEN ? AND ?
        GROUP BY pa.metodo
    ");
    $stMetodos->execute([$restauranteId, $inicioRango, $finRango]);
} else {
    $stMetodos = $db->prepare("
        SELECT pa.metodo, COALESCE(SUM(pa.monto),0) AS total
        FROM pagos pa
        JOIN pedidos pe ON pe.id = pa.pedido_id
        WHERE pe.restaurante_id = ? AND DATE(pa.created_at) = ?
        GROUP BY pa.metodo
    ");
    $stMetodos->execute([$restauranteId, $fecha]);
}
$metodos = $stMetodos->fetchAll();
$metodoMap = array_column($metodos, 'total', 'metodo');

$pageTitle  = 'Registro de Ventas';
$activeMenu = 'historial';
require_once '../../includes/header.php';
?>


        <div class="page-content">

            <?php if ($esHistorico): ?>
            <!-- Banner modo histórico -->
            <div style="background:linear-gradient(135deg,rgba(245,158,11,.1),rgba(251,191,36,.05));border:1.5px solid rgba(245,158,11,.35);border-radius:var(--radius-md);padding:12px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;">
                <i class="fa-solid fa-eye" style="color:#f59e0b;font-size:1.1rem;flex-shrink:0;"></i>
                <div style="flex:1;">
                    <div style="font-size:.85rem;font-weight:700;color:#92400e;">Vista de turno histórico</div>
                    <div style="font-size:.78rem;color:#b45309;">Solo lectura &mdash; <?= htmlspecialchars(strip_tags($tituloTurno)) ?></div>
                </div>
                <a href="historial.php" class="btn btn-ghost btn-sm" style="white-space:nowrap;">
                    <i class="fa-solid fa-arrow-left"></i> Turno actual
                </a>
            </div>
            <?php endif; ?>

            <div class="d-flex align-center justify-between mb-16">
                <div>
                    <h1><i class="fa-solid fa-clock-rotate-left" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Registro de Ventas</h1>
                    <p><?= $filtroTurno ? '<strong>' . $tituloTurno . '</strong><br>' : '' ?><?= count($pedidos) ?> pedido(s) encontrados</p>
                </div>
                <?php if ($filtroTurno && !$fechaParam): ?>
                <!-- Navegación de turnos -->
                <div style="display:flex;align-items:center;gap:8px;">
                    <?php if ($turnoAnteriorId): ?>
                    <a href="historial.php?turno_id=<?= $turnoAnteriorId ?>" class="btn btn-ghost btn-sm" title="Turno anterior" style="display:flex;align-items:center;gap:5px;">
                        <i class="fa-solid fa-chevron-left"></i> Anterior
                    </a>
                    <?php else: ?>
                    <button class="btn btn-ghost btn-sm" disabled style="opacity:.35;cursor:not-allowed;">
                        <i class="fa-solid fa-chevron-left"></i> Anterior
                    </button>
                    <?php endif; ?>

                    <span style="font-size:.75rem;color:var(--text-muted);padding:0 4px;white-space:nowrap;">
                        <?= $esHistorico ? 'Turno #' . $turnoId : 'Turno actual' ?>
                    </span>

                    <?php if ($turnoSiguienteId): ?>
                    <a href="historial.php?turno_id=<?= $turnoSiguienteId ?>" class="btn btn-ghost btn-sm" title="Turno siguiente" style="display:flex;align-items:center;gap:5px;">
                        Siguiente <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <?php elseif ($esHistorico): ?>
                    <a href="historial.php" class="btn btn-ghost btn-sm" style="display:flex;align-items:center;gap:5px;">
                        Actual <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <?php else: ?>
                    <button class="btn btn-ghost btn-sm" disabled style="opacity:.35;cursor:not-allowed;">
                        Siguiente <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Panel de Resumen -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px;">
                <!-- Ingresos -->
                <div class="card" style="border-left:4px solid var(--success);padding:16px;">
                    <div style="color:var(--success);margin-bottom:8px;"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">S/ <?= number_format($ingresosHoy, 2) ?></div>
                    <div style="font-size:.85rem;color:var(--text-secondary);">Ingresos <?= $filtroTurno ? $tituloTurno : ($fecha === date('Y-m-d') ? 'hoy' : 'del día') ?></div>
                </div>
                <!-- Gastos -->
                <div class="card" style="border-left:4px solid var(--danger);padding:16px;">
                    <div style="color:var(--danger);margin-bottom:8px;"><i class="fa-solid fa-money-bill-transfer"></i></div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">S/ <?= number_format($gastosHoy, 2) ?></div>
                    <div style="font-size:.85rem;color:var(--text-secondary);">Gastos <?= $filtroTurno ? $tituloTurno : ($fecha === date('Y-m-d') ? 'hoy' : 'del día') ?></div>
                </div>
                <!-- Utilidad -->
                <div class="card" style="border-left:4px solid var(--primary);padding:16px;background:var(--bg-secondary);">
                    <div style="color:var(--primary);margin-bottom:8px;"><i class="fa-solid fa-piggy-bank"></i></div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">S/ <?= number_format($utilidadHoy, 2) ?></div>
                    <div style="font-size:.85rem;color:var(--text-secondary);">Utilidad <?= $filtroTurno ? $tituloTurno : ($fecha === date('Y-m-d') ? 'hoy' : 'del día') ?></div>
                </div>
            </div>

            <div class="card mb-20" style="padding:16px;">
                <div style="font-size:.85rem;font-weight:700;color:var(--text-primary);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                    <i class="fa-solid fa-credit-card"></i> Ingresos por método de pago — <?= $filtroTurno ? $tituloTurno : ($fecha === date('Y-m-d') ? 'hoy' : $fecha) ?>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(120px, 1fr));gap:12px;">
                    <?php
                    $listaMetodos = [
                        'efectivo'      => ['Efectivo', 'fa-money-bill-1'],
                        'yape'          => ['Yape / Plin', 'fa-mobile-screen'],
                        'transferencia' => ['Transferencia', 'fa-building-columns'],
                        'tarjeta'       => ['Tarjeta', 'fa-credit-card'],
                        'otro'          => ['Otro', 'fa-wallet']
                    ];
                    foreach ($listaMetodos as $k => $m):
                        $val = $metodoMap[$k] ?? 0;
                    ?>
                    <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;text-align:center;">
                        <i class="fa-solid <?= $m[1] ?>" style="color:var(--text-secondary);font-size:1.2rem;margin-bottom:6px;"></i>
                        <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">S/ <?= number_format($val, 2) ?></div>
                        <div style="font-size:.75rem;color:var(--text-muted);"><?= $m[0] ?></div>
                    </div>
                    <?php endforeach; ?>
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
                                        <a href="../admin/ver_comprobante.php?id=<?= $p['comprobante_id'] ?>" class="btn btn-ghost btn-sm" title="Ver / Imprimir Comprobante">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <?php endif; ?>
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
</script>

<?php require_once '../../includes/footer.php'; ?>
