<?php
/**
 * File Repository - Database Configuration
 * ----------------------------------------
 * This script sets up the PDO database connection and initializes the
 * necessary tables: `users` and `user_files`.
 */

// --- Database Credentials ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'file_repository');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');

// --- PDO Connection Function ---
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
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // ✅ User Files Table
    "CREATE TABLE IF NOT EXISTS user_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100),
        file_size INT,
        file_path VARCHAR(500),
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
        echo "✅ Tables created successfully.\n";
    } catch (PDOException $e) {
        echo "❌ Failed to create tables: " . $e->getMessage() . "\n";
    }
}

// --- Uncomment below to auto-initialize tables on script run ---
// initializeDatabase();
?>
