<?php
/**
 * roles/cocina/dashboard.php — Vista de cocina en tiempo real
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['cocina', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$pageTitle     = 'Panel Cocina';
require_once '../../includes/header.php';
?>

<style>
body { background: #1A1A2E; }

.cocina-header {
    background: linear-gradient(135deg, #16213E, #0F3460);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #fff;
    border-bottom: 2px solid #E94560;
}
.cocina-header h1 { color: #fff; font-size: 1.4rem; }
.cocina-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .82rem;
}
.cocina-status .dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #27AE60;
    animation: blink 1.5s infinite;
}
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:.3;} }

.cocina-vacia {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: calc(100vh - 120px);
    color: rgba(255,255,255,.5);
    gap: 16px;
    font-size: 1.1rem;
}
.cocina-vacia .icon { font-size: 4rem; }

.cocina-card {
    background: #16213E;
    border: 2px solid #0F3460;
    border-radius: 14px;
    overflow: hidden;
    transition: border-color .2s;
}
.cocina-card.urgente { border-color: #E74C3C; animation: pulse-cocina 2s infinite; }
@keyframes pulse-cocina {
    0%,100% { box-shadow: 0 0 0 0 rgba(231,76,60,0); }
    50%      { box-shadow: 0 0 0 8px rgba(231,76,60,.2); }
}
.cocina-card-header {
    padding: 14px 18px;
    background: #0F3460;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #fff;
}
.cocina-card.urgente .cocina-card-header { background: #E74C3C; }
.ck-mesa    { font-size: 1.8rem; font-weight: 800; }
.ck-tipo    { font-size: .82rem; opacity: .8; margin-top: 2px; }
.ck-tiempo {
    background: rgba(255,255,255,.15);
    border-radius: 99px;
    padding: 5px 12px;
    font-size: .8rem;
    font-weight: 700;
}
.ck-items   { padding: 14px 18px; }
.ck-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.ck-item:last-child { border-bottom: none; }
.ck-qty {
    background: #E94560;
    color: #fff;
    width: 28px; height: 28px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800;
    font-size: .9rem;
    flex-shrink: 0;
}
.ck-item-info { flex: 1; }
.ck-nombre  { color: #fff; font-size: 1rem; font-weight: 700; }
.ck-opciones{ color: rgba(255,255,255,.55); font-size: .78rem; margin-top: 3px; }
.ck-btn-listo {
    padding: 5px 12px;
    border-radius: 7px;
    border: none;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
    flex-shrink: 0;
}
.ck-btn.pendiente       { background: #F39C12; color: #000; }
.ck-btn.en_preparacion  { background: #3498DB; color: #fff; }
.ck-btn.listo           { background: #27AE60; color: #fff; }
</style>

<!-- Audio de alerta -->
<audio id="timbre-audio" preload="auto">
    <source src="<?= BASE_URL ?>/assets/timbre.mp3" type="audio/mpeg">
</audio>

<!-- Cocina Header personalizada -->
<div class="cocina-header">
    <div>
        <h1>👨‍🍳 Panel de Cocina</h1>
        <div style="font-size:.75rem;opacity:.7;" id="cocina-reloj"></div>
    </div>
    <div class="cocina-status">
        <div class="dot"></div>
        <span>En vivo · actualiza cada 10s</span>
        <span id="cocina-count" style="background:rgba(255,255,255,.15);border-radius:99px;padding:4px 12px;font-weight:700;"></span>
    </div>
</div>

<div id="cocina-contenido" style="padding:16px;">
    <div class="cocina-vacia">
        <div class="icon">⏳</div>
        <p>Cargando pedidos...</p>
    </div>
</div>

<script>
const RESTAURANTE_ID = <?= $restauranteId ?>;

// ── ESTADO PREVIO para detectar cambios ──────────────────────
// Mapa: pedidoId → cantidad de items en ese pedido
let estadoPrevio = null; // null = primera carga (no disparar sonido)

function reproducirTimbre() {
    const audio = document.getElementById('timbre-audio');
    if (!audio) return;
    audio.currentTime = 0;
    audio.play().catch(() => {}); // .catch() silencia error si el navegador bloquea autoplay
}

/**
 * Compara el estado previo con el actual y reproduce el timbre
 * si llegó un pedido nuevo O si un pedido existente tiene más ítems.
 */
function detectarCambiosYAlertar(pedidos) {
    if (estadoPrevio === null) {
        // Primera carga — guardar estado sin alertar
        estadoPrevio = construirSnapshot(pedidos);
        return;
    }

    const nuevoSnapshot = construirSnapshot(pedidos);
    let hayNovedad = false;

    for (const [id, count] of nuevoSnapshot) {
        if (!estadoPrevio.has(id)) {
            // Pedido nuevo llegó a la cocina
            hayNovedad = true;
            break;
        }
        if (count > estadoPrevio.get(id)) {
            // Pedido existente recibió más platos
            hayNovedad = true;
            break;
        }
    }

    if (hayNovedad) reproducirTimbre();
    estadoPrevio = nuevoSnapshot;
}

function construirSnapshot(pedidos) {
    const map = new Map();
    (pedidos || []).forEach(p => map.set(p.id, (p.items || []).length));
    return map;
}

// ── RELOJ ─────────────────────────────────────────────────────
function actualizarReloj() {
    const now = new Date();
    const el  = document.getElementById('cocina-reloj');
    if (el) el.textContent = now.toLocaleTimeString('es-PE');
}
actualizarReloj();
setInterval(actualizarReloj, 1000);

// ── RENDER PEDIDOS ────────────────────────────────────────────
function renderPedidos(pedidos) {
    const cont  = document.getElementById('cocina-contenido');
    const count = document.getElementById('cocina-count');

    // Detectar cambios ANTES de renderizar
    detectarCambiosYAlertar(pedidos);

    if (!pedidos || pedidos.length === 0) {
        count.textContent = '0 pedidos';
        cont.innerHTML = `
            <div class="cocina-vacia">
                <div class="icon">✅</div>
                <p>No hay pedidos activos — ¡Todo listo!</p>
            </div>`;
        return;
    }

    count.textContent = `${pedidos.length} pedido(s)`;

    const grid = document.createElement('div');
    grid.className = 'cocina-grid';

    pedidos.forEach(p => {
        const ahora   = new Date();
        const inicio  = new Date(p.created_at);
        const minutos = Math.round((ahora - inicio) / 60000);
        const urgente = minutos >= 20;

        const card = document.createElement('div');
        card.className = `cocina-card${urgente ? ' urgente' : ''}`;
        card.id = `ck-pedido-${p.id}`;

        let itemsHTML = '';

        // Agrupar ítems idénticos (mismo nombre + estado + opciones + notas)
        const grupos = agruparItems(p.items || []);

        grupos.forEach(grupo => {
            const estadoClass = grupo.estado || 'pendiente';
            const etiquetas   = {'pendiente':'⏳ Pendiente','en_preparacion':'🔵 Preparando','listo':'✅ Listo','entregado':'📦 Entregado'};
            const idsJson     = JSON.stringify(grupo.ids);
            itemsHTML += `
                <div class="ck-item">
                    <div class="ck-qty">${grupo.cantidad}</div>
                    <div class="ck-item-info">
                        <div class="ck-nombre">${grupo.nombre}</div>
                        ${grupo.opciones ? `<div class="ck-opciones">· ${grupo.opciones}</div>` : ''}
                        ${grupo.notas   ? `<div class="ck-opciones">📝 ${grupo.notas}</div>`   : ''}
                    </div>
                    <button class="ck-btn-listo ck-btn ${estadoClass}"
                        onclick="cambiarEstadoItem(${idsJson}, '${estadoClass}')">
                        ${etiquetas[estadoClass] || estadoClass}
                    </button>
                </div>`;
        });

        card.innerHTML = `
            <div class="cocina-card-header">
                <div>
                    <div class="ck-mesa">${p.mesa_numero ? 'Mesa ' + p.mesa_numero : 'Llevar'}</div>
                    <div class="ck-tipo">${p.tipo === 'aqui' ? '🏠 Comer aquí' : '🛍️ Para llevar'} · #${p.id}</div>
                </div>
                <div class="ck-tiempo">${minutos} min${urgente ? ' ⚠️' : ''}</div>
            </div>
            <div class="ck-items">${itemsHTML}</div>`;

        grid.appendChild(card);
    });

    cont.innerHTML = '';
    cont.appendChild(grid);
}

// ── AGRUPADOR DE ÍTEMS IDÉNTICOS ──────────────────────────────
/**
 * Agrupa ítems con mismo nombre + estado + opciones + notas.
 * Suma sus cantidades y acumula sus IDs para el cambio de estado en lote.
 * Ítems con nota distinta permanecen separados.
 */
function agruparItems(items) {
    const grupos = new Map();
    items.forEach(item => {
        const key = [
            item.nombre,
            item.estado   || 'pendiente',
            item.opciones || '',
            item.notas    || '',
        ].join('||');

        if (grupos.has(key)) {
            const g = grupos.get(key);
            g.cantidad += (item.cantidad || 1);
            g.ids.push(item.id);
        } else {
            grupos.set(key, {
                ...item,
                cantidad: item.cantidad || 1,
                ids: [item.id],
            });
        }
    });
    return Array.from(grupos.values());
}

// ── CAMBIO DE ESTADO ÍTEM ─────────────────────────────────────
/**
 * ids puede ser un número (ítem único) o un array (grupo de ítems idénticos).
 */
async function cambiarEstadoItem(ids, estadoActual) {
    const estados   = ['pendiente','en_preparacion','listo','entregado'];
    const siguiente = estados[(estados.indexOf(estadoActual) + 1) % estados.length];
    const itemIds   = Array.isArray(ids) ? ids : [ids];

    try {
        const res  = await fetch(BASE_URL + '/api/marcar_item_listo.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ item_ids: itemIds, estado: siguiente })
        });
        const json = await res.json();
        if (!json.success) Toast.advertencia(json.message);
        // El polling actualizará la vista en el próximo ciclo
    } catch(e) {}
}

// ── INICIO DEL POLLING ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    iniciarPolling(
        `${BASE_URL}/api/get_pedidos_activos.php?restaurante_id=${RESTAURANTE_ID}`,
        renderPedidos,
        10000
    );
});
</script>

<?php require_once '../../includes/footer.php'; ?>
