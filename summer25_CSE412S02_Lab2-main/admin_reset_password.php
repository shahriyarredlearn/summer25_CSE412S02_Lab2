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
    $userId = isset($data['userId']) ? (int)$data['userId'] : 0;
    $password = $data['password'] ?? '';

    if ($userId <= 0) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID'
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
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }

    // Hash new password and update
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    
    if (!$stmt->execute([$hash, $userId])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to reset password'
        ]);
        exit;
    }
    
    // Update session activity
    update_session_activity();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'ok' => true,
        'message' => 'Password reset successfully',
        'user_email' => $user['email']
    ]);

} catch (Exception $e) {
    error_log("Admin Reset Password Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to reset password'
    ]);
    exit;
}
?>