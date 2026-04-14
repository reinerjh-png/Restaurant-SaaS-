<?php
/**
 * index.php — Página de login principal
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();

// Si ya está logueado, redirigir a su panel
if (isset($_SESSION['usuario_id'])) {
    $destinos = [
        'superadmin' => '/sistema_restaurante/roles/superadmin/dashboard.php',
        'admin'      => '/sistema_restaurante/roles/admin/dashboard.php',
        'atencion'   => '/sistema_restaurante/roles/atencion/dashboard.php',
        'cocina'     => '/sistema_restaurante/roles/cocina/dashboard.php',
    ];
    $destino = $destinos[$_SESSION['rol']] ?? '/sistema_restaurante/index.php';
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
    <title>Login — RestSaaS | R.DEV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sistema_restaurante/assets/css/main.css">
</head>
<body>

<div class="login-page">
    <div class="login-box">
        <!-- Logo -->
        <div class="login-logo">
            <div class="brand" style="margin-bottom: 5px;">
                <img src="/sistema_restaurante/assets/logo.png" alt="Sabor Perú Logo" style="max-height: 80px; width: auto; object-fit: contain;">
            </div>
            <div class="subtitle">Sistema de Gestión para Restaurantes · <strong>R.DEV</strong></div>
        </div>

        <h2 class="login-title">Bienvenido de vuelta</h2>
        <p class="login-subtitle">Ingresa tus credenciales para continuar</p>

        <!-- Alerta de error -->
        <?php if ($error): ?>
        <div style="background:#FDEDEC;border-left:4px solid var(--peligro);border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:.88rem;color:var(--rojo-dark);font-weight:500;">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form action="/sistema_restaurante/auth/login.php" method="POST" id="form-login">
            <div class="form-group">
                <label class="form-label" for="email">👤 Usuario</label>
                <input
                    type="text"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="usuario"
                    required
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
            </div>
            <div class="form-group">
                <label class="form-label" for="password">🔒 Contraseña</label>
                <div style="position:relative;">
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
                    <button type="button" id="toggle-pass"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--gris);"
                        title="Mostrar/ocultar contraseña">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primario btn-full btn-lg mt-16" id="btn-login">
                🚀 Ingresar al sistema
            </button>
        </form>



        <div class="login-footer">
            <p>Desarrollado por <strong>Reiner Jiménez</strong> · R.DEV</p>
            <p style="margin-top:2px;">© <?= date('Y') ?> · Todos los derechos reservados</p>
        </div>
    </div>
</div>

<script>
// Toggle visibilidad contraseña
document.getElementById('toggle-pass').addEventListener('click', function() {
    const inp = document.getElementById('password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.textContent = inp.type === 'password' ? '👁️' : '🙈';
});



// Prevenir doble submit
document.getElementById('form-login').addEventListener('submit', function() {
    const btn = document.getElementById('btn-login');
    btn.disabled = true;
    btn.textContent = '⏳ Verificando...';
});
</script>
</body>
</html>
