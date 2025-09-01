<?php
// me.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    start_app_session();
    
    // Check if session is valid
    if (!is_session_valid()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session expired or invalid']);
        http_response_code(401);
        exit;
    }
    
    // Get current user data
    $user = get_current_user_data();
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'User not found']);
        http_response_code(404);
        exit;
    }
    
    // Update session activity
    update_session_activity();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'ok' => true,
        'user' => $user,
        'session_valid' => true,
        'last_activity' => $_SESSION['last_activity'] ?? null
    ]);
    exit;
} catch (Exception $e) {
    error_log("Me.php error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
    http_response_code(500);
    exit;
}
?>
