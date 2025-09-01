<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // End user session and remove from tracking
    end_user_session();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'ok' => true,
        'message' => 'Logged out successfully'
    ]);
    exit;
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred during logout'
    ]);
    http_response_code(500);
    exit;
}
?>
