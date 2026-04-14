<?php
/**
 * roles/atencion/comanda.php — Tomar y añadir platos a un pedido
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$pedidoId = (int)($_GET['pedido_id'] ?? 0);
$mesaId   = (int)($_GET['mesa_id']   ?? 0);

if (!$pedidoId) {
    header('Location: dashboard.php');
    exit;
}

$db = getDB();

// Cargar pedido
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

// Categorías activas
$stCats = $db->prepare("SELECT * FROM categorias WHERE restaurante_id = ? AND activo = 1 ORDER BY orden");
$stCats->execute([$restauranteId]);
$categorias = $stCats->fetchAll();

// Productos activos
$stProds = $db->prepare("SELECT * FROM productos WHERE restaurante_id = ? AND activo = 1 ORDER BY categoria_id, nombre");
$stProds->execute([$restauranteId]);
$productos = $stProds->fetchAll();

// Items actuales del pedido
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

$pageTitle = "Comanda · $mesaLabel";
require_once '../../includes/header.php';
?>

<style>
.comanda-layout { display: grid; grid-template-columns: 1fr 340px; min-height: calc(100vh - 60px); }
@media (max-width: 900px) { .comanda-layout { grid-template-columns: 1fr; } }
</style>

<div class="comanda-layout">

    <!-- ── Menú ─────────────────────────── -->
    <div style="padding:16px;overflow-y:auto;max-height:calc(100vh - 60px);">
        <div class="d-flex align-center justify-between mb-12">
            <div>
                <h2><?= $pedido['tipo']==='aqui'?'🏠':'🛍️' ?> <?= htmlspecialchars($mesaLabel) ?></h2>
                <p>Selecciona productos para añadir al pedido</p>
            </div>
            <a href="dashboard.php" class="btn btn-ghost btn-sm">← Volver</a>
        </div>

        <!-- Categorías tabs -->
        <div class="categorias-tabs mb-12">
            <button class="tab-cat active" onclick="filtrarCat('', this)">Todos</button>
            <?php foreach ($categorias as $cat): ?>
            <button class="tab-cat" onclick="filtrarCat('<?= $cat['id'] ?>', this)">
                <?= htmlspecialchars($cat['icono'].' '.$cat['nombre']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Productos -->
        <div class="productos-grid" id="menu-grid">
            <?php foreach ($productos as $p): ?>
            <div class="producto-card menu-item" data-cat="<?= $p['categoria_id'] ?>" data-id="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
                <div class="producto-thumb">
                    <?php
                    $catIcono = '🍽️';
                    foreach ($categorias as $c) { if ($c['id'] == $p['categoria_id']) { $catIcono = $c['icono']; break; } }
                    echo htmlspecialchars($catIcono);
                    ?>
                </div>
                <div class="producto-info">
                    <div class="producto-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                    <div class="producto-precio">S/ <?= number_format($p['precio'],2) ?></div>
                    <?php if ($p['tiene_opciones']): ?>
                    <div style="font-size:.68rem;color:var(--naranja);font-weight:600;">⚙️ Personalizable</div>
                    <?php endif; ?>
                    <button class="btn-agregar" onclick="agregarProducto(<?= htmlspecialchars(json_encode($p)) ?>)">
                        ➕ Agregar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Panel lateral del pedido ──── -->
    <div style="background:#fff;border-left:1px solid var(--borde);display:flex;flex-direction:column;max-height:calc(100vh - 60px);position:sticky;top:60px;">

        <!-- Encabezado del pedido -->
        <div style="padding:16px;background:linear-gradient(135deg,var(--rojo),var(--rojo-dark));color:#fff;">
            <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($mesaLabel) ?></div>
            <div style="font-size:.8rem;opacity:.85;"><?= $pedido['tipo']==='aqui'?'🏠 Comer aquí':'🛍️ Para llevar' ?> · Pedido #<?= $pedidoId ?></div>
        </div>

        <!-- Items del pedido -->
        <div id="lista-items" style="flex:1;overflow-y:auto;padding:12px 16px;">
            <?php if ($items): ?>
            <?php foreach ($items as $item): ?>
            <div class="pedido-item" id="item-<?= $item['id'] ?>">
                <div style="flex:1;">
                    <div class="pedido-item-nombre"><?= $item['cantidad'] ?>x <?= htmlspecialchars($item['producto_nombre']) ?></div>
                    <?php if ($item['opciones_texto']): ?>
                    <div class="pedido-item-opciones">· <?= htmlspecialchars($item['opciones_texto']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="pedido-item-precio">S/ <?= number_format($item['subtotal'],2) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state" style="padding:30px 0;">
                <div class="icon">🍽️</div>
                <p>Agrega platos del menú</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Nuevos items pendientes -->
        <div id="lista-nuevos" style="padding:0 16px;border-top:2px dashed var(--naranja);display:none;">
            <div style="font-size:.75rem;font-weight:700;color:var(--naranja);padding:8px 0;">⏳ Por enviar a cocina:</div>
        </div>

        <!-- Total y botones -->
        <div style="padding:16px;border-top:1px solid var(--borde);">
            <div class="pedido-total">
                <span>Total actual</span>
                <span id="total-actual">S/ <?= number_format($pedido['total'],2) ?></span>
            </div>
            <div id="nuevo-total-row" style="display:none;" class="resumen-row" style="color:var(--naranja);">
                <span>+ Nuevos items</span>
                <span id="nuevo-total-val">S/ 0.00</span>
            </div>
            <button class="btn btn-primario btn-full btn-lg" id="btn-enviar" onclick="enviarACocina()" disabled>
                📤 Enviar a cocina
            </button>
            <a href="cobrar.php?pedido_id=<?= $pedidoId ?>" class="btn btn-exito btn-full mt-8" style="min-height:44px;display:flex;align-items:center;justify-content:center;gap:8px;border-radius:8px;font-weight:700;">
                💰 Ir a cobrar
            </a>
        </div>
    </div>

</div>

<script>
const PEDIDO_ID        = <?= $pedidoId ?>;
const RESTAURANTE_ID   = <?= $restauranteId ?>;
let nuevosItems        = []; // items añadidos en esta sesión pendientes de enviar
let nuevoTotal         = 0;

function filtrarCat(catId, btn) {
    document.querySelectorAll('.tab-cat').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.menu-item').forEach(p => {
        p.style.display = (!catId || p.dataset.cat == catId) ? '' : 'none';
    });
}

async function agregarProducto(producto) {
    let selecciones = [];

    if (producto.tiene_opciones == 1) {
        selecciones = await mostrarOpcionesSecuenciales(producto.id, producto.nombre);
        if (selecciones === null) return; // cancelado
    }

    const item = {
        producto_id:  producto.id,
        nombre:       producto.nombre,
        precio:       parseFloat(producto.precio),
        cantidad:     1,
        selecciones:  selecciones,
        opciones_str: '',
    };

    // Cargar nombres de opciones si tiene
    if (selecciones.length) {
        // Usar los datos ya obtenidos — almacenar textualmente
        item.opciones_str = '(opciones seleccionadas)';
    }

    nuevosItems.push(item);
    nuevoTotal += item.precio;

    renderNuevosItems();
    Toast.exito(`✅ ${producto.nombre} añadido`);
}

function renderNuevosItems() {
    const cont = document.getElementById('lista-nuevos');
    if (!nuevosItems.length) {
        cont.style.display = 'none';
        document.getElementById('btn-enviar').disabled = true;
        document.getElementById('nuevo-total-row').style.display = 'none';
        return;
    }
    cont.style.display = 'block';
    cont.innerHTML = '<div style="font-size:.75rem;font-weight:700;color:var(--naranja);padding:8px 0;">⏳ Por enviar a cocina:</div>';
    nuevosItems.forEach((it, i) => {
        cont.innerHTML += `
            <div class="pedido-item">
                <div style="flex:1;">
                    <div class="pedido-item-nombre">1x ${it.nombre}</div>
                    ${it.opciones_str ? `<div class="pedido-item-opciones">${it.opciones_str}</div>` : ''}
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="pedido-item-precio">S/ ${it.precio.toFixed(2)}</span>
                    <button onclick="quitarNuevo(${i})" style="background:none;border:none;color:var(--gris);cursor:pointer;font-size:1rem;">✕</button>
                </div>
            </div>`;
    });
    document.getElementById('btn-enviar').disabled = false;
    document.getElementById('nuevo-total-row').style.display = 'flex';
    document.getElementById('nuevo-total-val').textContent = `S/ ${nuevoTotal.toFixed(2)}`;
}

function quitarNuevo(idx) {
    nuevoTotal -= nuevosItems[idx].precio;
    nuevosItems.splice(idx, 1);
    renderNuevosItems();
}

async function enviarACocina() {
    if (!nuevosItems.length) return;
    const btn = document.getElementById('btn-enviar');
    btn.disabled = true; btn.textContent = '⏳ Enviando...';

    try {
        const res  = await fetch('/sistema_restaurante/api/agregar_item.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({ pedido_id: PEDIDO_ID, items: nuevosItems })
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito('🚀 Pedido enviado a cocina!');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.error(json.message);
            btn.disabled = false;
            btn.textContent = '📤 Enviar a cocina';
        }
    } catch(e) {
        Toast.error('Error de conexión');
        btn.disabled = false;
        btn.textContent = '📤 Enviar a cocina';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
