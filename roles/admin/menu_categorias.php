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


        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1><i class="fa-solid fa-folder" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Categorías del Menú</h1>
                    <p><?= count($categorias) ?> categorías registradas</p>
                </div>
                <button class="btn btn-primario" onclick="nuevaCategoria()" id="btn-nueva-cat">
                    <i class="fa-solid fa-plus"></i> Nueva Categoría
                </button>
            </div>

            <div class="card">
                <?php if ($categorias): ?>
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
                                <td style="color:var(--text-secondary);font-weight:700;"><?= $c['orden'] ?></td>
                                <td style="font-size:1.5rem;"><?= htmlspecialchars($c['icono']) ?></td>
                                <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                                <td>
                                    <span class="badge <?= $c['activo'] ? 'badge-verde' : 'badge-gris' ?>">
                                        <?= $c['activo'] ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <button class="btn btn-ghost btn-sm" title="Editar"
                                            onclick='editarCategoria(<?= json_encode($c) ?>)'>
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </button>
                                        <button class="btn btn-peligro btn-sm" title="Eliminar"
                                            onclick="eliminarCategoria(<?= $c['id'] ?>, '<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon"><i class="fa-solid fa-folder-open"></i></div>
                    <h3>Sin categorías</h3>
                    <p>Crea la primera categoría del menú</p>
                </div>
                <?php endif; ?>
            </div>

        </div>

<!-- Modal Categoría -->
<div class="modal-overlay" id="modal-categoria">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-cat-titulo">
                <i class="fa-solid fa-folder-plus"></i> Nueva Categoría
            </div>
            <button class="modal-close" onclick="Modal.cerrar('modal-categoria')"><i class="fa-solid fa-xmark"></i></button>
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
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-categoria')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarCategoria()" id="btn-guardar-cat">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
let modoEdicionCat = false;

function nuevaCategoria() {
    modoEdicionCat = false;
    document.getElementById('modal-cat-titulo').innerHTML = '<i class="fa-solid fa-folder-plus"></i> Nueva Categoría';
    document.getElementById('form-cat').reset();
    document.getElementById('cat-icono').value = '🍽️';
    document.getElementById('cat-id').value = '';
    Modal.abrir('modal-categoria');
}

function editarCategoria(cat) {
    modoEdicionCat = true;
    document.getElementById('modal-cat-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Categoría';
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
        } else Toast.error(json.message);
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function eliminarCategoria(id, nombre) {
    confirmar(`¿Eliminar la categoría "${nombre}"? Se eliminarán también sus productos.`, async () => {
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
