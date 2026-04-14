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
$baseRol = '/sistema_restaurante/roles/' . $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#C0392B">
    <meta name="description" content="Sistema de gestión para restaurante — R.DEV">
    <title><?= htmlspecialchars($pageTitle ?? 'Sistema Restaurante') ?> | R.DEV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sistema_restaurante/assets/css/main.css">
</head>
<body>

<!-- Navbar principal -->
<nav class="navbar">
    <div class="navbar-brand">
        <img src="/sistema_restaurante/assets/logo.png" alt="Sabor Perú" style="height: 32px; width: auto; align-self: center;">
    </div>
    <div class="navbar-right">
        <?php if (($_SESSION['rol'] ?? '') === 'superadmin' && strpos($_SERVER['REQUEST_URI'] ?? '', '/roles/admin/') !== false): ?>
            <a href="/sistema_restaurante/roles/superadmin/dashboard.php" style="margin-right:15px; color:white; font-size:0.8rem; text-decoration:none; background:rgba(255,255,255,0.2); padding:5px 10px; border-radius:4px; border:1px solid rgba(255,255,255,0.3);">🔙 Volver al panel global</a>
        <?php endif; ?>
        <div class="navbar-user">
            <div class="avatar"><?= $nombreInicial ?></div>
            <span><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></span>
            <span class="rol-badge rol-<?= $_SESSION['rol'] ?>"><?= $rolLabel ?></span>
        </div>
        <a href="/sistema_restaurante/auth/logout.php" class="btn-logout">⏻ Salir</a>
    </div>
</nav>

<!-- Modal de opciones secuenciales (global, disponible en todas las páginas) -->
<div class="modal-overlay" id="modal-opciones">
    <div class="modal">
        <div class="modal-header">
            <div>
                <div class="modal-title" id="modal-opciones-titulo">Elige una opción</div>
                <div style="font-size:.78rem;color:var(--texto-light);margin-top:2px" id="modal-opciones-subtitulo"></div>
            </div>
        </div>
        <div class="modal-body">
            <div class="opciones-list" id="modal-opciones-lista"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" id="modal-opciones-cancelar">Cancelar</button>
            <button class="btn btn-primario btn-full" id="modal-opciones-confirmar">Continuar →</button>
        </div>
    </div>
</div>

<!-- Modal de confirmación genérico -->
<div class="modal-overlay" id="modal-confirmar">
    <div class="modal">
        <div class="modal-header"><div class="modal-title">⚠️ Confirmar acción</div></div>
        <div class="modal-body">
            <p id="modal-confirmar-msg" style="color:var(--texto);font-size:.95rem;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost btn-full" onclick="Modal.cerrar('modal-confirmar')">Cancelar</button>
            <button class="btn btn-peligro btn-full" id="modal-confirmar-btn">Confirmar</button>
        </div>
    </div>
</div>
