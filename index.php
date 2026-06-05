<?php
/**
 * index.php — Página de login principal
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once 'config/db.php';

// Si ya está logueado, redirigir a su panel
if (isset($_SESSION['usuario_id'])) {
    $destinos = [
        'superadmin' => BASE_URL . '/roles/superadmin/dashboard.php',
        'admin'      => BASE_URL . '/roles/admin/dashboard.php',
        'atencion'   => BASE_URL . '/roles/atencion/dashboard.php',
        'cocina'     => BASE_URL . '/roles/cocina/dashboard.php',
    ];
    $destino = $destinos[$_SESSION['rol']] ?? BASE_URL . '/index.php';
    header("Location: $destino");
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema SaaS de gestión para restaurantes — Ingresa a tu cuenta">
    <title>Iniciar sesión — RestSaaS | R.DEV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css?v=<?= time() ?>">
</head>
<body>

<div class="split-layout">
    <!-- Mitad Izquierda: Branding & Mensaje -->
    <div class="split-left">
        <div class="split-left-content">
            <img src="<?= BASE_URL ?>/assets/logo.png" alt="Sabor Perú Logo" class="split-logo">
            <h1 class="split-headline">Gestión Inteligente y Moderna para tu Restaurante</h1>
            <p class="split-description">Optimiza tus operaciones, centraliza tus pedidos y acelera tus cobros en una plataforma diseñada para equipos de alto rendimiento.</p>
        </div>
    </div>

    <!-- Mitad Derecha: Formulario de Login -->
    <div class="split-right">
        <div class="login-box-new">
            
            <h2 class="login-title">Bienvenido de vuelta</h2>
            <p class="login-subtitle">Ingresa tus credenciales para continuar</p>

            <!-- Alerta de error -->
            <?php if ($error): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-xmark"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form action="<?= BASE_URL ?>/auth/login.php" method="POST" id="form-login">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="fa-solid fa-user" style="margin-right:4px;color:var(--text-muted);font-size:.75rem;"></i>
                        Usuario
                    </label>
                    <div class="input-icon-wrap">
                        <span class="input-icon-left"><i class="fa-solid fa-at"></i></span>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Tu usuario o correo"
                            required
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fa-solid fa-lock" style="margin-right:4px;color:var(--text-muted);font-size:.75rem;"></i>
                        Contraseña
                    </label>
                    <div class="input-icon-wrap">
                        <span class="input-icon-left"><i class="fa-solid fa-lock"></i></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                            style="padding-right:44px;"
                        >
                        <button type="button" class="input-icon-right" id="toggle-pass"
                                title="Mostrar/ocultar contraseña" aria-label="Mostrar contraseña">
                            <i class="fa-solid fa-eye" id="toggle-pass-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primario btn-full btn-lg mt-16" id="btn-login"
                        style="font-size:1rem; margin-top:24px;">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Ingresar al sistema
                </button>
            </form>

            <div class="login-footer">
                <p>Sistema desarrollado por <a href="https://www.linkedin.com/in/reiner-jairo-jim%C3%A9nez-huaman-9234a9388/" target="_blank" rel="noopener noreferrer" className="text-white hover:underline"><strong>Reiner Jimenez</strong></a> · R.DEV</p>
                <p style="margin-top:4px;">© <?= date('Y') ?> · Todos los derechos reservados</p>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle visibilidad contraseña
document.getElementById('toggle-pass').addEventListener('click', function() {
    const inp  = document.getElementById('password');
    const icon = document.getElementById('toggle-pass-icon');
    const show = inp.type === 'password';
    inp.type   = show ? 'text' : 'password';
    icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

// Prevenir doble submit
document.getElementById('form-login').addEventListener('submit', function() {
    const btn = document.getElementById('btn-login');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
});
</script>
</body>
</html>
