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

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon">🌐</span> Dashboard Global</a></li>
            <li><a href="restaurantes.php" class="active"><span class="menu-icon">🏪</span> Restaurantes</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1>🏪 Restaurantes</h1>
                    <p><?= count($restaurantes) ?> restaurante(s) registrado(s)</p>
                </div>
                <button class="btn btn-primario" onclick="nuevoRestaurante()">➕ Nuevo Restaurante</button>
            </div>

            <!-- Cards de restaurantes -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
                <?php foreach ($restaurantes as $r): ?>
                <div class="card" style="border-left:4px solid <?= $r['activo']?'var(--exito)':'var(--gris)' ?>;">
                    <div class="d-flex align-center justify-between mb-12">
                        <div>
                            <h3 style="font-size:1.1rem;"><?= htmlspecialchars($r['nombre']) ?></h3>
                            <span class="badge <?= $r['activo']?'badge-verde':'badge-gris' ?> mt-4">
                                <?= $r['activo'] ? '✅ Activo' : '❌ Inactivo' ?>
                            </span>
                        </div>
                        <div style="font-size:2.5rem;">🍽️</div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                        <div style="background:var(--fondo);border-radius:8px;padding:10px;text-align:center;">
                            <div style="font-size:1.3rem;font-weight:800;"><?= $r['total_usuarios'] ?></div>
                            <div style="font-size:.7rem;color:var(--texto-light);">Usuarios</div>
                        </div>
                        <div style="background:var(--fondo);border-radius:8px;padding:10px;text-align:center;">
                            <div style="font-size:1.3rem;font-weight:800;"><?= $r['total_mesas'] ?></div>
                            <div style="font-size:.7rem;color:var(--texto-light);">Mesas</div>
                        </div>
                    </div>

                    <?php if ($r['admin_nombre']): ?>
                    <div style="background:var(--fondo);border-radius:8px;padding:10px;font-size:.8rem;margin-bottom:14px;">
                        <strong>👤 Admin:</strong> <?= htmlspecialchars($r['admin_nombre']) ?><br>
                        <span style="color:var(--texto-light);"><?= htmlspecialchars($r['admin_email']) ?></span>
                    </div>
                    <?php else: ?>
                    <div style="background:#FEF9E7;border-radius:8px;padding:10px;font-size:.8rem;color:#B7770D;margin-bottom:14px;">
                        ⚠️ Sin administrador asignado
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-ghost btn-sm" style="flex:1;"
                            onclick='editarRestaurante(<?= json_encode($r) ?>)'>
                            ✏️ Editar
                        </button>
                        <button class="btn btn-naranja btn-sm" style="flex:1;"
                            onclick="crearAdminRestaurante(<?= $r['id'] ?>, '<?= htmlspecialchars($r['nombre'], ENT_QUOTES) ?>')">
                            👤 Crear Admin
                        </button>
                        <button class="btn btn-peligro btn-sm"
                            onclick="toggleRestaurante(<?= $r['id'] ?>, <?= $r['activo'] ?>)">
                            <?= $r['activo'] ? '⏸️' : '▶️' ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (!$restaurantes): ?>
                <div class="empty-state" style="grid-column:1/-1;">
                    <div class="icon">🏪</div>
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
            <div class="modal-title" id="modal-rest-titulo">➕ Nuevo Restaurante</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-restaurante')">✕</button>
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
                        <option value="1">✅ Activo</option>
                        <option value="0">❌ Inactivo</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-restaurante')">Cancelar</button>
            <button class="btn btn-primario btn-full" onclick="guardarRestaurante()" id="btn-guardar-rest">💾 Guardar</button>
        </div>
    </div>
</div>

<!-- Modal: Crear Admin para Restaurante -->
<div class="modal-overlay" id="modal-admin-rest">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-admin-titulo">👤 Crear Administrador</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-admin-rest')">✕</button>
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
            <button class="btn btn-primario btn-full" onclick="guardarAdminRest()" id="btn-guardar-admin">💾 Crear Admin</button>
        </div>
    </div>
</div>

<script>
let modoEdicionRest = false;

function nuevoRestaurante() {
    modoEdicionRest = false;
    document.getElementById('modal-rest-titulo').textContent = '➕ Nuevo Restaurante';
    document.getElementById('form-rest').reset();
    document.getElementById('rest-id').value = '';
    Modal.abrir('modal-restaurante');
}

function editarRestaurante(r) {
    modoEdicionRest = true;
    document.getElementById('modal-rest-titulo').textContent = '✏️ Editar Restaurante';
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
        const res  = await fetch(BASE_URL . '/api/superadmin_restaurantes.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito(json.message);
            Modal.cerrar('modal-restaurante');
            setTimeout(() => location.reload(), 700);
        } else Toast.error(json.message);
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function crearAdminRestaurante(restId, restNombre) {
    document.getElementById('modal-admin-titulo').textContent = `👤 Admin para: ${restNombre}`;
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

    if (datos.password.length < 6) {
        Toast.advertencia('La contraseña debe tener al menos 6 caracteres');
        btn.disabled = false;
        return;
    }

    try {
        const res  = await fetch(BASE_URL . '/api/superadmin_restaurantes.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(datos)
        });
        const json = await res.json();
        if (json.success) {
            Toast.exito(json.message);
            Modal.cerrar('modal-admin-rest');
            setTimeout(() => location.reload(), 700);
        } else Toast.error(json.message);
    } catch(e) { Toast.error('Error de conexión'); }
    finally { btn.disabled = false; }
}

function toggleRestaurante(id, activo) {
    const accion = activo ? 'desactivar' : 'activar';
    confirmar(`¿${accion.charAt(0).toUpperCase()+accion.slice(1)} este restaurante?`, async () => {
        try {
            const res  = await fetch(BASE_URL . '/api/superadmin_restaurantes.php', {
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
