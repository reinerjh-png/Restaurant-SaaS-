<?php
/**
 * roles/atencion/comprobante_view.php
 * Vista de comprobante (boleta o factura) optimizada para impresión.
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$comprobanteId = (int)($_GET['id'] ?? 0);
$restauranteId = $_SESSION['restaurante_id'];

if (!$comprobanteId) { header('Location: dashboard.php'); exit; }

$db = getDB();

$st = $db->prepare("
    SELECT c.*, u.nombre AS cajero_nombre,
           fc.ruc AS rest_ruc, fc.razon_social AS rest_razon, fc.direccion_fiscal AS rest_dir,
           fc.telefono AS rest_tel, fc.pie_mensaje, fc.logo AS rest_logo
    FROM comprobantes c
    JOIN usuarios u ON u.id = c.usuario_id
    LEFT JOIN facturacion_config fc ON fc.restaurante_id = c.restaurante_id
    WHERE c.id = ? AND c.restaurante_id = ?
");
$st->execute([$comprobanteId, $restauranteId]);
$comp = $st->fetch();

if (!$comp) { header('Location: dashboard.php'); exit; }

$items = json_decode($comp['items_json'] ?? '[]', true) ?: [];
$pagos = json_decode($comp['pagos_json'] ?? '[]', true) ?: [];

// Agrupar items por nombre+opciones
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

$pageTitle = 'Comprobante ' . htmlspecialchars($comp['numero_comprobante']);
$extraCSS  = BASE_URL . '/assets/css/print.css';
require_once '../../includes/header.php';
?>

<link rel="stylesheet" href="<?= $extraCSS ?>">

<div class="page-content" style="max-width:520px;margin:0 auto;padding:20px 16px;">

    <!-- Botones de acción -->
    <div class="ticket-acciones">
        <a href="dashboard.php" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
        <button class="btn btn-primario btn-sm" onclick="printTicket()">
            <i class="fa-solid fa-print"></i> Imprimir
        </button>
        <?php if (in_array($_SESSION['rol'], ['admin','superadmin'])): ?>
        <a href="<?= BASE_URL ?>/roles/admin/ver_comprobante.php?id=<?= $comprobanteId ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-eye"></i> Vista Admin
        </a>
        <?php endif; ?>
    </div>

    <script>
    function printTicket() {
        // Guardar display original
        const originalDisplays = [];
        Array.from(document.body.children).forEach(child => {
            if (child.tagName !== 'SCRIPT') {
                originalDisplays.push({ el: child, display: child.style.display });
                child.style.display = 'none';
            }
        });

        // Crear div temporal de impresión
        const printDiv = document.createElement('div');
        printDiv.id = 'temp-print-div';
        printDiv.innerHTML = document.getElementById('comprobante-print-root').innerHTML;
        document.body.appendChild(printDiv);

        window.print();

        // Restaurar estado
        document.body.removeChild(printDiv);
        originalDisplays.forEach(item => {
            item.el.style.display = item.display;
        });
    }
    </script>

    <!-- ══ COMPROBANTE IMPRIMIBLE ═════════════════════════════ -->
    <div id="comprobante-print-root">
    <div class="ticket-wrapper">

        <?php if ($comp['anulado']): ?>
        <div class="ticket-anulado-banner">★ ANULADO ★</div>
        <?php endif; ?>

        <!-- Logo del restaurante -->
        <?php
        $logoPath = $comp['rest_logo'] ?: null;
        if ($logoPath && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath)):
        ?>
        <div class="ticket-logo">
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo">
        </div>
        <?php else: ?>
        <div class="ticket-logo">
            <img src="<?= BASE_URL ?>/assets/logo.png" alt="Logo" style="max-width:100px;max-height:52px;">
        </div>
        <?php endif; ?>

        <!-- Datos del restaurante -->
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

        <!-- Tipo de comprobante -->
        <div class="ticket-tipo-wrap">
            <span class="ticket-tipo-comp">
                <?php
                if (strtoupper($comp['tipo']) === 'BOLETA') echo 'Boleta de Venta';
                elseif (strtoupper($comp['tipo']) === 'FACTURA') echo 'Factura';
                else echo 'Comprobante Simple';
                ?>
            </span>
        </div>

        <!-- Datos del comprobante -->
        <div class="ticket-section">
            <div class="ticket-row">
                <span class="label">Nº:</span>
                <span class="value"><?= htmlspecialchars($comp['numero_comprobante']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Fecha:</span>
                <span class="value"><?= date('d/m/Y H:i', strtotime($comp['created_at'])) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Cajero:</span>
                <span class="value"><?= htmlspecialchars($comp['cajero_nombre']) ?></span>
            </div>
        </div>

        <!-- Datos del cliente -->
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
            <div class="ticket-row">
                <span class="label">Dir:</span>
                <span class="value"><?= htmlspecialchars($comp['direccion_cliente']) ?></span>
            </div>
            <?php if ($comp['distrito']): ?>
            <div class="ticket-row">
                <span class="label">Ubic:</span>
                <span class="value">
                    <?= htmlspecialchars($comp['distrito']) ?>
                    <?= $comp['provincia']   ? ' - ' . htmlspecialchars($comp['provincia'])   : '' ?>
                    <?= $comp['departamento']? ' - ' . htmlspecialchars($comp['departamento']) : '' ?>
                </span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Tabla de items -->
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
            <div class="ticket-total-row">
                <span>Op. Gravada:</span>
                <span>S/ <?= number_format($comp['subtotal'], 2) ?></span>
            </div>
            <div class="ticket-total-row">
                <span>IGV (18%):</span>
                <span>S/ <?= number_format($comp['igv'], 2) ?></span>
            </div>
            <div class="ticket-total-row total-final">
                <span>TOTAL:</span>
                <span>S/ <?= number_format($comp['total'], 2) ?></span>
            </div>
        </div>

        <!-- Métodos de pago -->
        <?php if (!empty($pagos)): ?>
        <div class="ticket-pagos ticket-section">
            <?php
            $metodoLabel = ['efectivo'=>'Efectivo','yape'=>'Yape/Plin','transferencia'=>'Transferencia','tarjeta'=>'Tarjeta','otro'=>'Otro'];
            foreach ($pagos as $pago):
                $metodo = $pago['metodo'] ?? 'otro';
                $monto  = floatval($pago['monto'] ?? 0);
            ?>
            <div class="ticket-row">
                <span class="label"><?= htmlspecialchars($metodoLabel[$metodo] ?? $metodo) ?>:</span>
                <span class="value">S/ <?= number_format($monto, 2) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Pie de página -->
        <div class="ticket-pie">
            <?= htmlspecialchars($comp['pie_mensaje'] ?: '¡Gracias por su visita!') ?>
        </div>

    </div><!-- /.ticket-wrapper -->
    </div><!-- /#comprobante-print-root -->

</div>

<?php require_once '../../includes/footer.php'; ?>
