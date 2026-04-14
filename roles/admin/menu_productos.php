<?php
/**
 * roles/admin/menu_productos.php — Gestión de productos del menú
 * Incluye: crear/editar/eliminar productos con opciones secuenciales
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// Categorías para el filtro y el formulario
$stCats = $db->prepare("SELECT * FROM categorias WHERE restaurante_id = ? AND activo = 1 ORDER BY orden");
$stCats->execute([$restauranteId]);
$categorias = $stCats->fetchAll();

// Productos con categoría
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

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
            <li><a href="mesas.php"><span class="menu-icon">🪑</span> Mesas</a></li>
            <li><a href="menu_categorias.php"><span class="menu-icon">📂</span> Categorías</a></li>
            <li><a href="menu_productos.php" class="active"><span class="menu-icon">🍽️</span> Productos</a></li>
            <li><a href="usuarios.php"><span class="menu-icon">👥</span> Usuarios</a></li>
            <li><a href="reportes.php"><span class="menu-icon">📈</span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon">🗂️</span> Historial</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-16">
                <div>
                    <h1>🍽️ Productos del Menú</h1>
                    <p><?= count($productos) ?> productos registrados</p>
                </div>
                <button class="btn btn-primario" onclick="nuevoProducto()">➕ Nuevo Producto</button>
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
                <div class="producto-card admin-prod" data-cat="<?= $p['categoria_id'] ?>" data-id="<?= $p['id'] ?>">
                    <div class="producto-thumb" style="<?= !$p['activo'] ? 'filter:grayscale(1);opacity:.5;' : '' ?>">
                        <?= htmlspecialchars($p['categoria_icono']) ?>
                    </div>
                    <div class="producto-info">
                        <div class="producto-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div style="font-size:.7rem;color:var(--texto-light);margin:2px 0;"><?= htmlspecialchars($p['categoria_nombre']) ?></div>
                        <div class="producto-precio">S/ <?= number_format($p['precio'], 2) ?></div>
                        <?php if ($p['tiene_opciones']): ?>
                        <div style="font-size:.68rem;color:var(--naranja);font-weight:600;margin-top:3px;">⚙️ Con opciones</div>
                        <?php endif; ?>
                        <?php if (!$p['activo']): ?>
                        <div style="font-size:.68rem;color:var(--gris);font-weight:600;margin-top:3px;">❌ Inactivo</div>
                        <?php endif; ?>
                        <div style="display:flex;gap:5px;margin-top:8px;">
                            <button class="btn btn-ghost btn-sm" style="flex:1;font-size:.73rem;"
                                onclick='editarProducto(<?= json_encode($p) ?>)'>✏️</button>
                            <button class="btn btn-peligro btn-sm" style="flex:1;font-size:.73rem;"
                                onclick="eliminarProducto(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>')">🗑️</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$productos): ?>
            <div class="empty-state">
                <div class="icon">🍽️</div>
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
            <div class="modal-title" id="modal-prod-titulo">➕ Nuevo Producto</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-producto')">✕</button>
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
                            <option value="1">✅ Activo</option>
                            <option value="0">❌ Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" id="prod-opciones" style="width:18px;height:18px;">
                        <span class="form-label" style="margin:0;">⚙️ Este plato tiene opciones especiales (ej: tipo de arroz, acompañamientos)</span>
                    </label>
                </div>

                <!-- Sección de opciones (solo si tiene_opciones = 1) -->
                <div id="seccion-opciones" style="display:none;background:var(--fondo);border-radius:var(--radio-sm);padding:14px;border:1px solid var(--borde);">
                    <div class="d-flex align-center justify-between mb-8">
                        <div style="font-weight:700;font-size:.88rem;">Grupos de Opciones</div>
                        <button type="button" class="btn btn-naranja btn-sm" onclick="agregarGrupo()">+ Agregar Grupo</button>
                    </div>
                    <div id="grupos-container"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-producto')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarProducto()" id="btn-guardar-prod">💾 Guardar Producto</button>
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
    document.getElementById('modal-prod-titulo').textContent = '➕ Nuevo Producto';
    document.getElementById('form-prod').reset();
    document.getElementById('prod-id').value = '';
    document.getElementById('seccion-opciones').style.display = 'none';
    document.getElementById('grupos-container').innerHTML = '';
    Modal.abrir('modal-producto');
}

function editarProducto(prod) {
    modoEdicionProd = true;
    opcGrupos = [];
    document.getElementById('modal-prod-titulo').textContent = '✏️ Editar: ' + prod.nombre;
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
        const res  = await fetch(`/sistema_restaurante/api/get_opciones.php?producto_id=${productoId}`);
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
    div.style.cssText = 'background:#fff;border:1px solid var(--borde);border-radius:8px;padding:12px;margin-bottom:10px;';
    div.innerHTML = `
        <div class="d-flex align-center justify-between mb-8">
            <input type="text" placeholder="Pregunta (ej: ¿Con arroz o fideos?)" class="form-control"
                style="flex:1;margin-right:8px;height:36px;font-size:.82rem;"
                value="${grupo.nombre}" oninput="opcGrupos[${idx}].nombre = this.value">
            <button type="button" onclick="eliminarGrupo(${idx})" style="background:var(--peligro);color:#fff;border:none;border-radius:6px;width:30px;height:30px;cursor:pointer;font-size:.85rem;">✕</button>
        </div>
        <div id="valores-${idx}" style="display:flex;flex-direction:column;gap:6px;"></div>
        <button type="button" class="btn btn-ghost btn-sm mt-8" onclick="agregarValor(${idx})" style="font-size:.75rem;">+ Añadir opción</button>
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
            style="flex:1;height:34px;font-size:.82rem;"
            value="${valor}"
            oninput="opcGrupos[${grupoIdx}].valores[${valIdx}] = this.value">
        <button type="button" onclick="this.parentElement.remove(); opcGrupos[${grupoIdx}].valores.splice(${valIdx},1);"
            style="background:none;border:none;color:var(--gris);font-size:1rem;cursor:pointer;">✕</button>
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
        accion:       modoEdicionProd ? 'editar' : 'crear',
        id:           document.getElementById('prod-id').value,
        categoria_id: document.getElementById('prod-categoria').value,
        nombre:       document.getElementById('prod-nombre').value,
        descripcion:  document.getElementById('prod-descripcion').value,
        precio:       document.getElementById('prod-precio').value,
        activo:       document.getElementById('prod-activo').value,
        tiene_opciones: tieneOpciones ? 1 : 0,
        grupos: tieneOpciones ? opcGrupos.filter(g => g.nombre.trim()) : [],
    };

    try {
        const res  = await fetch('/sistema_restaurante/api/admin_productos.php', {
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
            const res  = await fetch('/sistema_restaurante/api/admin_productos.php', {
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

function filtrarCategoria(catId, btn) {
    document.querySelectorAll('.tab-cat').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.admin-prod').forEach(p => {
        p.style.display = (!catId || p.dataset.cat == catId) ? '' : 'none';
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
