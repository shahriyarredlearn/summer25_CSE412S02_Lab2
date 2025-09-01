<?php
// request_password_reset.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Get JSON input
    $data = read_json();
    
    // Check if email is provided
    if (!isset($data['email']) || empty($data['email'])) {
        json_response(false, ['error' => 'Email is required'], 400);
    }
    
    $email = trim($data['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, ['error' => 'Invalid email format'], 400);
    }
    
    // Get database connection
    $pdo = db();

    // Check if email exists in database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Set token expiration (1 hour from now)
    $expires = date('Y-m-d H:i:s', time() + 3600);
    
    if ($user) {
        // Update user record with reset token
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);
        
        // In a real application, you would send an email with the reset link
        // For this demo, we'll just log the reset link
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.html?token=" . $token;
        log_message("Password reset link for {$email}: {$resetLink}");
    }
    
    // Don't reveal whether the email exists for security reasons
    json_response(true, [
        'message' => 'If your email is registered, you will receive a password reset link shortly.',
        'debug_link' => isset($resetLink) ? $resetLink : null // Only for demonstration, remove in production
    ]);
    
} catch (PDOException $e) {
    log_error("Database error in password reset: " . $e->getMessage());
    json_response(false, ['error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    log_error("Error in password reset: " . $e->getMessage());
    json_response(false, ['error' => 'An error occurred'], 500);
}