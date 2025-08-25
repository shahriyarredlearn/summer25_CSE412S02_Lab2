<?php
// api/download.php
require_once __DIR__ . '/../config/app.php';

// Ensure user is logged in
$email = require_login();

// Validate file ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_response(false, ['error' => 'Invalid file ID'], 400);
}

try {
    $pdo = db();

    // Fetch file info
    $stmt = $pdo->prepare(
        'SELECT stored_name, original_name, file_type 
         FROM user_files 
         WHERE id = ? AND user_email = ?'
    );
    $stmt->execute([$id, $email]);
    $f = $stmt->fetch();

    if (!$f) {
        json_response(false, ['error' => 'File not found'], 404);
    }

    $path = realpath(__DIR__ . '/../uploads/' . $f['stored_name']);
    if (!$path || !is_file($path)) {
        log_error("Download failed: file missing ({$f['stored_name']}) for user {$email}");
        json_response(false, ['error' => 'File missing'], 404);
    }

    // Send headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $f['file_type']);
    header('Content-Disposition: attachment; filename="' . basename($f['original_name']) . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Stream file to output
    readfile($path);
    exit;

} catch (Exception $e) {
    log_error("Download error (id={$id}, user={$email}): " . $e->getMessage());
    json_response(false, ['error' => 'Internal server error'], 500);
}
