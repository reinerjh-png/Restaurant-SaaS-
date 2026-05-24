<?php
/**
 * roles/superadmin/restaurantes.php — Gestión multi-tenant de restaurantes
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['superadmin']);

$db = getDB();

$stList = $db->query("
    SELECT r.*,
           COUNT(DISTINCT u.id) AS total_usuarios,
           COUNT(DISTINCT m.id) AS total_mesas,
           adm.nombre AS admin_nombre,
           adm.email  AS admin_email
    FROM restaurantes r
    LEFT JOIN usuarios u   ON u.restaurante_id = r.id AND u.rol != 'superadmin'
    LEFT JOIN mesas m      ON m.restaurante_id = r.id AND m.activo = 1
    LEFT JOIN usuarios adm ON adm.restaurante_id = r.id AND adm.rol = 'admin' AND adm.activo = 1
    GROUP BY r.id
    ORDER BY r.id DESC
");
$restaurantes = $stList->fetchAll();

$pageTitle  = 'Gestión de Restaurantes';
$activeMenu = 'restaurantes';
require_once '../../includes/header.php';
?>


        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1><i class="fa-solid fa-store" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Restaurantes</h1>
                    <p><?= count($restaurantes) ?> restaurante(s) registrado(s)</p>
                </div>
                <button class="btn btn-primario" onclick="nuevoRestaurante()">
                    <i class="fa-solid fa-plus"></i> Nuevo Restaurante
                </button>
            </div>

            <!-- Cards de restaurantes -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
                <?php foreach ($restaurantes as $r): ?>
                <div class="card" style="border-left:4px solid <?= $r['activo']?'var(--success)':'var(--border)' ?>;">
                    <div class="d-flex align-center justify-between mb-12">
                        <div>
                            <h3 style="font-size:1.05rem;"><?= htmlspecialchars($r['nombre']) ?></h3>
                            <span class="badge <?= $r['activo']?'badge-verde':'badge-gris' ?> mt-4">
                                <?= $r['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <div style="width:44px;height:44px;background:var(--primary-light);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.2rem;">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                        <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:10px;text-align:center;">
                            <div style="font-size:1.3rem;font-weight:800;color:var(--text-primary);"><?= $r['total_usuarios'] ?></div>
                            <div style="font-size:.7rem;color:var(--text-secondary);">Usuarios</div>
                        </div>
                        <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:10px;text-align:center;">
                            <div style="font-size:1.3rem;font-weight:800;color:var(--text-primary);"><?= $r['total_mesas'] ?></div>
                            <div style="font-size:.7rem;color:var(--text-secondary);">Mesas</div>

                    <?php if ($r['admin_nombre']): ?>
                    <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:10px;font-size:.8rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                        <i class="fa-solid fa-user-tie" style="color:var(--primary);"></i>
                        <div>
                            <div style="font-weight:600;"><?= htmlspecialchars($r['admin_nombre']) ?></div>
                            <div style="color:var(--text-muted);font-size:.75rem;"><?= htmlspecialchars($r['admin_email']) ?></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="background:var(--warning-bg);border-radius:var(--radius-md);padding:10px;font-size:.8rem;color:var(--warning);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Sin administrador asignado
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-ghost btn-sm" style="flex:1;"
                            onclick='editarRestaurante(<?= json_encode($r) ?>)'>
                            <i class="fa-solid fa-pen"></i> Editar
                        </button>
                        <button class="btn btn-naranja btn-sm" style="flex:1;"
                            onclick="crearAdminRestaurante(<?= $r['id'] ?>, '<?= htmlspecialchars($r['nombre'], ENT_QUOTES) ?>')">
                            <i class="fa-solid fa-user-plus"></i> Admin
                        </button>
                        <button class="btn btn-peligro btn-sm" title="<?= $r['activo'] ? 'Desactivar' : 'Activar' ?>"
                            onclick="toggleRestaurante(<?= $r['id'] ?>, <?= $r['activo'] ?>)">
                            <i class="fa-solid <?= $r['activo'] ? 'fa-pause' : 'fa-play' ?>"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (!$restaurantes): ?>
                <div class="empty-state" style="grid-column:1/-1;">
                    <div class="icon"><i class="fa-solid fa-store"></i></div>
                    <h3>Sin restaurantes registrados</h3>
                    <p>Crea el primer restaurante del sistema</p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Restaurante -->
<div class="modal-overlay" id="modal-restaurante">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-rest-titulo"><i class="fa-solid fa-plus"></i> Nuevo Restaurante</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-restaurante')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="form-rest">
                <input type="hidden" id="rest-id">
                <div class="form-group">
                    <label class="form-label">Nombre del restaurante</label>
                    <input type="text" id="rest-nombre" class="form-control" placeholder="Ej: La Cevichería de Tingo María" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="rest-activo" class="form-control">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-restaurante')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarRestaurante()" id="btn-guardar-rest">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Crear Admin para Restaurante -->
<div class="modal-overlay" id="modal-admin-rest">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-admin-titulo"><i class="fa-solid fa-user-plus"></i> Crear Administrador</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-admin-rest')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="form-admin-rest" autocomplete="off">
                <input type="hidden" id="admin-rest-id">
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" id="admin-nombre" class="form-control" placeholder="Ej: Carlos Gerente" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" id="admin-email" class="form-control" placeholder="admin@restaurante.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña inicial</label>
                    <input type="password" id="admin-password" class="form-control" placeholder="Mín. 6 caracteres" required autocomplete="new-password">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-admin-rest')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarAdminRest()" id="btn-guardar-admin">
                <i class="fa-solid fa-floppy-disk"></i> Crear Admin
            </button>
        </div>
    </div>
</div>

<script>
let modoEdicionRest = false;

function nuevoRestaurante() {
    modoEdicionRest = false;
    document.getElementById('modal-rest-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Nuevo Restaurante';
    document.getElementById('form-rest').reset();
    document.getElementById('rest-id').value = '';
    Modal.abrir('modal-restaurante');
}

function editarRestaurante(r) {
    modoEdicionRest = true;
    document.getElementById('modal-rest-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Restaurante';
    document.getElementById('rest-id').value     = r.id;
    document.getElementById('rest-nombre').value = r.nombre;
    document.getElementById('rest-activo').value = r.activo;
    Modal.abrir('modal-restaurante');
}

async function guardarRestaurante() {
    const btn = document.getElementById('btn-guardar-rest');
    btn.disabled = true;
    const datos = {
        accion: modoEdicionRest ? 'editar' : 'crear',
        id:     document.getElementById('rest-id').value,
        nombre: document.getElementById('rest-nombre').value,
        activo: document.getElementById('rest-activo').value,
    };
    try {
        const res  = await fetch(BASE_URL + '/api/superadmin_restaurantes.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) { Toast.exito(json.message); Modal.cerrar('modal-restaurante'); setTimeout(() => location.reload(), 700); }
        else Toast.error(json.message);
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function crearAdminRestaurante(restId, restNombre) {
    document.getElementById('modal-admin-titulo').innerHTML = `<i class="fa-solid fa-user-plus"></i> Admin para: ${restNombre}`;
    document.getElementById('admin-rest-id').value = restId;
    document.getElementById('form-admin-rest').reset();
    document.getElementById('admin-rest-id').value = restId;
    Modal.abrir('modal-admin-rest');
}

async function guardarAdminRest() {
    const btn = document.getElementById('btn-guardar-admin');
    btn.disabled = true;
    const datos = {
        accion:         'crear_admin',
        restaurante_id: document.getElementById('admin-rest-id').value,
        nombre:         document.getElementById('admin-nombre').value,
        email:          document.getElementById('admin-email').value,
        password:       document.getElementById('admin-password').value,
    };
    if (datos.password.length < 6) { Toast.advertencia('La contraseña debe tener al menos 6 caracteres'); btn.disabled = false; return; }
    try {
        const res  = await fetch(BASE_URL + '/api/superadmin_restaurantes.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) { Toast.exito(json.message); Modal.cerrar('modal-admin-rest'); setTimeout(() => location.reload(), 700); }
        else Toast.error(json.message);
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function toggleRestaurante(id, activo) {
    const accion = activo ? 'desactivar' : 'activar';
    confirmar(`¿${accion.charAt(0).toUpperCase()+accion.slice(1)} este restaurante?`, async () => {
        try {
            const res  = await fetch(BASE_URL + '/api/superadmin_restaurantes.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({ accion: 'toggle', id, nuevo_activo: activo ? 0 : 1 })
            });
            const json = await res.json();
            if (json.success) { Toast.exito(json.message); setTimeout(() => location.reload(), 700); }
            else Toast.error(json.message);
        } catch(e) { Toast.error('Error de conexión'); }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
