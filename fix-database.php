<?php
// filepath: c:\xampp\htdocs\summer25_CSE412S02_Lab2-main\db-test.php
// Basic database connection test

// No output before header
header('Content-Type: text/plain');

try {
    $host = 'db';
    $dbname = 'filerepository';
    $user = 'dbuser';
    $pass = 'dbpassword';
    
    echo "Attempting to connect to MySQL...\n";
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected successfully!\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found: " . implode(", ", $tables ?: ['none']) . "\n";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>