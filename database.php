<?php
// config/database.php
declare(strict_types=1);

// Adjust these for your environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'file_repository');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');

// PDO connection (MySQL)
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode([ 'ok' => false, 'error' => 'DB connection failed', 'detail' => $e->getMessage() ]));
    }
}

// Common: start session (same settings everywhere)
function start_app_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Strict session cookie
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

// Helper: require login
function require_login(): string {
    start_app_session();
    if (!isset($_SESSION['email'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }
    return (string)$_SESSION['email'];
}

// JSON helpers
function read_json(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_ok(array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true] + $data);
    exit;
}

function json_error(string $msg, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
