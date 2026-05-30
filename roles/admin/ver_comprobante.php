<?php
/**
 * roles/admin/ver_comprobante.php
 * Vista detallada de un comprobante desde el panel admin.
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin', 'atencion']);
$rolActual = $_SESSION['rol'] ?? '';

$comprobanteId = (int)($_GET['id'] ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$comprobanteId) { header('Location: historial_comprobantes.php'); exit; }

$db = getDB();

$st = $db->prepare("
    SELECT c.*, u.nombre AS cajero_nombre,
           pe.tipo AS pedido_tipo,
           m.numero AS mesa_numero,
           fc.ruc AS rest_ruc, fc.razon_social AS rest_razon, fc.direccion_fiscal AS rest_dir,
           fc.telefono AS rest_tel, fc.pie_mensaje, fc.logo AS rest_logo
    FROM comprobantes c
    JOIN usuarios u ON u.id = c.usuario_id
    JOIN pedidos pe ON pe.id = c.pedido_id
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    LEFT JOIN facturacion_config fc ON fc.restaurante_id = c.restaurante_id
    WHERE c.id = ? AND c.restaurante_id = ?
");
$st->execute([$comprobanteId, $restauranteId]);
$comp = $st->fetch();

if (!$comp) { header('Location: historial_comprobantes.php'); exit; }

$items = json_decode($comp['items_json'] ?? '[]', true) ?: [];
$pagos = json_decode($comp['pagos_json'] ?? '[]', true) ?: [];

$itemsAgrupados = [];
foreach ($items as $item) {
    $key = ($item['producto_nombre'] ?? '') . '||' . ($item['opciones_texto'] ?? '');
    if (isset($itemsAgrupados[$key])) {
        $itemsAgrupados[$key]['cantidad'] += intval($item['cantidad']);
        $itemsAgrupados[$key]['subtotal'] += floatval($item['subtotal']);
    } else {
        $itemsAgrupados[$key] = [
            'nombre'   => $item['producto_nombre'] ?? '',
            'opciones' => $item['opciones_texto']  ?? '',
            'cantidad' => intval($item['cantidad']),
            'precio'   => floatval($item['precio_unitario'] ?? 0),
            'subtotal' => floatval($item['subtotal']),
        ];
    }
}

$metodoLabel = ['efectivo'=>'Efectivo','yape'=>'Yape/Plin','transferencia'=>'Transferencia','tarjeta'=>'Tarjeta','otro'=>'Otro'];

$pageTitle  = 'Comprobante ' . htmlspecialchars($comp['numero_comprobante']);
$activeMenu = 'comprobantes';
$extraCSS   = BASE_URL . '/assets/css/print.css';
require_once '../../includes/header.php';
?>

<link rel="stylesheet" href="<?= $extraCSS ?>">


        <div class="page-content">

            <!-- Acciones -->
            <div class="ticket-acciones" style="justify-content:flex-start;margin-bottom:20px;">
                <?php if ($rolActual === 'atencion'): ?>
                <a href="../atencion/historial.php" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver al historial
                </a>
                <?php else: ?>
                <a href="historial_comprobantes.php" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver al historial
                </a>
                <?php endif; ?>
                <button class="btn btn-primario btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Imprimir
                </button>
                <?php if (!$comp['anulado'] && in_array($rolActual, ['admin', 'superadmin'])): ?>
                <button class="btn btn-peligro btn-sm" onclick="anularComprobante(<?= $comp['id'] ?>, '<?= htmlspecialchars(addslashes($comp['numero_comprobante'])) ?>')">
                    <i class="fa-solid fa-ban"></i> Anular
                </button>
                <?php endif; ?>
            </div>

            <!-- Info de auditoría (no se imprime) -->
            <div class="card mb-20" style="font-size:.82rem;color:var(--text-secondary);">
                <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
                    <span><i class="fa-solid fa-receipt"></i> Pedido ID: <strong style="color:var(--text-primary);">#<?= $comp['pedido_id'] ?></strong></span>
                    <span><i class="fa-solid fa-user"></i> Cajero: <strong style="color:var(--text-primary);"><?= htmlspecialchars($comp['cajero_nombre']) ?></strong></span>
                    <span><i class="fa-solid fa-chair"></i>
                        <?= $comp['mesa_numero'] ? 'Mesa '.$comp['mesa_numero'] : 'Para llevar' ?>
                    </span>
                    <?php if ($comp['anulado']): ?>
                    <span style="color:var(--danger);font-weight:700;"><i class="fa-solid fa-ban"></i> ANULADO — <?= htmlspecialchars($comp['motivo_anulacion'] ?? '') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TICKET COMPROBANTE -->
            <div id="comprobante-print-root">
            <div class="ticket-wrapper">

                <?php if ($comp['anulado']): ?>
                <div class="ticket-anulado-banner">★ ANULADO ★</div>
                <?php endif; ?>

                <!-- Logo -->
                <?php
                $logoPath = $comp['rest_logo'] ?: null;
                if ($logoPath && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath)):
                ?>
                <div class="ticket-logo"><img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo"></div>
                <?php else: ?>
                <div class="ticket-logo"><img src="<?= BASE_URL ?>/assets/logo.png" alt="Logo" style="max-width:100px;max-height:52px;"></div>
                <?php endif; ?>

                <!-- Restaurante -->
                <div class="ticket-header">
                    <div class="razon-social"><?= htmlspecialchars($comp['rest_razon'] ?: 'Restaurante') ?></div>
                    <?php if ($comp['rest_ruc']): ?>
                    <div class="ruc-label">RUC: <?= htmlspecialchars($comp['rest_ruc']) ?></div>
                    <?php endif; ?>
                    <?php if ($comp['rest_dir']): ?>
                    <div style="font-size:11px;color:#555;"><?= htmlspecialchars($comp['rest_dir']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($comp['rest_tel'])): ?>
                    <div style="font-size:11px;color:#555;">Tel: <?= htmlspecialchars($comp['rest_tel']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="ticket-tipo-wrap">
                    <span class="ticket-tipo-comp">
                        <?php
                        if ($comp['tipo'] === 'boleta') echo 'Boleta de Venta';
                        elseif ($comp['tipo'] === 'factura') echo 'Factura';
                        else echo 'Comprobante Simple';
                        ?>
                    </span>
                </div>

                <!-- Datos comprobante -->
                <div class="ticket-section">
                    <div class="ticket-row"><span class="label">Nº:</span><span class="value"><?= htmlspecialchars($comp['numero_comprobante']) ?></span></div>
                    <div class="ticket-row"><span class="label">Fecha:</span><span class="value"><?= date('d/m/Y H:i', strtotime($comp['created_at'])) ?></span></div>
                    <div class="ticket-row"><span class="label">Cajero:</span><span class="value"><?= htmlspecialchars($comp['cajero_nombre']) ?></span></div>
                </div>

                <!-- Datos cliente -->
                <?php if ($comp['tipo'] !== 'simple'): ?>
                <div class="ticket-section">
                    <div class="ticket-row">
                        <span class="label"><?= strtoupper($comp['tipo_documento']) ?>:</span>
                        <span class="value"><?= htmlspecialchars($comp['numero_documento']) ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="label"><?= $comp['tipo'] === 'factura' ? 'Razón Social:' : 'Cliente:' ?></span>
                        <span class="value"><?= htmlspecialchars($comp['nombre_cliente']) ?></span>
                    </div>
                    <?php if ($comp['tipo'] === 'factura' && $comp['direccion_cliente']): ?>
                    <div class="ticket-row"><span class="label">Dir:</span><span class="value"><?= htmlspecialchars($comp['direccion_cliente']) ?></span></div>
                    <?php if ($comp['distrito']): ?>
                    <div class="ticket-row">
                        <span class="label">Ubic:</span>
                        <span class="value"><?= htmlspecialchars($comp['distrito']) ?><?= $comp['provincia'] ? ' - '.htmlspecialchars($comp['provincia']) : '' ?><?= $comp['departamento'] ? ' - '.htmlspecialchars($comp['departamento']) : '' ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Items -->
                <div class="ticket-section">
                    <table class="ticket-items-table">
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th style="text-align:center;">Cant</th>
                                <th>P.Unit</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itemsAgrupados as $item): ?>
                            <tr>
                                <td>
                                    <span class="item-nombre"><?= htmlspecialchars($item['nombre']) ?></span>
                                    <?php if ($item['opciones']): ?>
                                    <span class="item-opciones">· <?= htmlspecialchars($item['opciones']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;"><?= $item['cantidad'] ?></td>
                                <td><?= number_format($item['precio'], 2) ?></td>
                                <td><?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totales -->
                <div class="ticket-totales ticket-section">
                    <div class="ticket-total-row"><span>Op. Gravada:</span><span>S/ <?= number_format($comp['subtotal'], 2) ?></span></div>
                    <div class="ticket-total-row"><span>IGV (18%):</span><span>S/ <?= number_format($comp['igv'], 2) ?></span></div>
                    <div class="ticket-total-row total-final"><span>TOTAL:</span><span>S/ <?= number_format($comp['total'], 2) ?></span></div>
                </div>

                <!-- Pagos -->
                <?php if (!empty($pagos)): ?>
                <div class="ticket-pagos ticket-section">
                    <?php foreach ($pagos as $pago): ?>
                    <div class="ticket-row">
                        <span class="label"><?= htmlspecialchars($metodoLabel[$pago['metodo']] ?? $pago['metodo']) ?>:</span>
                        <span class="value">S/ <?= number_format(floatval($pago['monto']), 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="ticket-pie"><?= htmlspecialchars($comp['pie_mensaje'] ?: '¡Gracias por su visita!') ?></div>

            </div><!-- /.ticket-wrapper -->
            </div><!-- /#comprobante-print-root -->

        </div>

<!-- Modal anulación -->
<div class="modal-overlay" id="modal-anular">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-ban" style="color:var(--danger);"></i> Anular comprobante</div>
        </div>
        <div class="modal-body">
            <p id="anular-msg" style="color:var(--text-primary);margin-bottom:12px;"></p>
            <label class="form-label">Motivo de anulación <span style="color:var(--danger);">*</span></label>
            <input type="text" id="anular-motivo" class="form-control" placeholder="Ej: Error en datos del cliente...">
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
    document.getElementById('anular-msg').textContent = `¿Anular el comprobante ${numero}? Esta acción no puede deshacerse.`;
    document.getElementById('anular-motivo').value = '';
    Modal.abrir('modal-anular');
}
async function confirmarAnulacion() {
    const motivo = document.getElementById('anular-motivo').value.trim();
    if (!motivo) { Toast.advertencia('Ingresa un motivo.'); return; }
    const btn = document.getElementById('btn-confirmar-anular');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    try {
        const res  = await fetch(BASE_URL + '/api/anular_comprobante.php', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ comprobante_id: anularId, motivo })
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito('Comprobante anulado.');
            setTimeout(() => location.reload(), 900);
        } else {
            Toast.error(json.message);
        }
    } catch(e) { Toast.error('Error de conexión.'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-ban"></i> Anular';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
