<?php
// api/register.php
require_once __DIR__ . '/../config/database.php';
start_app_session();

$data = read_json();
$email = isset($data['email']) ? trim($data['email']) : '';
$pass  = $data['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_error('Invalid email');
if (strlen($pass) < 6) json_error('Password must be at least 6 characters');

$pdo = db();
$stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) json_error('Email already registered', 409);

$hash = password_hash($pass, PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)')->execute([$email, $hash]);

$_SESSION['email'] = $email;
json_ok(['message' => 'Registered successfully', 'email' => $email]);
