<?php
/**
 * roles/superadmin/dashboard.php — Panel global del desarrollador
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['superadmin']);

$db = getDB();

// Métricas globales
$stRest = $db->query("SELECT COUNT(*) FROM restaurantes WHERE activo = 1");
$totalRestaurantes = $stRest->fetchColumn();

$stUsr = $db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND rol != 'superadmin'");
$totalUsuarios = $stUsr->fetchColumn();

$hoy = date('Y-m-d');
$stVentas = $db->prepare("SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS cant FROM pedidos WHERE estado='cobrado' AND DATE(created_at) = ?");
$stVentas->execute([$hoy]);
$ventasHoy = $stVentas->fetch();

$stActivos = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'activo'");
$pedidosActivos = $stActivos->fetchColumn();

// Restaurantes con métricas
$stRestList = $db->query("
    SELECT r.id, r.nombre, r.activo,
           COUNT(DISTINCT u.id)  AS usuarios,
           COUNT(DISTINCT m.id)  AS mesas,
           COUNT(DISTINCT pe.id) AS pedidos_hoy,
           COALESCE(SUM(pe.total),0) AS ventas_hoy
    FROM restaurantes r
    LEFT JOIN usuarios u   ON u.restaurante_id = r.id
    LEFT JOIN mesas m      ON m.restaurante_id = r.id AND m.activo = 1
    LEFT JOIN pedidos pe   ON pe.restaurante_id = r.id AND pe.estado = 'cobrado' AND DATE(pe.created_at) = CURDATE()
    GROUP BY r.id
    ORDER BY r.id DESC
");
$restaurantes = $stRestList->fetchAll();

// Últimos logs de acceso
$stLogs = $db->query("
    SELECT la.accion, la.ip, la.created_at, u.nombre, u.rol
    FROM logs_acceso la
    LEFT JOIN usuarios u ON u.id = la.usuario_id
    ORDER BY la.created_at DESC
    LIMIT 20
");
$logs = $stLogs->fetchAll();

$pageTitle  = 'Panel Superadmin';
$activeMenu = 'dashboard';
require_once '../../includes/header.php';
?>

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><span class="menu-icon">🌐</span> Dashboard Global</a></li>
            <li><a href="restaurantes.php"><span class="menu-icon">🏪</span> Restaurantes</a></li>
        </ul>
        <div style="padding:20px 16px;border-top:1px solid var(--borde);margin-top:20px;">
            <div style="font-size:.73rem;font-weight:700;color:var(--texto-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Acceso Admin</div>
            <?php foreach ($restaurantes as $r): ?>
            <a href="<?= BASE_URL ?>/roles/admin/dashboard.php?set_rest_id=<?= $r['id'] ?>"
               style="display:block;font-size:.8rem;padding:6px 8px;border-radius:6px;color:var(--texto);margin-bottom:4px;transition:.15s;"
               onmouseover="this.style.background='var(--fondo)'"
               onmouseout="this.style.background='transparent'">
                🏪 <?= htmlspecialchars($r['nombre']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <!-- Encabezado -->
            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1>🌐 Panel Superadmin</h1>
                    <p>Vista global del sistema · <?= date('d/m/Y H:i') ?></p>
                </div>
                <div style="background:linear-gradient(135deg,#8E44AD,#6C3483);color:#fff;padding:8px 16px;border-radius:99px;font-size:.8rem;font-weight:700;">
                    🔮 R.DEV · Desarrollador
                </div>
            </div>

            <!-- Métricas globales -->
            <div class="stats-grid mb-24">
                <div class="stat-card rojo">
                    <div class="stat-icon">🏪</div>
                    <div class="stat-valor"><?= $totalRestaurantes ?></div>
                    <div class="stat-label">Restaurantes activos</div>
                </div>
                <div class="stat-card verde">
                    <div class="stat-icon">👥</div>
                    <div class="stat-valor"><?= $totalUsuarios ?></div>
                    <div class="stat-label">Usuarios totales</div>
                </div>
                <div class="stat-card naranja">
                    <div class="stat-icon">💰</div>
                    <div class="stat-valor">S/ <?= number_format($ventasHoy['total'], 2) ?></div>
                    <div class="stat-label">Ventas globales hoy</div>
                </div>
                <div class="stat-card dorado">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-valor"><?= $pedidosActivos ?></div>
                    <div class="stat-label">Pedidos activos ahora</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                <!-- Tabla de restaurantes -->
                <div class="card">
                    <div class="card-title d-flex align-center justify-between">
                        <span>🏪 Restaurantes</span>
                        <a href="restaurantes.php" class="btn btn-ghost btn-sm">Ver todos →</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Mesas</th>
                                    <th>Ventas hoy</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restaurantes as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['nombre']) ?></strong><br>
                                        <span style="font-size:.72rem;color:var(--texto-light);"><?= $r['usuarios'] ?> usuarios</span>
                                    </td>
                                    <td><?= $r['mesas'] ?></td>
                                    <td>S/ <?= number_format($r['ventas_hoy'],2) ?></td>
                                    <td><span class="badge <?= $r['activo']?'badge-verde':'badge-gris' ?>"><?= $r['activo']?'Activo':'Inactivo' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Logs de acceso -->
                <div class="card">
                    <div class="card-title">🔍 Actividad reciente</div>
                    <div style="display:flex;flex-direction:column;gap:8px;max-height:320px;overflow-y:auto;">
                        <?php foreach ($logs as $log): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--gris-claro);font-size:.82rem;">
                            <span style="font-size:1rem;"><?= $log['accion']==='login'?'🟢':'🔴' ?></span>
                            <div style="flex:1;">
                                <div style="font-weight:600;"><?= htmlspecialchars($log['nombre'] ?? 'Desconocido') ?></div>
                                <div style="color:var(--texto-light);font-size:.72rem;"><?= ucfirst($log['accion']) ?> · <?= $log['ip'] ?></div>
                            </div>
                            <div style="color:var(--texto-light);font-size:.72rem;white-space:nowrap;">
                                <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?>
                        <div class="empty-state" style="padding:20px 0;"><div class="icon">🔍</div><p>Sin actividad registrada</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Info del sistema -->
            <div class="card">
                <div class="card-title">⚙️ Información del Sistema</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
                    <?php
                    $infos = [
                        ['PHP Version',     PHP_VERSION,                     '🐘'],
                        ['MySQL',           'PDO MySQL',                      '🗃️'],
                        ['Servidor',        $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown', '🖥️'],
                        ['Zona horaria',    date_default_timezone_get(),      '🕐'],
                        ['Versión SaaS',    'v1.0.0',                         '🚀'],
                        ['Desarrollador',   'Reiner Jiménez · R.DEV',         '👨‍💻'],
                    ];
                    foreach ($infos as [$label,$valor,$icon]): ?>
                    <div style="background:var(--fondo);border-radius:8px;padding:12px;border:1px solid var(--borde);">
                        <div style="font-size:1.2rem;margin-bottom:4px;"><?= $icon ?></div>
                        <div style="font-size:.7rem;color:var(--texto-light);font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><?= $label ?></div>
                        <div style="font-size:.85rem;font-weight:700;color:var(--texto);margin-top:2px;"><?= htmlspecialchars($valor) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
