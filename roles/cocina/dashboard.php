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

/* Forzar header oscuro en cocina */
.navbar { background: #0F0F1A; border-bottom: 2px solid #1A1A2E; }
</style>

<!-- Audio de alerta -->
<audio id="timbre-audio" preload="auto">
    <source src="<?= BASE_URL ?>/assets/timbre.mp3" type="audio/mpeg">
</audio>

<!-- Cocina Header personalizada -->
<div class="cocina-header">
    <div>
        <h1><i class="fa-solid fa-kitchen-set"></i> Panel de Cocina</h1>
        <div style="font-size:.75rem;opacity:.7;" id="cocina-reloj"></div>
    </div>
    <div class="cocina-status">
        <div class="dot"></div>
        <span>En vivo · actualiza cada 10s</span>
        <span id="cocina-count" style="background:rgba(255,255,255,.15);border-radius:999px;padding:4px 12px;font-weight:700;"></span>
    </div>
</div>

<div id="cocina-contenido" style="padding:16px;">
    <div class="cocina-vacia">
        <div class="icon"><i class="fa-solid fa-hourglass-half"></i></div>
        <p>Cargando pedidos...</p>
    </div>
</div>

<script>
const RESTAURANTE_ID = <?= $restauranteId ?>;

let estadoPrevio = null;

function reproducirTimbre() {
    const audio = document.getElementById('timbre-audio');
    if (!audio) return;
    audio.currentTime = 0;
    audio.play().catch(() => {});
}

function detectarCambiosYAlertar(pedidos) {
    if (estadoPrevio === null) {
        estadoPrevio = construirSnapshot(pedidos);
        return;
    }
    const nuevoSnapshot = construirSnapshot(pedidos);
    let hayNovedad = false;
    for (const [id, count] of nuevoSnapshot) {
        if (!estadoPrevio.has(id)) { hayNovedad = true; break; }
        if (count > estadoPrevio.get(id)) { hayNovedad = true; break; }
    }
    if (hayNovedad) reproducirTimbre();
    estadoPrevio = nuevoSnapshot;
}

function construirSnapshot(pedidos) {
    const map = new Map();
    (pedidos || []).forEach(p => map.set(p.id, (p.items || []).length));
    return map;
}

function actualizarReloj() {
    const el = document.getElementById('cocina-reloj');
    if (el) el.textContent = new Date().toLocaleTimeString('es-PE');
}
actualizarReloj();
setInterval(actualizarReloj, 1000);

function renderPedidos(pedidos) {
    const cont  = document.getElementById('cocina-contenido');
    const count = document.getElementById('cocina-count');

    detectarCambiosYAlertar(pedidos);

    if (!pedidos || pedidos.length === 0) {
        count.textContent = '0 pedidos';
        cont.innerHTML = `
            <div class="cocina-vacia">
                <div class="icon"><i class="fa-solid fa-circle-check" style="color:var(--success);"></i></div>
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

        const grupos = agruparItems(p.items || []);
        let itemsHTML = '';

        grupos.forEach(grupo => {
            const estadoClass = grupo.estado || 'pendiente';
            const etiquetas = {
                'pendiente':      '<i class="fa-solid fa-hourglass-half"></i> Pendiente',
                'en_preparacion': '<i class="fa-solid fa-fire-burner"></i> Preparando',
                'listo':          '<i class="fa-solid fa-circle-check"></i> Listo',
                'entregado':      '<i class="fa-solid fa-bag-shopping"></i> Entregado'
            };
            const idsJson = JSON.stringify(grupo.ids);

            itemsHTML += `
                <div class="ck-item">
                    <div class="ck-qty">${grupo.cantidad}</div>
                    <div class="ck-item-info">
                        <div class="ck-nombre">${grupo.nombre}</div>
                        ${grupo.opciones ? `<div class="ck-opciones">· ${grupo.opciones}</div>` : ''}
                        ${grupo.notas   ? `<div class="ck-opciones"><i class="fa-solid fa-note-sticky"></i> ${grupo.notas}</div>` : ''}
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
                    <div class="ck-tipo">
                        <i class="fa-solid ${p.tipo === 'aqui' ? 'fa-house' : 'fa-bag-shopping'}"></i>
                        ${p.tipo === 'aqui' ? 'Comer aquí' : 'Para llevar'} · #${p.id}
                    </div>
                </div>
                <div class="ck-tiempo">${minutos} min${urgente ? ' <i class="fa-solid fa-triangle-exclamation"></i>' : ''}</div>
            </div>
            <div class="ck-items">${itemsHTML}</div>`;

        grid.appendChild(card);
    });

    cont.innerHTML = '';
    cont.appendChild(grid);
}

function agruparItems(items) {
    const grupos = new Map();
    items.forEach(item => {
        const key = [item.nombre, item.estado || 'pendiente', item.opciones || '', item.notas || ''].join('||');
        if (grupos.has(key)) {
            const g = grupos.get(key);
            g.cantidad += (item.cantidad || 1);
            g.ids.push(item.id);
        } else {
            grupos.set(key, { ...item, cantidad: item.cantidad || 1, ids: [item.id] });
        }
    });
    return Array.from(grupos.values());
}

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
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarPolling(
        `${BASE_URL}/api/get_pedidos_activos.php?restaurante_id=${RESTAURANTE_ID}`,
        renderPedidos,
        10000
    );
});
</script>

<?php require_once '../../includes/footer.php'; ?>
