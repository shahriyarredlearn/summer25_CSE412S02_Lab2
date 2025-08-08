<?php
/**
 * Database Configuration for File Repository
 * ------------------------------------------
 * This script configures the database connection using PDO and initializes
 * required tables (`users`, `user_files`) if they don't already exist.
 * 
 * To auto-initialize the DB, uncomment the `initializeDatabase();` line at the bottom.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'file_repository');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');

/**
 * Establish a PDO connection to the database
 *
 * @return PDO
 * @throws PDOException
 */
function getDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO(
            $dsn,
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
        die("❌ Database connection failed: " . $e->getMessage());
    }
}

/**
 * SQL queries to create required tables
 */
$createTablesSQL = [
    // Users Table
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Files Table
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

/**
 * Initialize the database schema by creating necessary tables
 *
 * @return void
 */
function initializeDatabase() {
    global $createTablesSQL;

    try {
        $pdo = getDatabaseConnection();
        foreach ($createTablesSQL as $sql) {
            $pdo->exec($sql);
        }
        echo "✅ Database tables created successfully.\n";
    } catch (PDOException $e) {
        echo "❌ Error creating tables: " . $e->getMessage() . "\n";
    }
}

// Uncomment the line below if you want to auto-create tables when the script runs
// initializeDatabase();
?>
