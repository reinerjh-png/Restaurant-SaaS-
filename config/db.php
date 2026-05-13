<?php
// ============================================
// Conexión a la Base de Datos — PDO
// Sistema SaaS Restaurante | R.DEV
// ============================================

// Detectar automáticamente la URL base (para localhost vs InfinityFree)
$host = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    define('BASE_URL', '/sistema_restaurante');
} else {
    define('BASE_URL', '');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurante_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── Facturación — API de consulta DNI/RUC ─────────────────────
// Obtén tu token en: https://api.apis.net.pe
define('APIS_NET_TOKEN', '');  // <-- Pega aquí tu token

/**
 * Retorna una instancia singleton de PDO.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // En producción no mostrar detalles del error
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
            exit;
        }
    }

    return $pdo;
}

/**
 * Respuesta estándar JSON para endpoints AJAX.
 */
function jsonResponse(bool $success, $data = null, string $message = ''): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'message' => $message,
    ]);
    exit;
}

/**
 * Verifica sesión activa y rol permitido.
 * Si no cumple, redirige al login o devuelve JSON si es petición AJAX.
 */
function requireRole(array $rolesPermitidos): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Permitir al superadmin cambiar el contexto del restaurante
    if (isset($_GET['set_rest_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin') {
        $_SESSION['restaurante_id'] = (int)$_GET['set_rest_id'];
    }

    // Si es superadmin intentando acceder a una ruta que requiere restaurante (roles/admin, roles/cocina, etc)
    // y no ha seleccionado uno, entonces redirigirlo al dash global.
    if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin') {
        if (empty($_SESSION['restaurante_id']) && strpos($_SERVER['REQUEST_URI'] ?? '', '/roles/superadmin/') === false) {
            header('Location: ' . BASE_URL . '/roles/superadmin/dashboard.php');
            exit;
        }
    }

    $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
        if ($esAjax) {
            jsonResponse(false, null, 'No autorizado');
        } else {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}
