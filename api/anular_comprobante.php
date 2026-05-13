<?php
/**
 * api/anular_comprobante.php
 * Marca un comprobante como anulado (no lo elimina).
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$comprobanteId = (int)($input['comprobante_id'] ?? 0);
$motivo        = trim($input['motivo'] ?? '');
$restauranteId = $_SESSION['restaurante_id'];

if (!$comprobanteId) jsonResponse(false, null, 'ID de comprobante inválido.');
if (!$motivo)        jsonResponse(false, null, 'El motivo es requerido.');

$db = getDB();

// Verificar que pertenece al restaurante y no está ya anulado
$st = $db->prepare("SELECT id, anulado FROM comprobantes WHERE id = ? AND restaurante_id = ?");
$st->execute([$comprobanteId, $restauranteId]);
$comp = $st->fetch();

if (!$comp)              jsonResponse(false, null, 'Comprobante no encontrado.');
if ($comp['anulado'])    jsonResponse(false, null, 'El comprobante ya está anulado.');

$stUpd = $db->prepare("UPDATE comprobantes SET anulado = 1, motivo_anulacion = ? WHERE id = ?");
$stUpd->execute([substr($motivo, 0, 200), $comprobanteId]);

jsonResponse(true, null, 'Comprobante anulado correctamente.');
