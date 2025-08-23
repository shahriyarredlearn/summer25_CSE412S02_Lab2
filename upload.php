<?php
// api/upload.php
require_once __DIR__ . '/../config/database.php';
$email = require_login();

// Ensure uploads dir
$uploadsDir = realpath(__DIR__ . '/../uploads');
if ($uploadsDir === false) {
    json_error('Uploads directory missing on server', 500);
}

if (!isset($_FILES['file'])) json_error('No file uploaded');

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) json_error('Upload error code: ' . $file['error'], 400);

// Validate type/size
$allowedExts = ['pdf','jpg','jpeg','png','gif','doc','docx','xls','xlsx','txt','zip','rar'];
$maxBytes = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxBytes) json_error('File too large (max 10MB)');

$orig = $file['name'];
$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) json_error('File type not allowed');

$mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?: 'application/octet-stream');

// Generate stored name
$rand = bin2hex(random_bytes(16));
$stored = $rand . '.' . $ext;
$dest = $uploadsDir . DIRECTORY_SEPARATOR . $stored;

// Move
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_error('Failed to save file on server', 500);
}

// Save DB row
$pdo = db();
$stmt = $pdo->prepare('INSERT INTO user_files (user_email, stored_name, original_name, file_type, file_size) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$email, $stored, $orig, $mime, (int)$file['size']]);

json_ok(['message' => 'Uploaded', 'id' => $pdo->lastInsertId()]);
