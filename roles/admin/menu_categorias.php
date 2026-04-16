<?php
/**
 * roles/admin/menu_categorias.php — Gestión de categorías del menú
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$st = $db->prepare("SELECT * FROM categorias WHERE restaurante_id = ? ORDER BY orden ASC, id ASC");
$st->execute([$restauranteId]);
$categorias = $st->fetchAll();

$pageTitle  = 'Categorías del Menú';
$activeMenu = 'categorias';
require_once '../../includes/header.php';
?>

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
            <li><a href="mesas.php"><span class="menu-icon">🪑</span> Mesas</a></li>
            <li><a href="menu_categorias.php" class="active"><span class="menu-icon">📂</span> Categorías</a></li>
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
                    <h1>📂 Categorías del Menú</h1>
                    <p><?= count($categorias) ?> categorías registradas</p>
                </div>
                <button class="btn btn-primario" onclick="nuevaCategoria()" id="btn-nueva-cat">
                    ➕ Nueva Categoría
                </button>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Icono</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td><?= $c['orden'] ?></td>
                                <td style="font-size:1.5rem;"><?= htmlspecialchars($c['icono']) ?></td>
                                <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                                <td>
                                    <span class="badge <?= $c['activo'] ? 'badge-verde' : 'badge-gris' ?>">
                                        <?= $c['activo'] ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-ghost btn-sm"
                                        onclick='editarCategoria(<?= json_encode($c) ?>)'>
                                        ✏️ Editar
                                    </button>
                                    <button class="btn btn-peligro btn-sm"
                                        onclick="eliminarCategoria(<?= $c['id'] ?>, '<?= htmlspecialchars($c['nombre']) ?>')">
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

<!-- Modal Categoría -->
<div class="modal-overlay" id="modal-categoria">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-cat-titulo">➕ Nueva Categoría</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-categoria')">✕</button>
        </div>
        <div class="modal-body">
            <form id="form-cat">
                <input type="hidden" id="cat-id" name="id">
                <div class="form-group">
                    <label class="form-label">Nombre de la categoría</label>
                    <input type="text" id="cat-nombre" class="form-control" placeholder="Ej: Ceviches" required maxlength="80">
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Icono (emoji)</label>
                        <input type="text" id="cat-icono" class="form-control" placeholder="🐟" maxlength="10" value="🍽️">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Orden de aparición</label>
                        <input type="number" id="cat-orden" class="form-control" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="cat-activo" class="form-control">
                        <option value="1">✅ Activa</option>
                        <option value="0">❌ Inactiva</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-categoria')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarCategoria()" id="btn-guardar-cat">💾 Guardar</button>
        </div>
    </div>
</div>

<script>
let modoEdicionCat = false;

function nuevaCategoria() {
    modoEdicionCat = false;
    document.getElementById('modal-cat-titulo').textContent = '➕ Nueva Categoría';
    document.getElementById('form-cat').reset();
    document.getElementById('cat-icono').value = '🍽️';
    document.getElementById('cat-id').value = '';
    Modal.abrir('modal-categoria');
}

function editarCategoria(cat) {
    modoEdicionCat = true;
    document.getElementById('modal-cat-titulo').textContent = '✏️ Editar Categoría';
    document.getElementById('cat-id').value     = cat.id;
    document.getElementById('cat-nombre').value = cat.nombre;
    document.getElementById('cat-icono').value  = cat.icono;
    document.getElementById('cat-orden').value  = cat.orden;
    document.getElementById('cat-activo').value = cat.activo;
    Modal.abrir('modal-categoria');
}

async function guardarCategoria() {
    const btn = document.getElementById('btn-guardar-cat');
    btn.disabled = true;

    const datos = {
        accion:  modoEdicionCat ? 'editar' : 'crear',
        id:      document.getElementById('cat-id').value,
        nombre:  document.getElementById('cat-nombre').value,
        icono:   document.getElementById('cat-icono').value,
        orden:   document.getElementById('cat-orden').value,
        activo:  document.getElementById('cat-activo').value,
    };

    try {
        const res  = await fetch(BASE_URL + '/api/admin_categorias.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito(json.message);
            Modal.cerrar('modal-categoria');
            setTimeout(() => location.reload(), 700);
        } else {
            Toast.error(json.message);
        }
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function eliminarCategoria(id, nombre) {
    confirmar(`¿Eliminar la categoría "${nombre}"?  Se eliminarán también sus productos.`, async () => {
        try {
            const res  = await fetch(BASE_URL + '/api/admin_categorias.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({ accion: 'eliminar', id })
            });
            const json = await res.json();
            if (json.success) { Toast.exito(json.message); setTimeout(() => location.reload(), 700); }
            else Toast.error(json.message);
        } catch(e) { Toast.error('Error de conexión'); }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
