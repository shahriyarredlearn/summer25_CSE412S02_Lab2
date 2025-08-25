<?php
// api/register.php
require_once __DIR__ . '/../config/app.php';
start_app_session();

try {
    $data = read_json();
    $email = isset($data['email']) ? trim($data['email']) : '';
    $pass  = $data['password'] ?? '';

    // ✅ Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, ['error' => 'Invalid email'], 400);
    }
    if (strlen($pass) < 6) {
        json_response(false, ['error' => 'Password must be at least 6 characters'], 400);
    }

    // Optional: enforce stronger password
    if (!preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
        json_response(false, ['error' => 'Password must contain at least one uppercase letter and one number'], 400);
    }

    $pdo = db();

    // Check if email already exists
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(false, ['error' => 'Email already registered'], 409);
    }

    // Hash password and insert
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);

    // ✅ Log user in after registration
    $_SESSION['email'] = $email;

    json_response(true, ['message' => 'Registered successfully', 'email' => $email]);

} catch (Exception $e) {
    log_error("Register Error for {$email}: " . $e->getMessage());
    json_response(false, ['error' => 'Internal server error'], 500);
}
