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
/* Forzar header oscuro en cocina */
.navbar { background: #0F0F1A; border-bottom: none; }

/* Ajustar colores de texto para el estado vacío ahora que el fondo es blanco */
.cocina-vacia { color: var(--text-secondary); }
.cocina-vacia .icon { color: var(--border); }
</style>

<!-- Audio de alerta -->
<audio id="timbre-audio" preload="auto">
    <source src="<?= BASE_URL ?>/assets/timbre.mp3" type="audio/mpeg">
</audio>

<!-- Cocina Header personalizada -->
<div class="cocina-header">
    <div>
        <div style="display:flex; align-items:center; gap: 12px;">
            <h1><i class="fa-solid fa-kitchen-set"></i> Panel de Cocina</h1>
            <button id="btn-activar-audio" class="btn btn-sm" style="background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.3); color:white; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; cursor:pointer; transition:all 0.2s;" onclick="activarAudio()">
                <i class="fa-solid fa-volume-xmark"></i> Activar Sonido
            </button>
        </div>
        <div style="font-size:.75rem;opacity:.7;margin-top:4px;" id="cocina-reloj"></div>
    </div>
    <div class="cocina-status">
        <div class="dot"></div>
        <span>En vivo · actualiza cada 10s</span>
        <span id="cocina-count" style="background:rgba(255,255,255,.15);border-radius:999px;padding:4px 12px;font-weight:700;"></span>
    </div>
</div>

<div id="cocina-contenido" style="padding: 4px 16px 16px; height: calc(100vh - 110px); display: flex; flex-direction: column; overflow: hidden;">
    <div class="cocina-vacia">
        <div class="icon"><i class="fa-solid fa-hourglass-half"></i></div>
        <p>Cargando pedidos...</p>
    </div>
</div>

<script>
const RESTAURANTE_ID = <?= $restauranteId ?>;

let estadoPrevio = null;
let audioActivado = false;

function activarAudio() {
    const audio = document.getElementById('timbre-audio');
    if (!audio) return;
    audio.play().then(() => {
        audio.pause();
        audio.currentTime = 0;
        audioActivado = true;
        const btn = document.getElementById('btn-activar-audio');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-volume-high"></i> Sonido Activado';
            btn.style.borderColor = 'var(--success)';
            btn.style.color = 'var(--success)';
            btn.style.background = 'rgba(40, 167, 69, 0.1)';
        }
        if (typeof Toast !== 'undefined') Toast.exito('Alertas sonoras activadas');
    }).catch(e => console.error('Error al activar audio:', e));
}

function reproducirTimbre() {
    if (!audioActivado) return;
    const audio = document.getElementById('timbre-audio');
    if (!audio) return;
    audio.currentTime = 0;
    audio.play().catch(() => {
        audioActivado = false;
        const btn = document.getElementById('btn-activar-audio');
        if(btn) {
            btn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i> Activar Sonido';
            btn.style.borderColor = 'var(--danger)';
            btn.style.color = 'var(--danger)';
        }
    });
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
        const minutos = parseInt(p.minutos_transcurridos) || 0;
        const urgente = minutos >= 20;

        const card = document.createElement('div');
        card.className = `cocina-card${urgente ? ' urgente' : ''}`;
        card.id = `ck-pedido-${p.id}`;

        const grupos = agruparItems(p.items || []);
        let itemsHTML = '';

        grupos.forEach(grupo => {
            const estadoClass = grupo.estado || 'pendiente';
            // Solo mostramos pendiente o listo visualmente
            const iconHTML = (estadoClass === 'listo') 
                ? '<i class="fa-solid fa-circle-check" style="color:var(--success)"></i>' 
                : '<i class="fa-solid fa-hourglass-half"></i>';

            const idsJson = JSON.stringify(grupo.ids);

            itemsHTML += `
                <div class="ck-item">
                    <div class="ck-qty">${grupo.cantidad}</div>
                    <div class="ck-item-info">
                        <div class="ck-nombre">${grupo.nombre}</div>
                        ${grupo.opciones ? `<div class="ck-opciones">· ${grupo.opciones}</div>` : ''}
                        ${grupo.notas   ? `<div class="ck-opciones"><i class="fa-solid fa-note-sticky"></i> ${grupo.notas}</div>` : ''}
                    </div>
                    <button class="ck-btn-listo ck-btn ${estadoClass === 'listo' ? 'listo' : 'pendiente'}"
                        title="${estadoClass === 'listo' ? 'Listo (click para ocultar)' : 'Pendiente (click para marcar listo)'}"
                        onclick="cambiarEstadoItem(this, ${idsJson}, '${estadoClass}')">
                        ${iconHTML}
                    </button>
                </div>`;
        });

        const tipoColor = p.tipo === 'aqui' ? 'var(--info)' : 'var(--warning)';
        const tipoBg = p.tipo === 'aqui' ? 'rgba(56, 189, 248, 0.15)' : 'rgba(250, 204, 21, 0.15)';

        card.innerHTML = `
            <div class="cocina-card-header">
                <div>
                    <div class="ck-mesa">${p.mesa_numero ? 'Mesa ' + p.mesa_numero : 'Llevar'}</div>
                    <div class="ck-tipo" style="margin-top:2px;">
                        <span style="color: ${tipoColor}; background: ${tipoBg}; padding: 1px 3px; border-radius: 4px; font-weight: bold; font-size: 0.6rem; display: inline-flex; align-items: center; gap: 2px;">
                            <i class="fa-solid ${p.tipo === 'aqui' ? 'fa-house' : 'fa-bag-shopping'}"></i>
                            ${p.tipo === 'aqui' ? 'Comer aquí' : 'Para llevar'}
                        </span>
                        <span style="opacity: 0.7; margin-left: 4px; font-size: 0.6rem;">· #${p.id}</span>
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

async function cambiarEstadoItem(btn, ids, estadoActual) {
    // Transición simplificada: pendiente -> listo -> entregado (desaparece)
    const siguiente = (estadoActual === 'listo') ? 'entregado' : 'listo';
    const itemIds   = Array.isArray(ids) ? ids : [ids];
    
    // Feedback visual inmediato
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const res  = await fetch(BASE_URL + '/api/marcar_item_listo.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ item_ids: itemIds, estado: siguiente })
        });
        const json = await res.json();
        if (!json.success) {
            Toast.advertencia(json.message);
            btn.disabled = false;
        } else {
            // Refrescar inmediatamente el panel completo
            cargarPedidosAhora();
        }
    } catch(e) {
        btn.disabled = false;
    }
}

function cargarPedidosAhora() {
    fetch(`${BASE_URL}/api/get_pedidos_activos.php?restaurante_id=${RESTAURANTE_ID}`)
        .then(r => r.json())
        .then(data => { if (data.success) renderPedidos(data.data); })
        .catch(e => console.warn(e));
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
