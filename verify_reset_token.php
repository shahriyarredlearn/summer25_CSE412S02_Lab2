<?php
// verify_reset_token.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Get JSON input
    $data = read_json();
    
    // Check if token is provided
    if (!isset($data['token']) || empty($data['token'])) {
        json_response(false, ['error' => 'Token is required'], 400);
    }
    
    $token = trim($data['token']);
    
    // Get database connection
    $pdo = db();

    // Check if token exists and is not expired
    $stmt = $pdo->prepare("SELECT email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        json_response(false, ['error' => 'Invalid or expired token. Please request a new password reset link.'], 400);
    }
    
    json_response(true, ['message' => 'Token is valid']);
    
} catch (PDOException $e) {
    log_error("Database error in verify reset token: " . $e->getMessage());
    json_response(false, ['error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    log_error("Error in verify reset token: " . $e->getMessage());
    json_response(false, ['error' => 'An error occurred'], 500);
}