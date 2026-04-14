<?php
/**
 * roles/atencion/dashboard.php — Mapa de mesas (panel atención)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// Mesas con su pedido activo si existe
$stMesas = $db->prepare("
    SELECT m.*,
           p.id AS pedido_id,
           p.tipo,
           p.total,
           p.created_at AS pedido_inicio,
           p.usuario_id AS pedido_usuario,
           COUNT(pi.id)  AS num_items
    FROM mesas m
    LEFT JOIN pedidos p ON p.mesa_id = m.id AND p.estado = 'activo'
    LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
    WHERE m.restaurante_id = ? AND m.activo = 1
    GROUP BY m.id, p.id
    ORDER BY m.numero ASC
");
$stMesas->execute([$restauranteId]);
$mesas = $stMesas->fetchAll();

$pageTitle  = 'Mapa de Mesas';
$activeMenu = 'mapa';
require_once '../../includes/header.php';
?>

<div style="padding:16px;" class="page-content">
    <div class="d-flex align-center justify-between mb-16">
        <div>
            <h1>🗺️ Mapa de Mesas</h1>
            <p id="hora-actual"></p>
        </div>
        <div class="d-flex gap-8">
            <!-- Leyenda -->
            <div style="display:flex;gap:12px;align-items:center;font-size:.78rem;font-weight:600;">
                <span>🟢 Libre</span>
                <span>🔴 Ocupada</span>
                <span>🔵 Reservada</span>
            </div>
        </div>
    </div>

    <!-- Resumen rápido -->
    <div class="stats-grid mb-16">
        <div class="stat-card verde">
            <div class="stat-icon">✅</div>
            <div class="stat-valor" id="cnt-libres"><?= count(array_filter($mesas, fn($m) => $m['estado'] === 'libre')) ?></div>
            <div class="stat-label">Mesas libres</div>
        </div>
        <div class="stat-card rojo">
            <div class="stat-icon">⏳</div>
            <div class="stat-valor" id="cnt-ocupadas"><?= count(array_filter($mesas, fn($m) => $m['estado'] === 'ocupada')) ?></div>
            <div class="stat-label">Mesas ocupadas</div>
        </div>
        <div class="stat-card naranja">
            <div class="stat-icon">🧾</div>
            <div class="stat-valor" id="cnt-activos"><?= count(array_filter($mesas, fn($m) => $m['pedido_id'])) ?></div>
            <div class="stat-label">Pedidos activos</div>
        </div>
    </div>

    <!-- Grid de mesas -->
    <div class="mesas-grid" id="mesas-grid">
        <?php foreach ($mesas as $m): ?>
        <?php $minutos = $m['pedido_inicio'] ? round((time() - strtotime($m['pedido_inicio'])) / 60) : 0; ?>
        <div class="mesa-card <?= $m['estado'] ?>"
            data-id="<?= $m['id'] ?>"
            data-estado="<?= $m['estado'] ?>"
            data-pedido="<?= $m['pedido_id'] ?? '' ?>"
            onclick="clickMesa(<?= htmlspecialchars(json_encode($m)) ?>)">

            <?php if ($m['pedido_inicio']): ?>
            <div class="mesa-tiempo"><?= $minutos ?>m</div>
            <?php endif; ?>

            <div class="mesa-numero"><?= $m['numero'] ?></div>
            <div class="mesa-capacidad">👥 <?= $m['capacidad'] ?></div>

            <?php if ($m['pedido_id']): ?>
                <div class="mesa-estado">S/ <?= number_format($m['total'],2) ?></div>
                <div style="font-size:.68rem;color:var(--texto-light);"><?= $m['num_items'] ?> item(s)</div>
            <?php else: ?>
                <div class="mesa-estado"><?= strtoupper($m['estado']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: ¿Qué hacer con esta mesa? -->
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

<!-- Modal: Elegir tipo (comer aquí / llevar) -->
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

<script>
let mesaActual = null;

// Reloj
function actualizarReloj() {
    const now = new Date();
    document.getElementById('hora-actual').textContent =
        now.toLocaleDateString('es-PE', {weekday:'long', day:'numeric', month:'long'}) + ' · ' +
        now.toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'});
}
actualizarReloj();
setInterval(actualizarReloj, 30000);

function clickMesa(mesa) {
    mesaActual = mesa;
    const titulo = document.getElementById('modal-mesa-titulo');
    const sub    = document.getElementById('modal-mesa-sub');
    const body   = document.getElementById('modal-mesa-body');
    titulo.textContent = `Mesa ${mesa.numero}`;

    if (mesa.estado === 'libre') {
        sub.textContent  = `👥 ${mesa.capacidad} personas · Libre`;
        body.innerHTML   = `
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
        body.innerHTML  = `
            <div style="display:flex;flex-direction:column;gap:10px;padding:8px 0;">
                <a href="/sistema_restaurante/roles/atencion/comanda.php?pedido_id=${mesa.pedido_id}&mesa_id=${mesa.id}"
                   class="btn btn-naranja btn-full btn-lg">➕ Añadir platos al pedido</a>
                <a href="/sistema_restaurante/roles/atencion/cobrar.php?pedido_id=${mesa.pedido_id}"
                   class="btn btn-exito btn-full btn-lg">💰 Cobrar pedido (S/ ${parseFloat(mesa.total).toFixed(2)})</a>
            </div>`;
    } else {
        sub.textContent = 'Mesa reservada';
        body.innerHTML  = '<p style="text-align:center;padding:20px 0;">Esta mesa está reservada.</p>';
    }

    Modal.abrir('modal-mesa');
}

function iniciarPedido() {
    Modal.cerrar('modal-mesa');
    setTimeout(() => Modal.abrir('modal-tipo'), 200);

    document.getElementById('btn-aqui').onclick = () => crearPedidoYIr('aqui');
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

document.addEventListener('DOMContentLoaded', () => {
    iniciarPolling('/sistema_restaurante/api/get_mesas.php', function(mesas) {
        if (!mesas) return;
        mesas.forEach(m => {
            const card = document.querySelector(`.mesa-card[data-id="${m.id}"]`);
            if (!card) return;
            card.className = `mesa-card ${m.estado}`;
            card.dataset.estado = m.estado;
            card.dataset.pedido = m.pedido_id || '';
        });
    });
});

</script>

<?php require_once '../../includes/footer.php'; ?>
