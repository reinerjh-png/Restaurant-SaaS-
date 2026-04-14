<?php
/**
 * auth/logout.php — Cierre de sesión
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();

// Registrar logout en logs
if (isset($_SESSION['usuario_id'])) {
    require_once '../config/db.php';
    try {
        $db  = getDB();
        $log = $db->prepare("INSERT INTO logs_acceso (usuario_id, accion, ip) VALUES (?, 'logout', ?)");
        $log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (Exception $e) { /* silenciar */ }
}

session_unset();
session_destroy();
header('Location: /sistema_restaurante/index.php');
exit;
