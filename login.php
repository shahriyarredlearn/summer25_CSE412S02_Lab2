<?php
// api/login.php
require_once __DIR__ . '/../config/database.php';
start_app_session();

$data = read_json();
$email = isset($data['email']) ? trim($data['email']) : '';
$pass  = $data['password'] ?? '';

$pdo = db();
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row || !password_verify($pass, $row['password_hash'])) {
    json_error('Invalid email or password', 401);
}

$_SESSION['email'] = $email;
json_ok(['message' => 'Login successful', 'email' => $email]);
