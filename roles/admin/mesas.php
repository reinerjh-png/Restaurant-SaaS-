<?php
/**
 * roles/admin/mesas.php — Gestión de mesas
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$stMesas = $db->prepare("SELECT * FROM mesas WHERE restaurante_id = ? ORDER BY numero ASC");
$stMesas->execute([$restauranteId]);
$mesas = $stMesas->fetchAll();

$pageTitle  = 'Gestión de Mesas';
$activeMenu = 'mesas';
require_once '../../includes/header.php';
?>

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

            <!-- Grid de mesas -->
            <div class="card mb-16">
                <div class="card-title">Vista del restaurante</div>
                <div id="mesas-grid" class="mesas-grid">
                    <?php foreach ($mesas as $m): ?>
                    <div class="mesa-card <?= $m['estado'] ?>" data-id="<?= $m['id'] ?>">
                        <div class="mesa-numero"><?= $m['numero'] ?></div>
                        <div class="mesa-capacidad">👥 <?= $m['capacidad'] ?> personas</div>
                        <div class="mesa-estado"><?= strtoupper($m['estado']) ?></div>
                        <?php if (!$m['activo']): ?>
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);border-radius:var(--radio);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.75rem;">INACTIVA</div>
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

            <!-- Tabla de mesas -->
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
                                    <span class="badge <?= ['libre'=>'badge-verde','ocupada'=>'badge-rojo','reservada'=>'badge-azul'][$m['estado']] ?>">
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

<!-- Modal: Nueva Mesa -->
<div class="modal-overlay" id="modal-nueva-mesa">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-mesa-titulo">➕ Nueva Mesa</div>
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
let modoEdicion = false;

function editarMesa(mesa) {
    modoEdicion = true;
    document.getElementById('modal-mesa-titulo').textContent = '✏️ Editar Mesa ' + mesa.numero;
    document.getElementById('mesa-id').value       = mesa.id;
    document.getElementById('mesa-numero').value   = mesa.numero;
    document.getElementById('mesa-capacidad').value= mesa.capacidad;
    document.getElementById('mesa-activo').value   = mesa.activo;
    Modal.abrir('modal-nueva-mesa');
}

document.getElementById('btn-nueva-mesa').addEventListener('click', function() {
    modoEdicion = false;
    document.getElementById('modal-mesa-titulo').textContent = '➕ Nueva Mesa';
    document.getElementById('form-mesa').reset();
    document.getElementById('mesa-id').value = '';
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
