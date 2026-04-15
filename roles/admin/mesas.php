<?php
/**
 * roles/admin/mesas.php — Gestión de mesas + mapa interactivo
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// ── Mesas con pedido activo (igual que el panel de atención) ────────────────
$stMesas = $db->prepare("
    SELECT m.*,
           p.id  AS pedido_id,
           p.tipo,
           p.total,
           p.created_at AS pedido_inicio,
           COUNT(pi.id) AS num_items
    FROM mesas m
    LEFT JOIN pedidos p  ON p.mesa_id = m.id AND p.estado = 'activo'
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    WHERE m.restaurante_id = ?
    GROUP BY m.id, p.id
    ORDER BY m.numero ASC
");
$stMesas->execute([$restauranteId]);
$mesas = $stMesas->fetchAll();

$pageTitle  = 'Gestión de Mesas';
$activeMenu = 'mesas';
require_once '../../includes/header.php';
?>

<style>
/* ── Sidebar overlay (móvil) ─────────────────────────────────────────────── */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 199;
}
.sidebar-overlay.activo { display: block; }

/* ── Botón hamburguesa ───────────────────────────────────────────────────── */
.btn-hamburguesa {
    display: none;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    color: #fff;
    padding: 0 10px;
    height: 48px;
    border-radius: 8px;
    transition: background .2s;
}
.btn-hamburguesa:hover { background: rgba(255,255,255,.15); }

/* ── Sidebar en móvil ────────────────────────────────────────────────────── */
@media (max-width: 860px) {
    .btn-hamburguesa { display: flex; }

    .layout-admin .sidebar {
        position: fixed;
        top: 0; left: 0;
        height: 100%;
        z-index: 200;
        transform: translateX(-110%);
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        box-shadow: 4px 0 24px rgba(0,0,0,.2);
    }
    .layout-admin .sidebar.abierto {
        transform: translateX(0);
    }
    .layout-admin .main-content {
        margin-left: 0 !important;
    }
}

/* ── Mapa de mesas ───────────────────────────────────────────────────────── */
.mesas-map-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 14px;
    padding: 4px;
}
.mesa-map-card {
    position: relative;
    border-radius: 14px;
    padding: 18px 12px 14px;
    text-align: center;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    border: 2px solid transparent;
    user-select: none;
}
.mesa-map-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.13); }
.mesa-map-card:active { transform: scale(.97); }
.mesa-map-card.libre    { background: #e8f8e8; border-color: #6abf69; }
.mesa-map-card.ocupada  { background: #fde8e8; border-color: #e57373; }
.mesa-map-card.reservada{ background: #e3eeff; border-color: #64b5f6; }
.mesa-map-card .map-num { font-size: 2rem; font-weight: 800; line-height: 1; color: var(--texto); }
.mesa-map-card .map-cap { font-size: .72rem; color: var(--texto-light); margin: 4px 0 2px; }
.mesa-map-card .map-est { font-size: .65rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.mesa-map-card.libre  .map-est  { color: #388e3c; }
.mesa-map-card.ocupada .map-est { color: #c62828; }
.mesa-map-card .map-badge {
    position: absolute; top: 6px; right: 8px;
    background: var(--rojo); color: #fff;
    border-radius: 99px; font-size: .62rem; font-weight: 700;
    padding: 2px 7px;
}
.mesa-map-card .map-tiempo {
    position: absolute; top: 6px; left: 8px;
    background: rgba(0,0,0,.12); color: var(--texto);
    border-radius: 99px; font-size: .62rem; font-weight: 700;
    padding: 2px 7px;
}

/* ── Botón eliminar ítem (solo admin) ────────────────────────────────────── */
.btn-del-item {
    background: none;
    border: none;
    cursor: pointer;
    color: #e53935;
    font-size: 1.1rem;
    padding: 2px 4px;
    border-radius: 6px;
    transition: background .15s;
    flex-shrink: 0;
}
.btn-del-item:hover { background: #fde8e8; }
</style>

<!-- Botón hamburguesa — se inyecta en la navbar vía JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inyectar botón hamburguesa al inicio de la navbar-right (o antes)
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    const ham = document.createElement('button');
    ham.className = 'btn-hamburguesa';
    ham.id = 'btn-hamburguesa';
    ham.title = 'Menú';
    ham.innerHTML = '☰';
    ham.setAttribute('aria-label', 'Abrir menú lateral');
    navbar.insertBefore(ham, navbar.firstChild);

    const sidebar  = document.querySelector('.layout-admin .sidebar');
    const overlay  = document.getElementById('sidebar-overlay');

    ham.addEventListener('click', function() { toggleSidebar(); });
    overlay.addEventListener('click', function() { toggleSidebar(false); });

    function toggleSidebar(force) {
        const open = (force === undefined) ? !sidebar.classList.contains('abierto') : force;
        sidebar.classList.toggle('abierto', open);
        overlay.classList.toggle('activo', open);
    }
});
</script>

<!-- Overlay para cerrar sidebar en móvil -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
            <li><a href="mesas.php" class="active"><span class="menu-icon">🪑</span> Mesas</a></li>
            <li><a href="menu_categorias.php"><span class="menu-icon">📂</span> Categorías</a></li>
            <li><a href="menu_productos.php"><span class="menu-icon">🍽️</span> Productos</a></li>
            <li><a href="usuarios.php"><span class="menu-icon">👥</span> Usuarios</a></li>
            <li><a href="reportes.php"><span class="menu-icon">📈</span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon">🗂️</span> Historial</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1>🪑 Gestión de Mesas</h1>
                    <p><?= count($mesas) ?> mesas configuradas</p>
                </div>
                <button class="btn btn-primario" onclick="Modal.abrir('modal-nueva-mesa')" id="btn-nueva-mesa">
                    ➕ Nueva Mesa
                </button>
            </div>

            <!-- ── Mapa interactivo (igual que panel atención) ─────────── -->
            <div class="card mb-16">
                <div class="card-title">🗺️ Mapa interactivo del restaurante</div>
                <div class="mesas-map-grid" id="mesas-map-grid">
                    <?php foreach ($mesas as $m): ?>
                    <?php $min = $m['pedido_inicio'] ? round((time() - strtotime($m['pedido_inicio'])) / 60) : 0; ?>
                    <div class="mesa-map-card <?= $m['activo'] ? $m['estado'] : 'inactiva' ?>"
                         data-id="<?= $m['id'] ?>"
                         onclick="<?= $m['activo'] ? "clickMesa(".htmlspecialchars(json_encode($m)).")" : "Toast.advertencia('Mesa inactiva')" ?>">

                        <?php if ($m['pedido_inicio']): ?>
                        <div class="map-tiempo"><?= $min ?>m</div>
                        <?php endif; ?>

                        <?php if ($m['pedido_id']): ?>
                        <div class="map-badge"><?= $m['num_items'] ?> 🍽️</div>
                        <?php endif; ?>

                        <div class="map-num"><?= $m['numero'] ?></div>
                        <div class="map-cap">👥 <?= $m['capacidad'] ?> personas</div>

                        <?php if ($m['pedido_id']): ?>
                            <div class="map-est" style="color:var(--rojo);">S/ <?= number_format($m['total'], 2) ?></div>
                        <?php else: ?>
                            <div class="map-est"><?= $m['activo'] ? strtoupper($m['estado']) : 'INACTIVA' ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$mesas): ?>
                    <div class="empty-state" style="grid-column:1/-1;">
                        <div class="icon">🪑</div>
                        <h3>Sin mesas configuradas</h3>
                        <p>Agrega tu primera mesa para comenzar</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Tabla de gestión ───────────────────────────────────── -->
            <div class="card">
                <div class="card-title">Lista detallada</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>N° Mesa</th>
                                <th>Capacidad</th>
                                <th>Estado</th>
                                <th>Activa</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-mesas">
                            <?php foreach ($mesas as $m): ?>
                            <tr data-id="<?= $m['id'] ?>">
                                <td><strong>Mesa <?= $m['numero'] ?></strong></td>
                                <td>👥 <?= $m['capacidad'] ?> personas</td>
                                <td>
                                    <span class="badge <?= ['libre'=>'badge-verde','ocupada'=>'badge-rojo','reservada'=>'badge-azul'][$m['estado']] ?? 'badge-verde' ?>">
                                        <?= ucfirst($m['estado']) ?>
                                    </span>
                                </td>
                                <td><?= $m['activo'] ? '✅' : '❌' ?></td>
                                <td>
                                    <button class="btn btn-ghost btn-sm"
                                        onclick="editarMesa(<?= htmlspecialchars(json_encode($m)) ?>)">
                                        ✏️ Editar
                                    </button>
                                    <button class="btn btn-peligro btn-sm"
                                        onclick="eliminarMesa(<?= $m['id'] ?>, <?= $m['numero'] ?>)">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ════════════════ MODAL: ¿Qué hacer con esta mesa? ════════════════════ -->
<div class="modal-overlay" id="modal-mesa">
    <div class="modal">
        <div class="modal-header">
            <div>
                <div class="modal-title" id="modal-mesa-titulo">Mesa</div>
                <div style="font-size:.78rem;color:var(--texto-light);margin-top:2px;" id="modal-mesa-sub"></div>
            </div>
            <button class="modal-close" onclick="Modal.cerrar('modal-mesa')">✕</button>
        </div>
        <div class="modal-body" id="modal-mesa-body"></div>
    </div>
</div>

<!-- ════════════════ MODAL: Elegir tipo de pedido ════════════════════════ -->
<div class="modal-overlay" id="modal-tipo">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">¿Cómo consume?</div>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:8px 0;">
                <button id="btn-aqui" class="btn btn-exito btn-lg"
                    style="flex-direction:column;gap:8px;height:100px;font-size:1rem;">
                    🏠<span>Comer aquí</span>
                </button>
                <button id="btn-llevar" class="btn btn-naranja btn-lg"
                    style="flex-direction:column;gap:8px;height:100px;font-size:1rem;">
                    🛍️<span>Para llevar</span>
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-tipo')">Cancelar</button>
        </div>
    </div>
</div>

<!-- ════════════════ MODAL: Detalle del pedido ═══════════════════════════ -->
<div class="modal-overlay" id="modal-detalle">
    <div class="modal" style="max-height:85vh;display:flex;flex-direction:column;">
        <div class="modal-header">
            <div class="modal-title">📋 Detalle del Pedido</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-detalle')">✕</button>
        </div>
        <div class="modal-body" id="modal-detalle-body" style="overflow-y:auto;">
            <div class="text-center"><div class="spinner"></div></div>
        </div>
    </div>
</div>

<!-- ════════════════ MODAL: Nueva / Editar Mesa ══════════════════════════ -->
<div class="modal-overlay" id="modal-nueva-mesa">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-mesa-titulo-form">➕ Nueva Mesa</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-nueva-mesa')">✕</button>
        </div>
        <div class="modal-body">
            <form id="form-mesa">
                <input type="hidden" id="mesa-id" name="id" value="">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label" for="mesa-numero">N° de Mesa</label>
                        <input type="number" id="mesa-numero" name="numero" class="form-control" min="1" max="999" required placeholder="Ej: 5">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="mesa-capacidad">Capacidad (personas)</label>
                        <input type="number" id="mesa-capacidad" name="capacidad" class="form-control" min="1" max="50" value="4" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="mesa-activo">Estado</label>
                    <select id="mesa-activo" name="activo" class="form-control">
                        <option value="1">✅ Activa</option>
                        <option value="0">❌ Inactiva</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-nueva-mesa')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarMesa()" id="btn-guardar-mesa">💾 Guardar</button>
        </div>
    </div>
</div>

<script>
/* ════════════════════════════════════════════════════════════════════════════
   MAPA INTERACTIVO — lógica compartida con panel de atención
   ════════════════════════════════════════════════════════════════════════════ */
let mesaActual = null;

/**
 * Se ejecuta al hacer clic sobre una tarjeta del mapa.
 * Muestra opciones según el estado de la mesa.
 */
function clickMesa(mesa) {
    mesaActual = mesa;
    const titulo = document.getElementById('modal-mesa-titulo');
    const sub    = document.getElementById('modal-mesa-sub');
    const body   = document.getElementById('modal-mesa-body');

    titulo.textContent = `Mesa ${mesa.numero}`;

    if (mesa.estado === 'libre') {
        sub.textContent = `👥 ${mesa.capacidad} personas · Libre`;
        body.innerHTML  = `
            <div style="text-align:center;padding:10px 0;">
                <div class="empty-state" style="padding:16px 0;">
                    <div class="icon">🍽️</div>
                    <p>Esta mesa está libre.</p>
                </div>
                <button class="btn btn-primario btn-full btn-lg" onclick="iniciarPedido()">
                    ➕ Iniciar nueva comanda
                </button>
            </div>`;
    } else if (mesa.pedido_id) {
        const minutos = mesa.pedido_inicio
            ? Math.round((Date.now() - new Date(mesa.pedido_inicio).getTime()) / 60000) : 0;
        sub.textContent = `⏳ ${minutos} min · ${mesa.num_items} plato(s) · S/ ${parseFloat(mesa.total).toFixed(2)}`;

        body.innerHTML = `
            <div style="display:flex;flex-direction:column;gap:10px;padding:8px 0;">
                <button onclick="verDetalle(${mesa.pedido_id}, ${mesa.id})"
                   class="btn btn-ghost btn-full btn-lg" style="border:2px solid var(--borde);font-weight:700;">
                   👁️ Ver pedido actual
                </button>
                <a href="/sistema_restaurante/roles/atencion/comanda.php?pedido_id=${mesa.pedido_id}&mesa_id=${mesa.id}"
                   class="btn btn-naranja btn-full btn-lg">➕ Añadir platos al pedido</a>
                <a href="/sistema_restaurante/roles/atencion/cobrar.php?pedido_id=${mesa.pedido_id}"
                   class="btn btn-exito btn-full btn-lg">💰 Cobrar pedido (S/ ${parseFloat(mesa.total).toFixed(2)})</a>
                <button onclick="cancelarPedido(${mesa.pedido_id})"
                   class="btn btn-ghost btn-full mt-8" style="color:var(--rojo);font-weight:600;">
                   ❌ Desocupar Mesa (Cancela la comanda)
                </button>
            </div>`;
    } else {
        sub.textContent = 'Mesa reservada';
        body.innerHTML  = '<p style="text-align:center;padding:20px 0;">Esta mesa está reservada.</p>';
    }

    Modal.abrir('modal-mesa');
}

/* ── Iniciar pedido ─────────────────────────────────────────────────────── */
function iniciarPedido() {
    Modal.cerrar('modal-mesa');
    setTimeout(() => Modal.abrir('modal-tipo'), 200);
    document.getElementById('btn-aqui').onclick   = () => crearPedidoYIr('aqui');
    document.getElementById('btn-llevar').onclick = () => crearPedidoYIr('llevar');
}

async function crearPedidoYIr(tipo) {
    Modal.cerrar('modal-tipo');
    Loading.show();
    try {
        const res  = await fetch('/sistema_restaurante/api/crear_pedido.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({ mesa_id: mesaActual.id, tipo })
        });
        const json = await res.json();
        if (json.success) {
            window.location.href = `/sistema_restaurante/roles/atencion/comanda.php?pedido_id=${json.data.pedido_id}&mesa_id=${mesaActual.id}`;
        } else {
            Toast.error(json.message);
            Loading.hide();
        }
    } catch(e) { Toast.error('Error de conexión'); Loading.hide(); }
}

/* ── Cancelar pedido ────────────────────────────────────────────────────── */
function cancelarPedido(id) {
    Modal.cerrar('modal-mesa');
    setTimeout(() => {
        confirmar('¿Seguro que quieres descartar este pedido? La mesa quedará libre nuevamente.', async () => {
            Loading.show();
            try {
                const res  = await fetch('/sistema_restaurante/api/cancelar_pedido.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body: JSON.stringify({ id })
                });
                const json = await res.json();
                if (json.success) {
                    Toast.exito(json.message);
                    setTimeout(() => location.reload(), 700);
                } else {
                    Toast.error(json.message);
                    Loading.hide();
                }
            } catch(e) { Toast.error('Error de conexión'); Loading.hide(); }
        });
    }, 200);
}

/* ── Ver detalle del pedido (con botones eliminar para admin) ───────────── */
async function verDetalle(pedidoId, mesaId) {
    Modal.cerrar('modal-mesa');
    setTimeout(() => Modal.abrir('modal-detalle'), 200);
    document.getElementById('modal-detalle-body').innerHTML = '<div class="text-center"><div class="spinner"></div></div>';

    try {
        const res  = await fetch(`/sistema_restaurante/api/get_pedido_detalle.php?id=${pedidoId}`);
        const json = await res.json();
        if (!json.success) {
            document.getElementById('modal-detalle-body').innerHTML = '<p>Error al cargar</p>';
            return;
        }
        const p = json.data;
        renderDetalle(p, mesaId);
    } catch(e) {
        document.getElementById('modal-detalle-body').innerHTML = '<p>Error de conexión</p>';
    }
}

function renderDetalle(p, mesaId) {
    let html = `<div style="margin-bottom:14px;">
        <span class="badge badge-azul">#${p.id}</span>
        ${p.tipo === 'aqui' ? '🏠 Aquí' : '🛍️ Para llevar'}
        ${p.mesa_numero ? '· Mesa ' + p.mesa_numero : ''}
    </div>`;

    if (!p.items || p.items.length === 0) {
        html += `<div class="empty-state" style="padding:20px 0;">
            <div class="icon">🍽️</div><p>Sin platos en este pedido</p>
        </div>`;
    } else {
        p.items.forEach(item => {
            html += `
            <div class="pedido-item" id="det-item-${item.id}"
                 style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:8px 0;align-items:center;">
                <div style="flex:1;">
                    <div class="pedido-item-nombre" style="font-weight:600;font-size:0.9rem;">
                        ${item.cantidad}x ${item.nombre}
                    </div>
                    ${item.opciones ? `<div class="pedido-item-opciones" style="font-size:0.8rem;color:#666;">· ${item.opciones}</div>` : ''}
                    ${item.notas   ? `<div class="pedido-item-opciones" style="font-size:0.8rem;color:#666;">📝 ${item.notas}</div>`   : ''}
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="pedido-item-precio" style="font-weight:700;">S/ ${parseFloat(item.subtotal).toFixed(2)}</div>
                    <button class="btn-del-item" title="Eliminar este plato"
                            onclick="eliminarItemDetalle(${item.id}, ${p.id}, ${mesaId})">🗑️</button>
                </div>
            </div>`;
        });
    }

    html += `<div class="pedido-total mt-12" id="det-total-row"
        style="display:flex;justify-content:space-between;padding-top:10px;font-weight:bold;font-size:1.1rem;">
        <span>Total</span>
        <span class="text-rojo" id="det-total-val">S/ ${parseFloat(p.total).toFixed(2)}</span>
    </div>`;

    // Botones de acción rápida desde el detalle
    html += `<div style="display:flex;flex-direction:column;gap:8px;margin-top:16px;">
        <a href="/sistema_restaurante/roles/atencion/comanda.php?pedido_id=${p.id}&mesa_id=${mesaId}"
           class="btn btn-naranja btn-full">➕ Añadir más platos</a>
        <a href="/sistema_restaurante/roles/atencion/cobrar.php?pedido_id=${p.id}"
           class="btn btn-exito btn-full">💰 Cobrar pedido</a>
    </div>`;

    document.getElementById('modal-detalle-body').innerHTML = html;
}

async function eliminarItemDetalle(itemId, pedidoId, mesaId) {
    // Cerrar el modal de detalle primero para que no tape el diálogo de confirmación
    Modal.cerrar('modal-detalle');

    setTimeout(() => {
        confirmar('¿Eliminar este plato del pedido?', async () => {
            try {
                const res  = await fetch('/sistema_restaurante/api/eliminar_item.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body: JSON.stringify({ item_id: itemId })
                });
                const json = await res.json();
                if (json.success) {
                    Toast.exito('Plato eliminado');
                    // Recargar el detalle actualizado y reabrir el modal
                    setTimeout(() => verDetalle(pedidoId, mesaId), 400);
                    // Actualizar tarjeta del mapa
                    refreshMesaCard(mesaId);
                } else {
                    Toast.error(json.message);
                    // Reabrir el detalle aunque haya error
                    setTimeout(() => Modal.abrir('modal-detalle'), 300);
                }
            } catch(e) {
                Toast.error('Error de conexión');
                setTimeout(() => Modal.abrir('modal-detalle'), 300);
            }
        }, () => {
            // Callback de cancelación: reabrir el modal de detalle
            setTimeout(() => Modal.abrir('modal-detalle'), 200);
        });
    }, 200);
}

// Refresca silenciosamente los datos de una mesa en el mapa
// y actualiza el onclick con datos frescos para que el modal muestre el precio correcto.
async function refreshMesaCard(mesaId) {
    try {
        const res  = await fetch('/sistema_restaurante/api/get_mesas.php');
        const json = await res.json();
        if (!json || !json.data) return;
        const m = json.data.find(x => x.id == mesaId);
        if (!m) return;
        const card = document.querySelector(`.mesa-map-card[data-id="${mesaId}"]`);
        if (!card) return;

        // ── Actualizar texto de estado/precio ─────────────────────────
        const estEl = card.querySelector('.map-est');
        if (estEl) estEl.textContent = m.pedido_id
            ? `S/ ${parseFloat(m.total).toFixed(2)}` : m.estado.toUpperCase();

        // ── Actualizar badge de items ──────────────────────────────────
        let badge = card.querySelector('.map-badge');
        if (m.pedido_id && m.num_items > 0) {
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'map-badge';
                card.appendChild(badge);
            }
            badge.textContent = `${m.num_items} 🍽️`;
        } else if (badge) {
            badge.remove();
        }

        // ── Actualizar onclick con datos frescos ───────────────────────
        // Esto es clave: evita que el modal muestre precio/items desactualizados
        card.onclick = m.activo
            ? () => clickMesa(m)
            : () => Toast.advertencia('Mesa inactiva');

    } catch(e) { /* silencioso */ }
}

/* ════════════════════════════════════════════════════════════════════════════
   GESTIÓN DE MESAS (crear / editar / eliminar)
   ════════════════════════════════════════════════════════════════════════════ */
let modoEdicion = false;

function editarMesa(mesa) {
    modoEdicion = true;
    document.getElementById('modal-mesa-titulo-form').textContent = '✏️ Editar Mesa ' + mesa.numero;
    document.getElementById('mesa-id').value        = mesa.id;
    document.getElementById('mesa-numero').value    = mesa.numero;
    document.getElementById('mesa-capacidad').value = mesa.capacidad;
    document.getElementById('mesa-activo').value    = mesa.activo;
    Modal.abrir('modal-nueva-mesa');
}

document.getElementById('btn-nueva-mesa').addEventListener('click', function() {
    modoEdicion = false;
    document.getElementById('modal-mesa-titulo-form').textContent = '➕ Nueva Mesa';
    document.getElementById('form-mesa').reset();
    document.getElementById('mesa-id').value        = '';
    document.getElementById('mesa-capacidad').value = '4';
});

async function guardarMesa() {
    const btn = document.getElementById('btn-guardar-mesa');
    btn.disabled = true; btn.textContent = '⏳ Guardando...';

    const datos = {
        accion:    modoEdicion ? 'editar' : 'crear',
        id:        document.getElementById('mesa-id').value,
        numero:    document.getElementById('mesa-numero').value,
        capacidad: document.getElementById('mesa-capacidad').value,
        activo:    document.getElementById('mesa-activo').value,
    };

    try {
        const res  = await fetch('/sistema_restaurante/api/admin_mesas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito(json.message);
            Modal.cerrar('modal-nueva-mesa');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.error(json.message);
        }
    } catch(e) {
        Toast.error('Error de conexión');
    } finally {
        btn.disabled = false; btn.textContent = '💾 Guardar';
    }
}

function eliminarMesa(id, numero) {
    confirmar(`¿Eliminar la Mesa ${numero}? Esta acción no se puede deshacer.`, async () => {
        try {
            const res  = await fetch('/sistema_restaurante/api/admin_mesas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({ accion: 'eliminar', id })
            });
            const json = await res.json();
            if (json.success) {
                Toast.exito(json.message);
                setTimeout(() => location.reload(), 700);
            } else {
                Toast.error(json.message);
            }
        } catch(e) { Toast.error('Error de conexión'); }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
