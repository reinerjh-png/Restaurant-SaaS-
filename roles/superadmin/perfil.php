<?php
/**
 * roles/superadmin/perfil.php — Editar perfil del Superadmin
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['superadmin']);

$db = getDB();
$usuarioId = $_SESSION['usuario_id'];

$saved = false;
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password_confirm'] ?? '';

    if (empty($nombre) || empty($email)) {
        $error = 'El nombre y el correo/usuario son obligatorios.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!empty($pass) && strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verificar si el email ya existe para otro usuario
        $stVerificar = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stVerificar->execute([$email, $usuarioId]);
        if ($stVerificar->fetch()) {
            $error = 'El nombre de usuario o correo electrónico ya está en uso.';
        } else {
            if (!empty($pass)) {
                // Actualizar con contraseña
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $st = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ? WHERE id = ?");
                $st->execute([$nombre, $email, $hash, $usuarioId]);
            } else {
                // Actualizar sin tocar la contraseña
                $st = $db->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
                $st->execute([$nombre, $email, $usuarioId]);
            }

            // Actualizar variables de sesión
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email']  = $email;

            $saved = true;
        }
    }
}

// Obtener datos actuales
$st = $db->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
$st->execute([$usuarioId]);
$usuario = $st->fetch();

$pageTitle  = 'Mi Perfil';
$activeMenu = 'perfil';
require_once '../../includes/header.php';
?>

<div class="layout-admin">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon"><i class="fa-solid fa-globe"></i></span> Dashboard Global</a></li>
            <li><a href="restaurantes.php"><span class="menu-icon"><i class="fa-solid fa-store"></i></span> Restaurantes</a></li>
            <li><a href="perfil.php" class="active"><span class="menu-icon"><i class="fa-solid fa-user-shield"></i></span> Mi Perfil</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content" style="max-width:600px;">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1><i class="fa-solid fa-user-shield" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Mi Perfil</h1>
                    <p>Actualiza tus credenciales de acceso</p>
                </div>
            </div>

            <?php if ($saved): ?>
            <div class="alert-exito" style="background:var(--success-bg,rgba(34,197,94,.12));border:1px solid var(--success);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:20px;color:var(--success);font-weight:600;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-circle-check"></i> Perfil actualizado correctamente.
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert-error" style="background:rgba(239,68,68,.1);border:1px solid var(--danger);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:20px;color:var(--danger);font-weight:600;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST">
                    <div class="form-group mb-16">
                        <label class="form-label">Nombre completo <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="form-group mb-20">
                        <label class="form-label">Usuario o Correo <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                        <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">Este es el usuario con el que inicias sesión.</div>
                    </div>

                    <hr style="border:0;border-top:1px solid var(--border);margin:20px 0;">

                    <div style="font-size:.9rem;font-weight:600;color:var(--text-primary);margin-bottom:12px;"><i class="fa-solid fa-lock"></i> Cambiar Contraseña</div>
                    <div style="font-size:.8rem;color:var(--text-secondary);margin-bottom:16px;">Déjalo en blanco si no deseas cambiar tu contraseña actual.</div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="mb-20">
                        <div class="form-group">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmar Contraseña</label>
                            <input type="password" name="password_confirm" class="form-control" placeholder="Repite la contraseña" autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primario btn-lg" style="width:100%;">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
