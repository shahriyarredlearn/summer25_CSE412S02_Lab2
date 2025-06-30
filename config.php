<?php
/**
 * Database Configuration File
 * This file contains database connection settings for the File Repository application
 * Currently using localStorage in JavaScript, but this can be upgraded to use MySQL
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'file_repository');

// Create database connection
function getDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// SQL to create necessary tables
$createTablesSQL = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
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

// Function to initialize database tables
function initializeDatabase() {
    global $createTablesSQL;
    
    try {
        $pdo = getDatabaseConnection();
        
        foreach ($createTablesSQL as $sql) {
            $pdo->exec($sql);
        }
        
        echo "Database tables created successfully!\n";
    } catch (PDOException $e) {
        echo "Error creating tables: " . $e->getMessage() . "\n";
    }
}

// Uncomment the line below to initialize database when this file is run directly
// initializeDatabase();

?>