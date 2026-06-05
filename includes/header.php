<?php
/**
 * header.php — Navbar compartida del sistema
 * Requiere que $pageTitle y $activeMenu estén definidos antes de incluir
 * Sistema SaaS Restaurante | R.DEV
 */

// Determinar el rol para badge y colores
$rolLabel = [
    'superadmin' => 'Super Admin',
    'admin'      => 'Administrador',
    'atencion'   => 'Atención',
    'cocina'     => 'Cocina',
][$_SESSION['rol']] ?? $_SESSION['rol'];

$nombreInicial = strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1));

// Prefijo de ruta según rol
$baseRol = BASE_URL . '/roles/' . $_SESSION['rol'];

// ── Branding dinámico por restaurante ──────────────────────────
// Para roles no-superadmin que tienen restaurante_id, cargar su branding
$_brandingLogo   = BASE_URL . '/assets/logo.png'; // fallback genérico
$_brandingNombre = 'R.DEV';                        // fallback genérico
if (!empty($_SESSION['restaurante_id']) && $_SESSION['rol'] !== 'superadmin') {
    // Usar conexión ya existente si está disponible, si no abrir una nueva
    try {
        $_dbB = getDB();
        $_stB = $_dbB->prepare("
            SELECT fc.logo, fc.nombre_comercial, r.nombre
            FROM restaurantes r
            LEFT JOIN facturacion_config fc ON fc.restaurante_id = r.id
            WHERE r.id = ?
        ");
        $_stB->execute([$_SESSION['restaurante_id']]);
        $_brand = $_stB->fetch();
        if ($_brand) {
            // Logo: usar el de facturacion_config si existe y el archivo está en disco
            $__logo = $_brand['logo'] ?? null;
            if ($__logo && file_exists($_SERVER['DOCUMENT_ROOT'] . $__logo)) {
                $_brandingLogo = htmlspecialchars($__logo);
            }
            // Nombre: nombre_comercial tiene prioridad sobre nombre del restaurante
            $_brandingNombre = htmlspecialchars(
                !empty($_brand['nombre_comercial']) ? $_brand['nombre_comercial'] : $_brand['nombre']
            );
        }
    } catch (Exception $_eB) { /* silencioso — usa fallback */ }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#1A1A2E">
    <meta name="description" content="Sistema de gestión para restaurante — R.DEV">
    <title><?= htmlspecialchars($pageTitle ?? 'Sistema Restaurante') ?> | R.DEV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script>
        window.BASE_URL = "<?= BASE_URL ?>";
        window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";

        // Intercept fetch to add CSRF token automatically
        const originalFetch = window.fetch;
        window.fetch = function() {
            let args = Array.prototype.slice.call(arguments);
            let config = args[1] || {};
            
            if (config.method && config.method.toUpperCase() === 'POST') {
                config.headers = config.headers || {};
                if (config.headers instanceof Headers) {
                    config.headers.append('X-CSRF-Token', window.CSRF_TOKEN);
                } else {
                    config.headers['X-CSRF-Token'] = window.CSRF_TOKEN;
                }
                args[1] = config;
            }
            return originalFetch.apply(this, args);
        };
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css?v=<?= filemtime(__DIR__ . '/../assets/css/main.css') ?>">
</head>
<body>

<!-- Navbar principal -->
<nav class="navbar">
    <div class="navbar-brand">
        <?php if ($_SESSION['rol'] === 'superadmin'): ?>
            <img src="<?= BASE_URL ?>/assets/logo.png" alt="R.DEV" style="height:32px;width:auto;">
        <?php else: ?>
            <img src="<?= $_brandingLogo ?>" alt="<?= $_brandingNombre ?>"
                 style="height:36px;width:auto;max-width:120px;object-fit:contain;border-radius:4px;">
            <span style="font-size:.82rem;font-weight:700;color:rgba(255,255,255,.92);margin-left:8px;
                         max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
                         letter-spacing:.01em;">
                <?= $_brandingNombre ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="navbar-right">
        <?php if (($_SESSION['rol'] ?? '') === 'superadmin' && strpos($_SERVER['REQUEST_URI'] ?? '', '/roles/admin/') !== false): ?>
            <a href="<?= BASE_URL ?>/roles/superadmin/dashboard.php"
               style="margin-right:8px; color:rgba(255,255,255,.85); font-size:0.8rem; text-decoration:none; background:rgba(255,255,255,.15); padding:6px 12px; border-radius:6px; border:1px solid rgba(255,255,255,.2); display:inline-flex; align-items:center; gap:6px;">
               <i class="fa-solid fa-arrow-left"></i> Panel global
            </a>
        <?php endif; ?>
    </div>
</nav>

<div class="layout-admin">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">

<!-- Modal de opciones secuenciales (global, disponible en todas las páginas) -->
<div class="modal-overlay" id="modal-opciones">
    <div class="modal">
        <div class="modal-header">
            <div>
                <div class="modal-title" id="modal-opciones-titulo">Elige una opción</div>
                <div style="font-size:.78rem;color:var(--text-secondary);margin-top:2px" id="modal-opciones-subtitulo"></div>
            </div>
        </div>
        <div class="modal-body">
            <div class="opciones-list" id="modal-opciones-lista"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" id="modal-opciones-cancelar">Cancelar</button>
            <button class="btn btn-primario btn-full" id="modal-opciones-confirmar">Continuar <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </div>
</div>

<!-- Modal de confirmación genérico -->
<div class="modal-overlay" id="modal-confirmar">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning);"></i> Confirmar acción
            </div>
        </div>
        <div class="modal-body">
            <p id="modal-confirmar-msg" style="color:var(--text-primary);font-size:.95rem;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-confirmar')">Cancelar</button>
            <button class="btn btn-peligro btn-full" id="modal-confirmar-btn">Confirmar</button>
        </div>
    </div>
</div>
