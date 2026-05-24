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


        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1><i class="fa-solid fa-users" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Usuarios del Sistema</h1>
                    <p><?= count($usuarios) ?> usuarios registrados</p>
                </div>
                <button class="btn btn-primario" onclick="nuevoUsuario()" id="btn-nuevo-usr">
                    <i class="fa-solid fa-plus"></i> Nuevo Usuario
                </button>
            </div>

            <div class="card">
                <?php if ($usuarios): ?>
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
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="user-avatar avatar-<?= $u['rol'] ?>">
                                            <?= strtoupper(substr($u['nombre'],0,1)) ?>
                                        </div>
                                        <span style="font-weight:600;"><?= htmlspecialchars($u['nombre']) ?></span>
                                    </div>
                                </td>
                                <td style="color:var(--text-secondary);font-size:.875rem;"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge <?= ['admin'=>'badge-azul','atencion'=>'badge-verde','cocina'=>'badge-naranja'][$u['rol']] ?? 'badge-gris' ?>">
                                        <?= ucfirst($u['rol']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $u['activo'] ? 'badge-verde' : 'badge-gris' ?>">
                                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td style="color:var(--text-secondary);font-size:.82rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <button class="btn btn-ghost btn-sm" title="Editar"
                                            onclick='editarUsuario(<?= json_encode($u) ?>)'>
                                            <i class="fa-solid fa-pen"></i>
                                            <span class="btn-label-desktop"> Editar</span>
                                        </button>
                                        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                        <button class="btn btn-peligro btn-sm" title="Eliminar"
                                            onclick="eliminarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon"><i class="fa-solid fa-users"></i></div>
                    <h3>Sin usuarios registrados</h3>
                    <p>Agrega el primer usuario del sistema</p>
                </div>
                <?php endif; ?>
            </div>

        </div>

<!-- Modal Usuario -->
<div class="modal-overlay" id="modal-usuario">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-usr-titulo">
                <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
            </div>
            <button class="modal-close" onclick="Modal.cerrar('modal-usuario')"><i class="fa-solid fa-xmark"></i></button>
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
                        <option value="atencion">Atención al cliente</option>
                        <option value="cocina">Cocina</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group" id="grp-password">
                    <label class="form-label"><span id="lbl-pass">Contraseña</span></label>
                    <input type="password" id="usr-password" class="form-control" placeholder="Mín. 6 caracteres" autocomplete="new-password">
                    <div class="form-error" id="lbl-pass-edit" style="display:none;">Dejar en blanco para mantener la contraseña actual</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="usr-activo" class="form-control">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-usuario')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarUsuario()" id="btn-guardar-usr">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
let modoEdicionUsr = false;

function nuevoUsuario() {
    modoEdicionUsr = false;
    document.getElementById('modal-usr-titulo').innerHTML = '<i class="fa-solid fa-user-plus"></i> Nuevo Usuario';
    document.getElementById('form-usr').reset();
    document.getElementById('usr-id').value = '';
    document.getElementById('lbl-pass-edit').style.display = 'none';
    Modal.abrir('modal-usuario');
}

function editarUsuario(usr) {
    modoEdicionUsr = true;
    document.getElementById('modal-usr-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar: ' + usr.nombre;
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
        const res  = await fetch(BASE_URL + '/api/admin_usuarios.php', {
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
            const res  = await fetch(BASE_URL + '/api/admin_usuarios.php', {
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
