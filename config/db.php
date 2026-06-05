<?php
// ============================================
// Conexión a la Base de Datos — PDO
// Sistema SaaS Restaurante | R.DEV
// ============================================

/**
 * Función nativa para cargar variables de entorno desde un archivo .env
 * Compatible con hostings compartidos (no requiere Composer).
 */
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Quitar comillas si las tiene
            $value = trim($value, '"\'');
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Cargar variables de entorno
loadEnv(__DIR__ . '/../.env');

// Detectar automáticamente la URL base (configurable vía .env, o auto-detectar localhost vs producción)
if (!empty($_ENV['BASE_URL'])) {
    define('BASE_URL', rtrim($_ENV['BASE_URL'], '/'));
} else {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        define('BASE_URL', '/system-restaurant');
    } else {
        define('BASE_URL', '');
    }
}

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'restaurante_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// ── Facturación — API de consulta DNI/RUC ─────────────────────
// Obtén tu token en: https://api.apis.net.pe
define('APIS_NET_TOKEN', $_ENV['APIS_NET_TOKEN'] ?? '');

date_default_timezone_set('America/Lima');

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
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-05:00'",
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
        $nuevoRestId = (int)$_GET['set_rest_id'];
        try {
            $db = getDB();
            $st = $db->prepare("SELECT id FROM restaurantes WHERE id = ? AND activo = 1");
            $st->execute([$nuevoRestId]);
            if ($st->fetch()) {
                $_SESSION['restaurante_id'] = $nuevoRestId;
            }
        } catch (Exception $e) {
            // Ignore if db fails, just don't change the context
        }
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


/**
 * CSRF Protection
 */
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function verifyCsrfProtection(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $jsonPayload = json_decode(file_get_contents('php://input'), true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? ($jsonPayload['csrf_token'] ?? '');
        
        if (!validateCsrfToken($token)) {
            $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                      (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                      isset($_SERVER['HTTP_X_CSRF_TOKEN']);
                      
            if ($esAjax) {
                jsonResponse(false, null, 'Error de seguridad: Token CSRF inválido.');
            } else {
                http_response_code(403);
                die('Error de seguridad: Token CSRF inválido.');
            }
        }
    }
}

getCsrfToken();
verifyCsrfProtection();
