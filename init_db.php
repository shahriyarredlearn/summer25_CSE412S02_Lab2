<?php
// config/init_db.php
require_once __DIR__ . '/database.php';

try {
    $pdo = db();

    // users
    $pdo->exec("""
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """);

    // user_files
    $pdo->exec("""
        CREATE TABLE IF NOT EXISTS user_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_email VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            file_size BIGINT NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    """);

    header('Content-Type: text/plain');
    echo "✅ Database tables ensured.\n";
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "❌ Failed: " . $e->getMessage() . "\n";
}
