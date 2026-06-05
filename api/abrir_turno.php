<?php
/**
 * api/abrir_turno.php
 * Abre un nuevo turno de caja para el restaurante.
 * Exclusivo para administradores y superadministradores.
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.');
}

$restauranteId = $_SESSION['restaurante_id'];
$usuarioId = $_SESSION['usuario_id'];
$db = getDB();

try {
    $db->beginTransaction();

    // Verificar si ya hay un turno abierto — FOR UPDATE garantiza atomicidad
    // ante dos peticiones simultáneas (la segunda bloqueará hasta que la primera
    // haga commit, y entonces verá el turno ya existente y abortará).
    $stVerificar = $db->prepare("
        SELECT id FROM turnos
        WHERE restaurante_id = ? AND fin IS NULL
        FOR UPDATE
    ");
    $stVerificar->execute([$restauranteId]);

    if ($stVerificar->fetch()) {
        $db->rollBack();
        jsonResponse(false, null, 'Ya existe una caja abierta en este momento.');
    }

    // Insertar nuevo turno
    $stInsert = $db->prepare("
        INSERT INTO turnos (restaurante_id, usuario_id, inicio)
        VALUES (?, ?, NOW())
    ");
    $stInsert->execute([$restauranteId, $usuarioId]);

    $db->commit();
    jsonResponse(true, null, 'Caja abierta correctamente.');

} catch (PDOException $e) {
    $db->rollBack();
    error_log('Error al abrir la caja: ' . $e->getMessage());
    jsonResponse(false, null, 'Ocurrió un error interno en el servidor.');
}
