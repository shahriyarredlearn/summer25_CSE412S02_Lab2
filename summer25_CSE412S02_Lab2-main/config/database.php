<?php
/**
 * File Repository - Database Configuration
 * ----------------------------------------
 * This script sets up the PDO database connection and initializes the
 * necessary tables: `users`, `user_files`, `user_sessions`, and `password_resets`.
 */

// --- Database Credentials ---
// For Docker development
define('DB_HOST', 'db');
define('DB_NAME', 'filerepository');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'rootpassword');  // Docker MySQL root password

// --- PDO Connection Function ---
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    return getDatabaseConnection();
}

function getDatabaseConnection(): PDO {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        return new PDO(
            $dsn,
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        die("❌ Database connection failed: " . $e->getMessage());
    }
}

// --- SQL Table Definitions ---
$createTablesSQL = [

    // ✅ Users Table
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        reset_token VARCHAR(64) NULL,
        reset_token_expiry DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // ✅ User Files Table
    "CREATE TABLE IF NOT EXISTS user_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        file_size BIGINT NOT NULL,
        is_deleted BOOLEAN DEFAULT 0,
        deleted_at TIMESTAMP NULL DEFAULT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_email),
        FOREIGN KEY (user_email) REFERENCES users(email) ON DELETE CASCADE
    )",
    
    // ✅ User Sessions Table for tracking online users
    "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        INDEX(user_email),
        INDEX(session_id),
        INDEX(last_activity),
        FOREIGN KEY (user_email) REFERENCES users(email) ON DELETE CASCADE
    )"
];

// --- Database Initialization Function ---
function initializeDatabase(): void {
    global $createTablesSQL;

    try {
        $pdo = getDatabaseConnection();
        foreach ($createTablesSQL as $sql) {
            $pdo->exec($sql);
        }
        // Check if admin user exists, if not create one
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            // Create default admin user (email: admin@example.com, password: Admin123)
            $adminEmail = 'admin@example.com';
            $adminPassword = password_hash('Admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$adminEmail, $adminPassword]);
        }
    } catch (PDOException $e) {
        // Silent error handling
    }
}

// --- Helper functions for API responses ---
function json_response(bool $success, array $data = [], int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'ok' => $success], $data));
    exit;
}

function json_ok(array $data = []): void {
    json_response(true, $data);
}

function json_error(string $message, int $status = 400): void {
    json_response(false, ['error' => $message], $status);
}

function read_json(): array {
    $json = file_get_contents('php://input');
    if (empty($json)) {
        return [];
    }
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_error('Invalid JSON input');
    }
    return $data;
}

function log_error(string $message): void {
    // Silent error logging in production
}

function log_message(string $level, string $message): void {
    // Silent logging in production
}

function start_app_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Disable secure cookies for HTTP environments
        ini_set('session.cookie_secure', 0);
        // Set session save path to a writable directory
        ini_set('session.save_path', '/tmp');
        session_start();
    }
}

// --- Initialize tables if this file is accessed directly ---
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    initializeDatabase();
}
?>