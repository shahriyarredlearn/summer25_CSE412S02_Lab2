<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Ensure user is logged in and is an admin
    require_admin();
    
    // Check if session is valid
    if (!is_session_valid()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session expired or invalid'
        ]);
        exit;
    }
    
    $data = read_json();
    $newEmail = isset($data['email']) ? trim($data['email']) : '';
    $password = $data['password'] ?? '';
    $role = isset($data['role']) && in_array($data['role'], ['user', 'admin']) ? $data['role'] : 'user';

    // Validate input
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format'
        ]);
        exit;
    }
    if (strlen($password) < 6) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Password must be at least 6 characters'
        ]);
        exit;
    }

    $pdo = db();
    
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$newEmail]);
    if ($stmt->fetch()) {
        header('Content-Type: application/json');
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Email already registered'
        ]);
        exit;
    }

    // Hash password and insert
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
    
    if (!$stmt->execute([$newEmail, $hash, $role])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create user'
        ]);
        exit;
    }

    $newUserId = $pdo->lastInsertId();
    
    // Update session activity
    update_session_activity();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'ok' => true,
        'message' => 'User created successfully', 
        'id' => $newUserId,
        'email' => $newEmail,
        'role' => $role
    ]);

} catch (Exception $e) {
    error_log("Admin Create User Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create user'
    ]);
    exit;
}
?>