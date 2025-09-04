<?php
// Include database configuration and auth functions
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Start session
    start_app_session();
    
    // Parse JSON input
    $data = read_json();
    
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = $data['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        http_response_code(400);
        exit;
    }
    
    // Get database connection
    $pdo = db();
    
    // Check user credentials
    $stmt = $pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        http_response_code(401);
        exit;
    }
    
    // User authenticated successfully - create user session
    create_user_session($user['email'], $user['role']);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'ok' => true,
        'message' => 'Login successful',
        'email' => $user['email'],
        'role' => $user['role'],
        'user_id' => $user['id']
    ]);
    exit;
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'An error occurred during login']);
    http_response_code(500);
    exit;
}
?>