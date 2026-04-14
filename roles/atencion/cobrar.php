<?php
/**
 * roles/atencion/cobrar.php — Flujo de cobro con pago mixto
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$pedidoId = (int)($_GET['pedido_id'] ?? 0);

if (!$pedidoId) {
    header('Location: dashboard.php');
    exit;
}

$db = getDB();

// Cargar pedido con items
$stPed = $db->prepare("
    SELECT pe.*, m.numero AS mesa_numero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    WHERE pe.id = ? AND pe.restaurante_id = ? AND pe.estado = 'activo'
");
$stPed->execute([$pedidoId, $restauranteId]);
$pedido = $stPed->fetch();
if (!$pedido) {
    header('Location: dashboard.php');
    exit;
}

// Items del pedido
$stItems = $db->prepare("
    SELECT pi.*, pr.nombre AS producto_nombre,
           GROUP_CONCAT(ov.valor SEPARATOR ' · ') AS opciones_texto
    FROM pedido_items pi
    JOIN productos pr ON pr.id = pi.producto_id
    LEFT JOIN pedido_item_opciones pio ON pio.item_id = pi.id
    LEFT JOIN opciones_valor ov ON ov.id = pio.valor_id
    WHERE pi.pedido_id = ?
    GROUP BY pi.id
    ORDER BY pi.created_at ASC
");
$stItems->execute([$pedidoId]);
$items = $stItems->fetchAll();

$mesaLabel = $pedido['mesa_numero'] ? 'Mesa '.$pedido['mesa_numero'] : 'Para llevar';
$total     = $pedido['total'];

$pageTitle = "Cobrar · $mesaLabel";
require_once '../../includes/header.php';
?>

<div style="max-width:560px;margin:0 auto;padding:16px;" class="page-content">

    <div class="d-flex align-center justify-between mb-16">
        <div>
            <h1>💰 Cobrar pedido</h1>
            <p><?= $pedido['tipo']==='aqui'?'🏠':'🛍️' ?> <?= htmlspecialchars($mesaLabel) ?> · #<?= $pedidoId ?></p>
        </div>
        <a href="comanda.php?pedido_id=<?= $pedidoId ?>&mesa_id=<?= $pedido['mesa_id'] ?>" class="btn btn-ghost btn-sm">← Volver</a>
    </div>

    <!-- Resumen del pedido -->
    <div class="card mb-16">
        <div class="card-title">📋 Resumen del pedido</div>
        <?php foreach ($items as $item): ?>
        <div class="pedido-item">
            <div style="flex:1;">
                <div class="pedido-item-nombre"><?= $item['cantidad'] ?>x <?= htmlspecialchars($item['producto_nombre']) ?></div>
                <?php if ($item['opciones_texto']): ?>
                <div class="pedido-item-opciones">· <?= htmlspecialchars($item['opciones_texto']) ?></div>
                <?php endif; ?>
            </div>
            <div class="pedido-item-precio">S/ <?= number_format($item['subtotal'],2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="pedido-total" style="margin-top:12px;padding-top:12px;border-top:2px solid var(--borde);">
            <span>TOTAL A COBRAR</span>
            <span class="text-rojo" style="font-size:1.4rem;">S/ <?= number_format($total,2) ?></span>
        </div>
    </div>

    <!-- Métodos de pago -->
    <div class="card mb-16">
        <div class="card-title">💳 Método(s) de pago</div>
        <div class="metodos-pago">
            <?php
            $metodos = [
                ['efectivo',      '💵', 'Efectivo'],
                ['yape',          '📱', 'Yape / Plin'],
                ['transferencia', '🏦', 'Transferencia'],
                ['tarjeta',       '💳', 'Tarjeta'],
                ['otro',          '🔄', 'Otro'],
            ];
            foreach ($metodos as [$key, $icon, $label]): ?>
            <div class="metodo-item" id="metodo-<?= $key ?>">
                <div class="metodo-header" onclick="toggleMetodo('<?= $key ?>')">
                    <div class="metodo-check" id="check-<?= $key ?>">✓</div>
                    <span class="metodo-icon"><?= $icon ?></span>
                    <span class="metodo-nombre"><?= $label ?></span>
                </div>
                <div class="metodo-monto-wrap">
                    <label class="form-label" style="font-size:.78rem;">Monto (S/)</label>
                    <input type="number" id="monto-<?= $key ?>" class="form-control"
                        step="0.10" min="0" placeholder="0.00"
                        oninput="calcularDiferencia()">
                    <?php if ($key === 'yape' || $key === 'transferencia'): ?>
                    <input type="text" id="ref-<?= $key ?>" class="form-control mt-8"
                        placeholder="N° de operación (opcional)">
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Diferencia -->
        <div class="diferencia-aviso" id="aviso-diferencia"></div>

        <!-- Resumen de pago -->
        <div class="resumen-totales mt-12">
            <div class="resumen-row"><span>Total del pedido</span><span>S/ <?= number_format($total,2) ?></span></div>
            <div class="resumen-row"><span>Total asignado</span><span id="total-asignado">S/ 0.00</span></div>
            <div class="resumen-row total"><span>Diferencia</span><span id="diferencia-val">S/ <?= number_format($total,2) ?></span></div>
        </div>
    </div>

    <!-- Notas opcionales -->
    <div class="card mb-16">
        <div class="card-title">📝 Notas del cobro (opcional)</div>
        <textarea id="notas-cobro" class="form-control" rows="2" placeholder="Ej: cliente pagó con billete de 50..."></textarea>
    </div>

    <!-- Botón confirmar -->
    <button class="btn btn-exito btn-full btn-lg" id="btn-confirmar-cobro" onclick="confirmarCobro()">
        ✅ Confirmar cobro
    </button>

</div>

<script>
const TOTAL_PEDIDO = <?= $total ?>;
const PEDIDO_ID    = <?= $pedidoId ?>;
const METODOS      = ['efectivo','yape','transferencia','tarjeta','otro'];
const activos      = new Set();

function toggleMetodo(key) {
    const el = document.getElementById(`metodo-${key}`);
    if (activos.has(key)) {
        activos.delete(key);
        el.classList.remove('activo');
        document.getElementById(`monto-${key}`).value = '';
    } else {
        activos.add(key);
        el.classList.add('activo');
        // Si solo hay un método activo, prellenar con el total
        if (activos.size === 1) {
            document.getElementById(`monto-${key}`).value = TOTAL_PEDIDO.toFixed(2);
        }
    }
    calcularDiferencia();
}

function calcularDiferencia() {
    let asignado = 0;
    activos.forEach(key => {
        asignado += parseFloat(document.getElementById(`monto-${key}`).value || 0);
    });

    const diferencia = TOTAL_PEDIDO - asignado;
    document.getElementById('total-asignado').textContent = `S/ ${asignado.toFixed(2)}`;
    document.getElementById('diferencia-val').textContent  = `S/ ${Math.abs(diferencia).toFixed(2)}`;

    const aviso = document.getElementById('aviso-diferencia');
    if (Math.abs(diferencia) > 0.10) {
        aviso.classList.add('show');
        aviso.textContent = diferencia > 0
            ? `⚠️ Faltan S/ ${diferencia.toFixed(2)} por asignar`
            : `ℹ️ Hay S/ ${Math.abs(diferencia).toFixed(2)} de vuelto/propina`;
    } else {
        aviso.classList.remove('show');
    }
}

async function confirmarCobro() {
    if (activos.size === 0) {
        Toast.advertencia('Selecciona al menos un método de pago');
        return;
    }

    const pagos = [];
    for (const key of activos) {
        const monto = parseFloat(document.getElementById(`monto-${key}`).value || 0);
        if (monto <= 0) {
            Toast.advertencia(`Ingresa el monto para: ${key}`);
            return;
        }
        const pago = { metodo: key, monto };
        const refEl = document.getElementById(`ref-${key}`);
        if (refEl) pago.referencia = refEl.value.trim();
        pagos.push(pago);
    }

    const diferencia = TOTAL_PEDIDO - pagos.reduce((s, p) => s + p.monto, 0);
    if (Math.abs(diferencia) > 0.10) {
        const ok = confirm(`Hay una diferencia de S/ ${Math.abs(diferencia).toFixed(2)}. ¿Confirmar de todas formas?`);
        if (!ok) return;
    }

    const btn = document.getElementById('btn-confirmar-cobro');
    btn.disabled = true; btn.textContent = '⏳ Procesando...';

    try {
        const res  = await fetch('/sistema_restaurante/api/cobrar_pedido.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({
                pedido_id: PEDIDO_ID,
                pagos,
                notas: document.getElementById('notas-cobro').value,
            })
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito('✅ ¡Cobro registrado exitosamente!');
            setTimeout(() => { window.location.href = '/sistema_restaurante/roles/atencion/dashboard.php'; }, 1200);
        } else {
            Toast.error(json.message);
            btn.disabled = false;
            btn.textContent = '✅ Confirmar cobro';
        }
    } catch(e) {
        Toast.error('Error de conexión');
        btn.disabled = false;
        btn.textContent = '✅ Confirmar cobro';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
