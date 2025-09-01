<?php
// reset_password.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Get JSON input
    $data = read_json();
    
    // Check if token and password are provided
    if (!isset($data['token']) || empty($data['token'])) {
        json_response(false, ['error' => 'Token is required'], 400);
    }
    
    if (!isset($data['password']) || empty($data['password'])) {
        json_response(false, ['error' => 'Password is required'], 400);
    }
    
    $token = trim($data['token']);
    $password = trim($data['password']);
    
    // Validate password
    if (strlen($password) < 6) {
        json_response(false, ['error' => 'Password must be at least 6 characters long'], 400);
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        json_response(false, ['error' => 'Password must contain at least one uppercase letter'], 400);
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        json_response(false, ['error' => 'Password must contain at least one number'], 400);
    }
    
    // Get database connection
    $pdo = db();

    // Start transaction
    $pdo->beginTransaction();
    
    // Check if token exists and is not expired
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $pdo->rollBack();
        json_response(false, ['error' => 'Invalid or expired token. Please request a new password reset link.'], 400);
    }
    
    // Hash the new password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user's password and clear reset token
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
    $stmt->execute([$password_hash, $user['id']]);
    
    // Commit transaction
    $pdo->commit();
    
    // Log the successful password reset
    log_message("Password reset successful for user: {$user['email']}");
    
    json_response(true, ['message' => 'Your password has been reset successfully. You can now log in with your new password.']);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error("Database error in password reset: " . $e->getMessage());
    json_response(false, ['error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error("Error in password reset: " . $e->getMessage());
    json_response(false, ['error' => 'An error occurred'], 500);
}