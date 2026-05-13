<?php
/**
 * api/consultar_documento.php
 * Consulta DNI o RUC usando la API de apis.net.pe (solo desde PHP/cURL).
 * El token NUNCA llega al frontend.
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Método no permitido');
}

$numero = preg_replace('/\D/', '', trim($_GET['numero'] ?? ''));
$tipo   = strtolower(trim($_GET['tipo'] ?? ''));

// Validación básica
if ($tipo === 'dni' && strlen($numero) !== 8) {
    jsonResponse(false, null, 'El DNI debe tener exactamente 8 dígitos numéricos.');
}
if ($tipo === 'ruc' && strlen($numero) !== 11) {
    jsonResponse(false, null, 'El RUC debe tener exactamente 11 dígitos numéricos.');
}
if (!in_array($tipo, ['dni', 'ruc'])) {
    jsonResponse(false, null, 'Tipo de documento inválido (dni o ruc).');
}

// Token desde constante de configuración
$token = defined('APIS_NET_TOKEN') ? APIS_NET_TOKEN : '';

// Si no hay token configurado aún, retornar indicación de ingreso manual
if (empty($token)) {
    jsonResponse(false, null, 'Token de API no configurado. Ingresa los datos manualmente.');
}

// Construir URL de la API
$baseUrl = 'https://api.apis.net.pe/v1/';
$url     = $baseUrl . ($tipo === 'dni' ? 'dni' : 'ruc') . '?numero=' . urlencode($numero);

// Ejecutar cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$body    = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error   = curl_error($ch);
curl_close($ch);

// Error de red / timeout
if ($error) {
    jsonResponse(false, null, 'No se pudo conectar a la API de consulta. Ingresa los datos manualmente.');
}

$data = json_decode($body, true);

if ($httpCode === 200 && $data) {
    if ($tipo === 'dni') {
        $nombre = trim($data['nombre'] ?? '');
        if (empty($nombre)) {
            $nombre = trim(
                ($data['nombres'] ?? '') . ' ' .
                ($data['apellidoPaterno'] ?? '') . ' ' .
                ($data['apellidoMaterno'] ?? '')
            );
        }
        jsonResponse(true, [
            'tipo'             => 'dni',
            'numero_documento' => $data['numeroDocumento'] ?? $numero,
            'nombre'           => $nombre,
        ]);
    } else {
        // RUC: puede venir como 'nombre' o 'razonSocial'
        $razonSocial = $data['razonSocial'] ?? $data['nombre'] ?? '';
        jsonResponse(true, [
            'tipo'             => 'ruc',
            'numero_documento' => $data['numeroDocumento'] ?? $numero,
            'nombre'           => $razonSocial,
            'direccion'        => (isset($data['direccion']) && $data['direccion'] !== '-') ? $data['direccion'] : '',
            'distrito'         => (isset($data['distrito']) && $data['distrito'] !== '-') ? $data['distrito'] : '',
            'provincia'        => (isset($data['provincia']) && $data['provincia'] !== '-') ? $data['provincia'] : '',
            'departamento'     => (isset($data['departamento']) && $data['departamento'] !== '-') ? $data['departamento'] : '',
            'estado'           => $data['estado'] ?? '',
            'condicion'        => $data['condicion'] ?? '',
        ]);
    }
} elseif ($httpCode === 404) {
    jsonResponse(false, null, 'Documento no encontrado en el registro. Verifica el número o ingresa los datos manualmente.');
} elseif ($httpCode === 422) {
    jsonResponse(false, null, 'Formato de documento inválido según la API.');
} else {
    jsonResponse(false, null, 'La API de consulta no está disponible en este momento. Ingresa los datos manualmente.');
}
