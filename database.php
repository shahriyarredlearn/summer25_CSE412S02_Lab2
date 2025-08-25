<?php
declare(strict_types=1);

/**
 * =============================
 *  Database & App Configuration
 * =============================
 * Uses environment variables for DB config (no hardcoding).
 * Create a `.env` file in your project root with:
 *
 * DB_HOST=localhost
 * DB_NAME=file_repository
 * DB_USER=root
 * DB_PASS=
 */

/** @var array $_ENV */
$_ENV = $_ENV ?? [];

// Composer autoload (optional)
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}
log_message('info','autoloaded');
/**
 * =============================
 *  Database Connection (PDO)
 * =============================
 */

/** @return PDO */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Use temporary variables to avoid VS Code warnings
    $env = $_ENV;
    $host = $env['DB_HOST'] ?? 'localhost';
    $name = $env['DB_NAME'] ?? 'file_repository';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        log_error("DB Connection Failed: " . $e->getMessage());
        json_response(false, ['error' => 'Database connection failed'], 500);
    }
}

/**
 * =============================
 *  Session Handling
 * =============================
 */

/** Start application session */
function start_app_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * =============================
 *  Authentication Helpers
 * =============================
 */

/** Require user login and return email */
function require_login(): string {
    start_app_session();
    if (!isset($_SESSION['email'])) {
        json_response(false, ['error' => 'Unauthorized'], 401);
    }
    return (string)$_SESSION['email'];
}

/** Require specific role (if used) */
function require_role(string $role): string {
    $email = require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        json_response(false, ['error' => 'Forbidden'], 403);
    }
    return $email;
}

/**
 * =============================
 *  CSRF Protection
 * =============================
 */

/** Generate CSRF token */
function generate_csrf_token(): string {
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verify CSRF token */
function verify_csrf_token(string $token): bool {
    start_app_session();
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * =============================
 *  JSON Helpers
 * =============================
 */

/**
 * Send JSON response
 * @param bool $ok
 * @param array $data
 * @param int $status
 */
function json_response(bool $ok, array $data = [], int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode(['ok' => $ok] + $data);
    exit;
}

/** Read JSON from request body */
function read_json(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * =============================
 *  Logging
 * =============================
 */

/** Log errors to file */
function log_error(string $message): void {
    $logFile = __DIR__ . '/../logs/app.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0775, true);
    }
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL, 3, $logFile);
}
