<?php
/**
 * roles/admin/menu_productos.php — Gestión de productos del menú
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$stCats = $db->prepare("SELECT * FROM categorias WHERE restaurante_id = ? AND activo = 1 ORDER BY orden");
$stCats->execute([$restauranteId]);
$categorias = $stCats->fetchAll();

$stProds = $db->prepare("
    SELECT p.*, c.nombre AS categoria_nombre, c.icono AS categoria_icono
    FROM productos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE p.restaurante_id = ?
    ORDER BY c.orden, p.nombre
");
$stProds->execute([$restauranteId]);
$productos = $stProds->fetchAll();

$pageTitle  = 'Productos del Menú';
$activeMenu = 'productos';
require_once '../../includes/header.php';
?>


        <div class="page-content">

            <div class="d-flex align-center justify-between mb-16">
                <div>
                    <h1><i class="fa-solid fa-utensils" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Productos del Menú</h1>
                    <p><?= count($productos) ?> productos registrados</p>
                </div>
                <button class="btn btn-primario" onclick="nuevoProducto()">
                    <i class="fa-solid fa-plus"></i> Nuevo Producto
                </button>
            </div>

            <!-- Buscador -->
            <div class="mb-16">
                <div class="input-icon-wrap">
                    <span class="input-icon-left"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscador-admin" class="form-control"
                        placeholder="Buscar producto por nombre…"
                        oninput="buscarAdmin(this.value)"
                        style="border-radius:999px;">
                </div>
            </div>

            <!-- Filtro por categoría -->
            <div class="categorias-tabs mb-16">
                <button class="tab-cat active" onclick="filtrarCategoria('', this)">Todos</button>
                <?php foreach ($categorias as $cat): ?>
                <button class="tab-cat" onclick="filtrarCategoria('<?= $cat['id'] ?>', this)">
                    <?= htmlspecialchars($cat['icono']) ?> <?= htmlspecialchars($cat['nombre']) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Grid de productos -->
            <div id="productos-grid" class="productos-grid">
                <?php foreach ($productos as $p): ?>
                <div class="producto-card admin-prod"
                    data-cat="<?= $p['categoria_id'] ?>"
                    data-id="<?= $p['id'] ?>"
                    data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>">
                    <div class="producto-thumb" style="<?= !$p['activo'] ? 'filter:grayscale(1);opacity:.5;' : '' ?>">
                        <?= htmlspecialchars($p['categoria_icono']) ?>
                    </div>
                    <div class="producto-info">
                        <div class="producto-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div style="font-size:.7rem;color:var(--text-secondary);margin:2px 0;"><?= htmlspecialchars($p['categoria_nombre']) ?></div>
                        <div class="producto-precio">S/ <?= number_format($p['precio'], 2) ?></div>
                        <?php if ($p['tiene_opciones']): ?>
                        <div style="font-size:.68rem;color:var(--warning);font-weight:600;margin-top:3px;">
                            <i class="fa-solid fa-sliders"></i> Con opciones
                        </div>
                        <?php endif; ?>
                        <?php if (!$p['activo']): ?>
                        <div style="font-size:.68rem;color:var(--text-muted);font-weight:600;margin-top:3px;">
                            <i class="fa-solid fa-eye-slash"></i> Inactivo
                        </div>
                        <?php endif; ?>
                        <div style="display:flex;gap:5px;margin-top:8px;">
                            <button class="btn btn-ghost btn-sm" style="flex:1;font-size:.73rem;"
                                onclick='editarProducto(<?= json_encode($p) ?>)'>
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-peligro btn-sm" style="flex:1;font-size:.73rem;"
                                onclick="eliminarProducto(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$productos): ?>
            <div class="empty-state">
                <div class="icon"><i class="fa-solid fa-utensils"></i></div>
                <h3>Sin productos registrados</h3>
                <p>Agrega el primer plato del menú</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal: Producto -->
<div class="modal-overlay" id="modal-producto">
    <div class="modal" style="max-width:550px;">
        <div class="modal-header">
            <div class="modal-title" id="modal-prod-titulo"><i class="fa-solid fa-plus"></i> Nuevo Producto</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-producto')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="form-prod">
                <input type="hidden" id="prod-id">
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select id="prod-categoria" class="form-control" required>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['icono'].' '.$cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre del producto</label>
                    <input type="text" id="prod-nombre" class="form-control" placeholder="Ej: Ceviche Clásico" required maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción (opcional)</label>
                    <textarea id="prod-descripcion" class="form-control" placeholder="Ingredientes o descripción breve..." rows="2"></textarea>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Precio (S/)</label>
                        <input type="number" id="prod-precio" class="form-control" step="0.50" min="0.50" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select id="prod-activo" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" id="prod-opciones" style="width:18px;height:18px;">
                        <span class="form-label" style="margin:0;">
                            <i class="fa-solid fa-sliders" style="color:var(--warning);"></i>
                            Tiene opciones personalizables
                        </span>
                    </label>
                </div>
                <!-- Grupos de opciones -->
                <div id="seccion-opciones" style="display:none;background:var(--bg-secondary);border-radius:var(--radius-md);padding:14px;border:1px solid var(--border);">
                    <div class="d-flex align-center justify-between mb-8">
                        <div style="font-weight:700;font-size:.88rem;">Grupos de Opciones</div>
                        <button type="button" class="btn btn-naranja btn-sm" onclick="agregarGrupo()">
                            <i class="fa-solid fa-plus"></i> Agregar Grupo
                        </button>
                    </div>
                    <div id="grupos-container"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-producto')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarProducto()" id="btn-guardar-prod">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Producto
            </button>
        </div>
    </div>
</div>

<script>
let modoEdicionProd = false;
let opcGrupos = [];

document.getElementById('prod-opciones').addEventListener('change', function() {
    document.getElementById('seccion-opciones').style.display = this.checked ? 'block' : 'none';
});

function nuevoProducto() {
    modoEdicionProd = false;
    opcGrupos = [];
    document.getElementById('modal-prod-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Nuevo Producto';
    document.getElementById('form-prod').reset();
    document.getElementById('prod-id').value = '';
    document.getElementById('seccion-opciones').style.display = 'none';
    document.getElementById('grupos-container').innerHTML = '';
    Modal.abrir('modal-producto');
}

function editarProducto(prod) {
    modoEdicionProd = true;
    opcGrupos = [];
    document.getElementById('modal-prod-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar: ' + prod.nombre;
    document.getElementById('prod-id').value          = prod.id;
    document.getElementById('prod-categoria').value   = prod.categoria_id;
    document.getElementById('prod-nombre').value      = prod.nombre;
    document.getElementById('prod-descripcion').value = prod.descripcion || '';
    document.getElementById('prod-precio').value      = prod.precio;
    document.getElementById('prod-activo').value      = prod.activo;
    document.getElementById('prod-opciones').checked  = prod.tiene_opciones == 1;
    document.getElementById('seccion-opciones').style.display = prod.tiene_opciones == 1 ? 'block' : 'none';
    if (prod.tiene_opciones == 1) cargarGruposExistentes(prod.id);
    Modal.abrir('modal-producto');
}

async function cargarGruposExistentes(productoId) {
    try {
        const res  = await fetch(`${BASE_URL}/api/get_opciones.php?producto_id=${productoId}`);
        const json = await res.json();
        if (json.success && json.data) {
            json.data.forEach(g => {
                const grupo = { nombre: g.nombre, orden: g.orden, valores: g.valores.map(v => v.valor) };
                opcGrupos.push(grupo);
                renderizarGrupo(opcGrupos.length - 1, grupo);
            });
        }
    } catch(e) {}
}

function agregarGrupo() {
    const grupo = { nombre: '', orden: opcGrupos.length + 1, valores: [''] };
    opcGrupos.push(grupo);
    renderizarGrupo(opcGrupos.length - 1, grupo);
}

function renderizarGrupo(idx, grupo) {
    const cont = document.getElementById('grupos-container');
    const div  = document.createElement('div');
    div.id = `grupo-${idx}`;
    div.style.cssText = 'background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-md);padding:12px;margin-bottom:10px;';
    div.innerHTML = `
        <div class="d-flex align-center justify-between mb-8">
            <input type="text" placeholder="Ej: ¿Con arroz o fideos?" class="form-control"
                style="flex:1;margin-right:8px;min-height:38px;font-size:.875rem;"
                value="${grupo.nombre}" oninput="opcGrupos[${idx}].nombre = this.value">
            <button type="button" onclick="eliminarGrupo(${idx})"
                style="background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);width:32px;height:32px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="valores-${idx}" style="display:flex;flex-direction:column;gap:6px;"></div>
        <button type="button" class="btn btn-ghost btn-sm mt-8" onclick="agregarValor(${idx})" style="font-size:.78rem;">
            <i class="fa-solid fa-plus"></i> Añadir opción
        </button>
    `;
    cont.appendChild(div);
    if (grupo.valores.length === 0) grupo.valores = [''];
    grupo.valores.forEach((v, vi) => agregarValorRender(idx, vi, v));
}

function agregarValor(grupoIdx) {
    if (!opcGrupos[grupoIdx]) return;
    const vi = opcGrupos[grupoIdx].valores.length;
    opcGrupos[grupoIdx].valores.push('');
    agregarValorRender(grupoIdx, vi, '');
}

function agregarValorRender(grupoIdx, valIdx, valor) {
    const cont = document.getElementById(`valores-${grupoIdx}`);
    const row  = document.createElement('div');
    row.style.cssText = 'display:flex;gap:8px;align-items:center;';
    row.innerHTML = `
        <input type="text" placeholder="Ej: Con Fideos" class="form-control"
            style="flex:1;min-height:38px;font-size:.875rem;"
            value="${valor}"
            oninput="opcGrupos[${grupoIdx}].valores[${valIdx}] = this.value">
        <button type="button" onclick="this.parentElement.remove(); opcGrupos[${grupoIdx}].valores.splice(${valIdx},1);"
            style="background:none;border:none;color:var(--danger);font-size:.875rem;cursor:pointer;padding:4px;min-height:38px;display:flex;align-items:center;">
            <i class="fa-solid fa-minus-circle"></i>
        </button>
    `;
    cont.appendChild(row);
}

function eliminarGrupo(idx) {
    opcGrupos.splice(idx, 1);
    const el = document.getElementById(`grupo-${idx}`);
    if (el) el.remove();
}

async function guardarProducto() {
    const btn = document.getElementById('btn-guardar-prod');
    btn.disabled = true;

    const tieneOpciones = document.getElementById('prod-opciones').checked;
    const datos = {
        accion:        modoEdicionProd ? 'editar' : 'crear',
        id:            document.getElementById('prod-id').value,
        categoria_id:  document.getElementById('prod-categoria').value,
        nombre:        document.getElementById('prod-nombre').value,
        descripcion:   document.getElementById('prod-descripcion').value,
        precio:        document.getElementById('prod-precio').value,
        activo:        document.getElementById('prod-activo').value,
        tiene_opciones: tieneOpciones ? 1 : 0,
        grupos: tieneOpciones ? opcGrupos.filter(g => g.nombre.trim()) : [],
    };

    try {
        const res  = await fetch(BASE_URL + '/api/admin_productos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito(json.message);
            Modal.cerrar('modal-producto');
            setTimeout(() => location.reload(), 700);
        } else Toast.error(json.message);
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function eliminarProducto(id, nombre) {
    confirmar(`¿Eliminar el producto "${nombre}"?`, async () => {
        try {
            const res  = await fetch(BASE_URL + '/api/admin_productos.php', {
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

let catAdmin = '', txtAdmin = '';

function aplicarFiltrosAdmin() {
    const texto = txtAdmin.toLowerCase();
    document.querySelectorAll('.admin-prod').forEach(p => {
        const nombre = (p.dataset.nombre || '').toLowerCase();
        const cat = p.dataset.cat;
        p.style.display = ((!catAdmin || cat == catAdmin) && (!texto || nombre.includes(texto))) ? '' : 'none';
    });
}

function filtrarCategoria(catId, btn) {
    document.querySelectorAll('.tab-cat').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    catAdmin = catId;
    aplicarFiltrosAdmin();
}

function buscarAdmin(texto) { txtAdmin = texto; aplicarFiltrosAdmin(); }
</script>

<?php require_once '../../includes/footer.php'; ?>
