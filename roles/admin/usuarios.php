<?php
/**
 * roles/admin/usuarios.php — Gestión de usuarios del restaurante
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

$st = $db->prepare("
    SELECT id, nombre, email, rol, activo, created_at
    FROM usuarios
    WHERE restaurante_id = ? AND rol != 'superadmin'
    ORDER BY rol, nombre
");
$st->execute([$restauranteId]);
$usuarios = $st->fetchAll();

$pageTitle  = 'Usuarios';
$activeMenu = 'usuarios';
require_once '../../includes/header.php';
?>

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
            <li><a href="mesas.php"><span class="menu-icon">🪑</span> Mesas</a></li>
            <li><a href="menu_categorias.php"><span class="menu-icon">📂</span> Categorías</a></li>
            <li><a href="menu_productos.php"><span class="menu-icon">🍽️</span> Productos</a></li>
            <li><a href="usuarios.php" class="active"><span class="menu-icon">👥</span> Usuarios</a></li>
            <li><a href="reportes.php"><span class="menu-icon">📈</span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon">🗂️</span> Historial</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1>👥 Usuarios del Sistema</h1>
                    <p><?= count($usuarios) ?> usuarios registrados</p>
                </div>
                <button class="btn btn-primario" onclick="nuevoUsuario()">➕ Nuevo Usuario</button>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="avatar" style="background:var(--rojo);color:#fff;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;">
                                            <?= strtoupper(substr($u['nombre'],0,1)) ?>
                                        </div>
                                        <?= htmlspecialchars($u['nombre']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge <?= ['admin'=>'badge-naranja','atencion'=>'badge-azul','cocina'=>'badge-verde'][$u['rol']] ?? 'badge-gris' ?>">
                                        <?= ucfirst($u['rol']) ?>
                                    </span>
                                </td>
                                <td><span class="badge <?= $u['activo'] ? 'badge-verde' : 'badge-gris' ?>"><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                                <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-ghost btn-sm"
                                        onclick='editarUsuario(<?= json_encode($u) ?>)'>✏️ Editar</button>
                                    <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                    <button class="btn btn-peligro btn-sm"
                                        onclick="eliminarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>')">🗑️</button>
                                    <?php endif; ?>
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

<!-- Modal Usuario -->
<div class="modal-overlay" id="modal-usuario">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-usr-titulo">➕ Nuevo Usuario</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-usuario')">✕</button>
        </div>
        <div class="modal-body">
            <form id="form-usr" autocomplete="off">
                <input type="hidden" id="usr-id">
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" id="usr-nombre" class="form-control" placeholder="Ej: María García" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" id="usr-email" class="form-control" placeholder="usuario@restaurante.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select id="usr-rol" class="form-control">
                        <option value="atencion">👩‍💼 Atención al cliente</option>
                        <option value="cocina">👨‍🍳 Cocina</option>
                        <option value="admin">⚙️ Administrador</option>
                    </select>
                </div>
                <div class="form-group" id="grp-password">
                    <label class="form-label"><span id="lbl-pass">Contraseña</span></label>
                    <input type="password" id="usr-password" class="form-control" placeholder="Mín. 6 caracteres" autocomplete="new-password">
                    <div class="form-error" id="lbl-pass-edit" style="display:none;color:var(--texto-light);">Dejar en blanco para mantener la contraseña actual</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="usr-activo" class="form-control">
                        <option value="1">✅ Activo</option>
                        <option value="0">❌ Inactivo</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-usuario')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarUsuario()" id="btn-guardar-usr">💾 Guardar</button>
        </div>
    </div>
</div>

<script>
let modoEdicionUsr = false;

function nuevoUsuario() {
    modoEdicionUsr = false;
    document.getElementById('modal-usr-titulo').textContent = '➕ Nuevo Usuario';
    document.getElementById('form-usr').reset();
    document.getElementById('usr-id').value = '';
    document.getElementById('lbl-pass-edit').style.display = 'none';
    Modal.abrir('modal-usuario');
}

function editarUsuario(usr) {
    modoEdicionUsr = true;
    document.getElementById('modal-usr-titulo').textContent = '✏️ Editar: ' + usr.nombre;
    document.getElementById('usr-id').value     = usr.id;
    document.getElementById('usr-nombre').value = usr.nombre;
    document.getElementById('usr-email').value  = usr.email;
    document.getElementById('usr-rol').value    = usr.rol;
    document.getElementById('usr-activo').value = usr.activo;
    document.getElementById('usr-password').value = '';
    document.getElementById('lbl-pass-edit').style.display = 'block';
    Modal.abrir('modal-usuario');
}

async function guardarUsuario() {
    const btn = document.getElementById('btn-guardar-usr');
    btn.disabled = true;

    const datos = {
        accion:   modoEdicionUsr ? 'editar' : 'crear',
        id:       document.getElementById('usr-id').value,
        nombre:   document.getElementById('usr-nombre').value,
        email:    document.getElementById('usr-email').value,
        rol:      document.getElementById('usr-rol').value,
        password: document.getElementById('usr-password').value,
        activo:   document.getElementById('usr-activo').value,
    };

    if (!modoEdicionUsr && datos.password.length < 6) {
        Toast.advertencia('La contraseña debe tener al menos 6 caracteres');
        btn.disabled = false;
        return;
    }

    try {
        const res  = await fetch('/sistema_restaurante/api/admin_usuarios.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito(json.message);
            Modal.cerrar('modal-usuario');
            setTimeout(() => location.reload(), 700);
        } else Toast.error(json.message);
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function eliminarUsuario(id, nombre) {
    confirmar(`¿Eliminar al usuario "${nombre}"?`, async () => {
        try {
            const res  = await fetch('/sistema_restaurante/api/admin_usuarios.php', {
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
