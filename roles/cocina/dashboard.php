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

function actualizarReloj() {
    const now = new Date();
    const el  = document.getElementById('cocina-reloj');
    if (el) el.textContent = now.toLocaleTimeString('es-PE');
}
actualizarReloj();
setInterval(actualizarReloj, 1000);

function renderPedidos(pedidos) {
    const cont  = document.getElementById('cocina-contenido');
    const count = document.getElementById('cocina-count');

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
        (p.items || []).forEach(item => {
            const estadoClass = item.estado || 'pendiente';
            const etiquetas   = {'pendiente':'⏳ Pendiente','en_preparacion':'🔵 Preparando','listo':'✅ Listo','entregado':'📦 Entregado'};
            itemsHTML += `
                <div class="ck-item" id="ck-item-${item.id}">
                    <div class="ck-qty">${item.cantidad}</div>
                    <div class="ck-item-info">
                        <div class="ck-nombre">${item.nombre}</div>
                        ${item.opciones ? `<div class="ck-opciones">· ${item.opciones}</div>` : ''}
                        ${item.notas ? `<div class="ck-opciones">📝 ${item.notas}</div>` : ''}
                    </div>
                    <button class="ck-btn-listo ck-btn ${estadoClass}"
                        onclick="cambiarEstadoItem(${item.id}, '${estadoClass}')">
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

async function cambiarEstadoItem(itemId, estadoActual) {
    const estados    = ['pendiente','en_preparacion','listo','entregado'];
    const siguiente  = estados[(estados.indexOf(estadoActual) + 1) % estados.length];

    try {
        const res  = await fetch('/sistema_restaurante/api/marcar_item_listo.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ item_id: itemId, estado: siguiente })
        });
        const json = await res.json();
        if (!json.success) Toast.advertencia(json.message);
        // El polling actualizará la vista en el próximo ciclo
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarPolling(
        `/sistema_restaurante/api/get_pedidos_activos.php?restaurante_id=${RESTAURANTE_ID}`,
        renderPedidos,
        10000
    );
});

</script>

<?php require_once '../../includes/footer.php'; ?>
