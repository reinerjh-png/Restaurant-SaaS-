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

if (!$pedidoId) { header('Location: dashboard.php'); exit; }

$db = getDB();

$stPed = $db->prepare("
    SELECT pe.*, m.numero AS mesa_numero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    WHERE pe.id = ? AND pe.restaurante_id = ? AND pe.estado = 'activo'
");
$stPed->execute([$pedidoId, $restauranteId]);
$pedido = $stPed->fetch();
if (!$pedido) { header('Location: dashboard.php'); exit; }

$stCats = $db->prepare("SELECT * FROM categorias WHERE restaurante_id = ? AND activo = 1 ORDER BY orden");
$stCats->execute([$restauranteId]);
$categorias = $stCats->fetchAll();

$stProds = $db->prepare("SELECT * FROM productos WHERE restaurante_id = ? AND activo = 1 ORDER BY categoria_id, nombre");
$stProds->execute([$restauranteId]);
$productos = $stProds->fetchAll();

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
$hideLogout = true;
require_once '../../includes/header.php';
?>

<style>
.comanda-layout { display: grid; grid-template-columns: 1fr 340px; min-height: calc(100vh - 60px); position: relative; }
.panel-pedido {
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px);
    position: sticky;
    top: 60px;
    z-index: 100;
}
.barra-ver-pedido { display: none; }
.panel-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999; }
.btn-cerrar-panel-movil { display: none; }

@media (max-width: 900px) {
    .comanda-layout { grid-template-columns: 1fr; }
    .panel-pedido {
        position: fixed;
        top: auto; bottom: 0; left: 0; right: 0;
        height: 85vh;
        box-shadow: 0 -4px 20px rgba(0,0,0,.12);
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        transform: translateY(100%);
        transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
        z-index: 1000;
        border-left: none;
    }
    .panel-pedido.abierto { transform: translateY(0); }
    .barra-ver-pedido { display: flex; }
    .panel-overlay.activo { display: block; }
    .btn-cerrar-panel-movil {
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.25);
        border: none;
        color: #fff;
        width: 32px; height: 32px;
        border-radius: 50%;
        font-weight: 700;
        cursor: pointer;
        flex-shrink: 0;
    }
    .menu-scroll-area { padding-bottom: 80px !important; }
}
</style>

<div class="comanda-layout">

    <!-- Menú -->
    <div class="menu-scroll-area" style="padding:16px;overflow-y:auto;max-height:calc(100vh - 60px);">
        <div class="d-flex align-center justify-between mb-12">
            <div>
                <h2>
                    <i class="fa-solid <?= $pedido['tipo']==='aqui' ? 'fa-house' : 'fa-bag-shopping' ?>"></i>
                    <?= htmlspecialchars($mesaLabel) ?>
                </h2>
                <p>Selecciona productos para añadir al pedido</p>
            </div>
            <a href="dashboard.php" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="mb-12">
            <div class="input-icon-wrap">
                <span class="input-icon-left"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="buscador-platos" class="form-control"
                    placeholder="Buscar plato por nombre…"
                    oninput="buscarPlatos(this.value)"
                    style="border-radius:999px;">
            </div>
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
            <div class="producto-card menu-item"
                data-cat="<?= $p['categoria_id'] ?>"
                data-id="<?= $p['id'] ?>"
                data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
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
                    <div style="font-size:.68rem;color:var(--warning);font-weight:600;">
                        <i class="fa-solid fa-sliders"></i> Personalizable
                    </div>
                    <?php endif; ?>
                    <button class="btn-agregar" onclick="agregarProducto(<?= htmlspecialchars(json_encode($p)) ?>)">
                        <i class="fa-solid fa-plus"></i> Agregar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Panel lateral del pedido -->
    <div class="panel-overlay" id="panel-overlay" onclick="togglePanelPedido()"></div>
    <div class="panel-pedido" id="panel-pedido">

        <!-- Encabezado del pedido -->
        <div style="padding:16px;background:var(--primary);color:#fff;border-radius:inherit;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <div>
                <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($mesaLabel) ?></div>
                <div style="font-size:.8rem;opacity:.85;">
                    <i class="fa-solid <?= $pedido['tipo']==='aqui' ? 'fa-house' : 'fa-bag-shopping' ?>"></i>
                    <?= $pedido['tipo']==='aqui' ? 'Comer aquí' : 'Para llevar' ?>
                    · Pedido #<?= $pedidoId ?>
                </div>
            </div>
            <button class="btn-cerrar-panel-movil" onclick="togglePanelPedido()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Items del pedido (ya enviados) -->
        <div id="lista-items" style="flex:1;overflow-y:auto;padding:12px 16px;min-height:0;">
            <?php if ($items): ?>
            <?php foreach ($items as $item): ?>
            <div class="pedido-item" id="item-<?= $item['id'] ?>">
                <div style="flex:1;">
                    <div class="pedido-item-nombre"><?= $item['cantidad'] ?>x <?= htmlspecialchars($item['producto_nombre']) ?></div>
                    <?php if ($item['opciones_texto']): ?>
                    <div class="pedido-item-opciones">&middot; <?= htmlspecialchars($item['opciones_texto']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="pedido-item-precio">S/ <?= number_format($item['subtotal'],2) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state" style="padding:30px 0;">
                <div class="icon"><i class="fa-solid fa-utensils"></i></div>
                <p>Agrega platos del men&uacute;</p>
            </div>
            <?php endif; ?>
        </div><!-- /#lista-items -->

        <!-- Nuevos items pendientes (max-height con scroll para no empujar botones) -->
        <div id="lista-nuevos" style="padding:0 16px;border-top:2px dashed var(--warning);display:none;max-height:220px;overflow-y:auto;flex-shrink:0;">

            <div style="font-size:.75rem;font-weight:700;color:var(--warning);padding:8px 0;">
                <i class="fa-solid fa-hourglass-half"></i> Por enviar a cocina:
            </div>
        </div>

        <!-- Total y botones -->
        <div style="padding:16px;border-top:1px solid var(--border);flex-shrink:0;">
            <div class="pedido-total">
                <span>Total actual</span>
                <span id="total-actual">S/ <?= number_format($pedido['total'],2) ?></span>
            </div>
            <div id="nuevo-total-row" style="display:none;" class="resumen-row" style="color:var(--warning);">
                <span>+ Nuevos items</span>
                <span id="nuevo-total-val">S/ 0.00</span>
            </div>
            <button class="btn btn-primario btn-full btn-lg" id="btn-enviar" onclick="enviarACocina()" disabled>
                <i class="fa-solid fa-paper-plane"></i> Enviar a cocina
            </button>
            <div id="cobrar-wrap" style="margin-top:8px;">
                <a href="cobrar.php?pedido_id=<?= $pedidoId ?>"
                   id="btn-cobrar"
                   class="btn btn-exito btn-full"
                   style="min-height:48px;display:flex;align-items:center;justify-content:center;gap:8px;border-radius:var(--radius-md);font-weight:700;">
                    <i class="fa-solid fa-sack-dollar"></i> Ir a cobrar
                </a>
                <div id="cobrar-aviso" style="display:none;margin-top:6px;font-size:.75rem;color:var(--warning);text-align:center;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Env&iacute;a los platos a cocina antes de cobrar
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Barra inferior fija para móviles -->
<button class="barra-ver-pedido" onclick="togglePanelPedido()">
    <div class="barra-left">
        <i class="fa-solid fa-bag-shopping" style="font-size:1.2rem;"></i>
        <span class="barra-badge" id="barra-items-count"><?= count($items) ?> plato(s)</span>
    </div>
    <span class="barra-subtotal" id="barra-subtotal">S/ <?= number_format($pedido['total'], 2) ?></span>
    <span class="barra-cta">Ver pedido <i class="fa-solid fa-arrow-right"></i></span>
</button>

<script>
const PEDIDO_ID      = <?= $pedidoId ?>;
const RESTAURANTE_ID = <?= $restauranteId ?>;
let nuevosItems      = [];
let nuevoTotal       = 0;
let catActual = '', textoBuscado = '';

function aplicarFiltros() {
    const texto = textoBuscado.toLowerCase();
    document.querySelectorAll('.menu-item').forEach(p => {
        const nombre = (p.dataset.nombre || '').toLowerCase();
        const cat    = p.dataset.cat;
        p.style.display = ((!catActual || cat == catActual) && (!texto || nombre.includes(texto))) ? '' : 'none';
    });
}

function filtrarCat(catId, btn) {
    document.querySelectorAll('.tab-cat').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    catActual = catId;
    aplicarFiltros();
}

function buscarPlatos(texto) { textoBuscado = texto; aplicarFiltros(); }

async function agregarProducto(producto) {
    let selecciones = [];
    if (producto.tiene_opciones == 1) {
        selecciones = await mostrarOpcionesSecuenciales(producto.id, producto.nombre);
        if (selecciones === null) return;
    }

    const item = {
        producto_id:  producto.id,
        nombre:       producto.nombre,
        precio:       parseFloat(producto.precio),
        cantidad:     1,
        selecciones:  selecciones,
        opciones_str: selecciones.length ? '(opciones seleccionadas)' : '',
        notas:        '',
    };

    nuevosItems.push(item);
    nuevoTotal += item.precio;
    renderNuevosItems();
    Toast.exito(`${producto.nombre} añadido`);
}

function renderNuevosItems() {
    const cont = document.getElementById('lista-nuevos');
    actualizarCountItems();
    if (!nuevosItems.length) {
        cont.style.display = 'none';
        document.getElementById('btn-enviar').disabled = true;
        document.getElementById('nuevo-total-row').style.display = 'none';
        return;
    }
    cont.style.display = 'block';
    cont.innerHTML = `<div style="font-size:.75rem;font-weight:700;color:var(--warning);padding:8px 0;">
        <i class="fa-solid fa-hourglass-half"></i> Por enviar a cocina:
    </div>`;
    nuevosItems.forEach((it, i) => {
        cont.innerHTML += `
            <div class="pedido-item" style="flex-direction:column;align-items:stretch;gap:6px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;">
                        <div class="pedido-item-nombre">1x ${it.nombre}</div>
                        ${it.opciones_str ? `<div class="pedido-item-opciones">${it.opciones_str}</div>` : ''}
                    </div>
                    <span class="pedido-item-precio">S/ ${it.precio.toFixed(2)}</span>
                    <button onclick="quitarNuevo(${i})"
                        style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.875rem;flex-shrink:0;min-height:32px;display:flex;align-items:center;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <textarea
                    id="nota-item-${i}"
                    rows="1"
                    placeholder="Nota para cocina (opcional)\u2026"
                    oninput="actualizarNota(${i}, this.value)"
                    style="width:100%;font-size:.78rem;border:1.5px dashed var(--border);border-radius:var(--radius-sm);padding:6px 8px;background:var(--bg-secondary);color:var(--text-primary);resize:none;font-family:var(--font);outline:none;transition:border-color var(--transition);"
                    onfocus="this.style.borderColor='var(--warning)'"
                    onblur="this.style.borderColor='var(--border)'"
                >${it.notas}</textarea>
            </div>`;
    });
    document.getElementById('btn-enviar').disabled = false;
    document.getElementById('nuevo-total-row').style.display = 'flex';
    document.getElementById('nuevo-total-val').textContent = `S/ ${nuevoTotal.toFixed(2)}`;
    // Bloquear "Ir a cobrar" mientras haya ítems pendientes
    actualizarEstadoCobrar();
}

function actualizarNota(idx, valor) {
    if (nuevosItems[idx]) nuevosItems[idx].notas = valor.trim();
}

function quitarNuevo(idx) {
    nuevoTotal -= nuevosItems[idx].precio;
    nuevosItems.splice(idx, 1);
    renderNuevosItems();
}

function actualizarEstadoCobrar() {
    const btnCobrar = document.getElementById('btn-cobrar');
    const aviso     = document.getElementById('cobrar-aviso');
    const hayPendientes = nuevosItems.length > 0;
    if (hayPendientes) {
        btnCobrar.style.opacity = '0.4';
        btnCobrar.style.pointerEvents = 'none';
        aviso.style.display = 'block';
    } else {
        btnCobrar.style.opacity = '';
        btnCobrar.style.pointerEvents = '';
        aviso.style.display = 'none';
    }
}

function actualizarCountItems() {
    const baseItems  = <?= count($items) ?>;
    const totalItems = baseItems + nuevosItems.length;
    const totalBase  = <?= $pedido['total'] ?>;
    const totalMostrar = totalBase + nuevoTotal;
    const barraCount = document.getElementById('barra-items-count');
    const barraSub   = document.getElementById('barra-subtotal');
    if (barraCount) barraCount.textContent = `${totalItems} plato(s)`;
    if (barraSub)   barraSub.textContent   = `S/ ${totalMostrar.toFixed(2)}`;
}

function togglePanelPedido() {
    document.getElementById('panel-pedido').classList.toggle('abierto');
    document.getElementById('panel-overlay').classList.toggle('activo');
}

async function enviarACocina() {
    if (!nuevosItems.length) return;
    const btn = document.getElementById('btn-enviar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

    try {
        const res  = await fetch(`${BASE_URL}/api/agregar_item.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({ pedido_id: PEDIDO_ID, items: nuevosItems })
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito('¡Pedido enviado a cocina!');
            setTimeout(() => window.location.href = 'dashboard.php', 800);
        } else {
            Toast.error(json.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar a cocina';
        }
    } catch(e) {
        Toast.error('Error de conexión');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar a cocina';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
