<?php
require_once __DIR__ . '/config/database.php';

try {
    // Connect to database
    $pdo = db();
    
    // Admin user details
    $email = 'admin@example.com';
    $password = 'admin123';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'admin';
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update existing user
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = ? WHERE email = ?");
        $result = $stmt->execute([$password_hash, $role, $email]);
        echo "Admin user updated successfully.\n";
    } else {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
        $result = $stmt->execute([$email, $password_hash, $role]);
        echo "Admin user created successfully.\n";
    }
    
    echo "Admin credentials:\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>