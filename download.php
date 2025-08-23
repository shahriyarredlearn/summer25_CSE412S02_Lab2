<?php
// api/download.php
require_once __DIR__ . '/../config/database.php';
$email = require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT stored_name, original_name, file_type FROM user_files WHERE id = ? AND user_email = ?');
$stmt->execute([$id, $email]);
$f = $stmt->fetch();

if (!$f) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$path = realpath(__DIR__ . '/../uploads/' . $f['stored_name']);
if (!$path || !is_file($path)) {
    http_response_code(404);
    echo 'File missing';
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $f['file_type']);
header('Content-Disposition: attachment; filename="' . basename($f['original_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
