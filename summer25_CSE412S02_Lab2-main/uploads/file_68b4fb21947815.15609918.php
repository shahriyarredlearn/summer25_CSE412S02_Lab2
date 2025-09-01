<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Ensure user is logged in and is an admin
    require_admin();
    
    // Check if session is valid
    if (!is_session_valid()) {
        json_error('Session expired or invalid', 401);
    }
    
    $data = read_json();
    $newEmail = isset($data['email']) ? trim($data['email']) : '';
    $password = $data['password'] ?? '';
    $role = isset($data['role']) && in_array($data['role'], ['user', 'admin']) ? $data['role'] : 'user';

    // Validate input
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email format', 400);
    }
    if (strlen($password) < 6) {
        json_error('Password must be at least 6 characters', 400);
    }

    $pdo = db();
    
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$newEmail]);
    if ($stmt->fetch()) {
        json_error('Email already registered', 409);
    }

    // Hash password and insert
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
    
    if (!$stmt->execute([$newEmail, $hash, $role])) {
        json_error('Failed to create user', 500);
    }

    $newUserId = $pdo->lastInsertId();
    
    // Update session activity
    update_session_activity();
    
    json_ok([
        'message' => 'User created successfully', 
        'id' => $newUserId,
        'email' => $newEmail,
        'role' => $role
    ]);

} catch (Exception $e) {
    error_log("Admin Create User Error: " . $e->getMessage());
    json_error('Failed to create user', 500);
}
?>