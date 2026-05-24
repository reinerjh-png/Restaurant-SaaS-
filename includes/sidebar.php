<?php
/**
 * sidebar.php — Menú lateral dinámico y unificado del sistema
 * Sistema SaaS Restaurante | R.DEV
 */

// Evitar acceso directo si no hay sesión activa
if (empty($_SESSION['rol'])) {
    return;
}

$rol = $_SESSION['rol'];

// Inicializar datos si no vienen definidos
if (!isset($rolLabel)) {
    $rolLabel = [
        'superadmin' => 'Super Admin',
        'admin'      => 'Administrador',
        'atencion'   => 'Atención',
        'cocina'     => 'Cocina',
    ][$rol] ?? $rol;
}
if (!isset($nombreInicial)) {
    $nombreInicial = strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1));
}

// Cargar restaurantes para superadmin (Acceso rápido)
$sidebarRestaurantes = [];
if ($rol === 'superadmin') {
    try {
        $_dbSidebar = getDB();
        $_stSidebar = $_dbSidebar->query("SELECT id, nombre FROM restaurantes WHERE activo = 1 ORDER BY nombre ASC");
        $sidebarRestaurantes = $_stSidebar->fetchAll();
    } catch (Exception $e) {
        /* Silencioso */
    }
}
?>
<aside class="sidebar">
    <ul class="sidebar-menu">
        <?php if ($rol === 'superadmin'): ?>
            <li><a href="<?= BASE_URL ?>/roles/superadmin/dashboard.php"><span class="menu-icon"><i class="fa-solid fa-globe"></i></span> Dashboard Global</a></li>
            <li><a href="<?= BASE_URL ?>/roles/superadmin/restaurantes.php"><span class="menu-icon"><i class="fa-solid fa-store"></i></span> Restaurantes</a></li>
            <li><a href="<?= BASE_URL ?>/roles/superadmin/perfil.php"><span class="menu-icon"><i class="fa-solid fa-user-shield"></i></span> Mi Perfil</a></li>

        <?php elseif ($rol === 'admin'): ?>
            <li><a href="<?= BASE_URL ?>/roles/admin/dashboard.php"><span class="menu-icon"><i class="fa-solid fa-chart-bar"></i></span> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/mesas.php"><span class="menu-icon"><i class="fa-solid fa-chair"></i></span> Mesas</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/menu_categorias.php"><span class="menu-icon"><i class="fa-solid fa-folder"></i></span> Categorías</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/menu_productos.php"><span class="menu-icon"><i class="fa-solid fa-utensils"></i></span> Productos</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/usuarios.php"><span class="menu-icon"><i class="fa-solid fa-users"></i></span> Usuarios</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/reportes.php"><span class="menu-icon"><i class="fa-solid fa-chart-line"></i></span> Reportes</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/historial.php"><span class="menu-icon"><i class="fa-solid fa-clock-rotate-left"></i></span> Historial</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/historial_comprobantes.php"><span class="menu-icon"><i class="fa-solid fa-file-invoice"></i></span> Comprobantes</a></li>
            <li><a href="<?= BASE_URL ?>/roles/admin/config_facturacion.php"><span class="menu-icon"><i class="fa-solid fa-store"></i></span> Mi Restaurante</a></li>

        <?php elseif ($rol === 'atencion'): ?>
            <li><a href="<?= BASE_URL ?>/roles/atencion/dashboard.php"><span class="menu-icon"><i class="fa-solid fa-map-location-dot"></i></span> Mapa de Mesas</a></li>

        <?php elseif ($rol === 'cocina'): ?>
            <li><a href="<?= BASE_URL ?>/roles/cocina/dashboard.php"><span class="menu-icon"><i class="fa-solid fa-kitchen-set"></i></span> Panel Cocina</a></li>
        <?php endif; ?>
    </ul>

    <!-- Acceso rápido a restaurantes (Solo Super Admin) -->
    <?php if ($rol === 'superadmin' && !empty($sidebarRestaurantes)): ?>
        <div style="padding: 12px 20px; border-top: 1px solid var(--border); overflow: hidden;">
            <div style="font-size: .71rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 8px;">Acceso rápido</div>
            <div style="max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px;">
                <?php foreach ($sidebarRestaurantes as $r): ?>
                <a href="<?= BASE_URL ?>/roles/admin/dashboard.php?set_rest_id=<?= $r['id'] ?>"
                   style="display: flex; align-items: center; gap: 8px; font-size: .8rem; padding: 6px 8px; border-radius: var(--radius-sm); color: var(--text-secondary); transition: .15s;"
                   onmouseover="this.style.background='var(--bg)'"
                   onmouseout="this.style.background='transparent'">
                   <i class="fa-solid fa-store" style="font-size: .75rem; flex-shrink: 0;"></i>
                   <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($r['nombre']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Panel de Perfil e Información de Usuario -->
    <div class="sidebar-user-panel" style="padding: 16px 20px; border-top: 1px solid var(--border); background: var(--bg-secondary); margin-top: auto; display: flex; flex-direction: column; gap: 12px; overflow: hidden;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="avatar" style="width: 36px; height: 36px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">
                <?= $nombreInicial ?>
            </div>
            <div style="display: flex; flex-direction: column; min-width: 0; flex: 1;">
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                </span>
                <span class="rol-badge rol-<?= $rol ?>" style="align-self: flex-start; margin-top: 2px;">
                    <?= $rolLabel ?>
                </span>
            </div>
        </div>
        <?php if (!isset($hideLogout) || !$hideLogout): ?>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-ghost btn-sm btn-full" style="min-height: 36px; gap: 8px; justify-content: center; border-color: var(--border); color: var(--danger); background: var(--surface);">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Salir</span>
        </a>
        <?php endif; ?>
    </div>
</aside>
