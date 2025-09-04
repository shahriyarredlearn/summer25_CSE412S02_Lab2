<?php
/**
 * Database Initialization Script
 * -----------------------------
 * This script initializes the database for the file manager system.
 * It creates the necessary tables and sets up a default admin user.
 */

require_once __DIR__ . '/config/database.php';

echo "Starting database initialization...\n";

try {
    // Create database if it doesn't exist
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '" . DB_NAME . "' created or already exists.\n";
    
    // Initialize tables
    initializeDatabase();
    
    echo "\n✅ Database initialization completed successfully!\n";
    echo "\nDefault admin credentials:\n";
    echo "Email: admin@example.com\n";
    echo "Password: Admin123\n";
    echo "\nPlease change these credentials after first login.\n";
    
} catch (PDOException $e) {
    echo "❌ Database initialization failed: " . $e->getMessage() . "\n";
}
