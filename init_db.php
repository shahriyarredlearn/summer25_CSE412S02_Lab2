<?php
// config/init_db.php
require_once __DIR__ . '/app.php'; // uses db(), json_response(), log_error()

try {
    $pdo = db();

    // ✅ Users table
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL
    );

    // ✅ User files table
    $pdo->exec(<<<SQL
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
    SQL
    );

    // Optional: return JSON
    json_response(true, ['message' => 'Database tables ensured']);

} catch (Throwable $e) {
    log_error("DB Init Error: " . $e->getMessage());
    json_response(false, ['error' => 'Database initialization failed'], 500);
}
