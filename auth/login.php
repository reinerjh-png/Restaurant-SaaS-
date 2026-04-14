<?php
/**
 * auth/login.php — Procesador del formulario de login
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sistema_restaurante/index.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Por favor completa todos los campos.';
    header('Location: /sistema_restaurante/index.php');
    exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT id, restaurante_id, nombre, email, password, rol, activo
         FROM usuarios
         WHERE email = :email
         LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        $_SESSION['login_error'] = 'Credenciales incorrectas. Intenta de nuevo.';
        header('Location: /sistema_restaurante/index.php');
        exit;
    }

    if (!$usuario['activo']) {
        $_SESSION['login_error'] = 'Tu cuenta está desactivada. Contacta al administrador.';
        header('Location: /sistema_restaurante/index.php');
        exit;
    }

    // Registrar acceso en logs
    $log = $db->prepare("INSERT INTO logs_acceso (usuario_id, accion, ip) VALUES (?, 'login', ?)");
    $log->execute([$usuario['id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

    // Iniciar sesión
    session_regenerate_id(true);
    $_SESSION['usuario_id']     = $usuario['id'];
    $_SESSION['restaurante_id'] = $usuario['restaurante_id'];
    $_SESSION['nombre']         = $usuario['nombre'];
    $_SESSION['email']          = $usuario['email'];
    $_SESSION['rol']            = $usuario['rol'];

    // Redirigir según rol
    $destinos = [
        'superadmin' => '/sistema_restaurante/roles/superadmin/dashboard.php',
        'admin'      => '/sistema_restaurante/roles/admin/dashboard.php',
        'atencion'   => '/sistema_restaurante/roles/atencion/dashboard.php',
        'cocina'     => '/sistema_restaurante/roles/cocina/dashboard.php',
    ];
    header('Location: ' . ($destinos[$usuario['rol']] ?? '/sistema_restaurante/index.php'));
    exit;

} catch (PDOException $e) {
    $_SESSION['login_error'] = 'Error del servidor. Intenta más tarde.';
    header('Location: /sistema_restaurante/index.php');
    exit;
}
