<?php
// api/list_files.php
require_once __DIR__ . '/../config/app.php';

// Ensure user is logged in
$email = require_login();

try {
    $pdo = db();

    // Fetch all files for the logged-in user
    $stmt = $pdo->prepare(
        'SELECT id, original_name, file_type, file_size, upload_date 
         FROM user_files 
         WHERE user_email = ? 
         ORDER BY upload_date DESC'
    );
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll();

    // Format file sizes in KB/MB for easier frontend display
    $files = array_map(function ($file) {
        $size = (int)$file['file_size'];
        if ($size >= 1024 * 1024) {
            $file['file_size'] = round($size / (1024 * 1024), 2) . ' MB';
        } elseif ($size >= 1024) {
            $file['file_size'] = round($size / 1024, 2) . ' KB';
        } else {
            $file['file_size'] = $size . ' B';
        }
        $file['upload_date'] = date('Y-m-d H:i:s', strtotime($file['upload_date']));
        return $file;
    }, $rows);

    json_response(true, ['files' => $files]);

} catch (Exception $e) {
    log_error("List Files Error for user {$email}: " . $e->getMessage());
    json_response(false, ['error' => 'Internal server error'], 500);
}
