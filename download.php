<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Check if user is logged in
    if (!is_logged_in()) {
        json_error('Authentication required', 401);
    }
    
    // Check if session is valid
    if (!is_session_valid()) {
        json_error('Session expired or invalid', 401);
    }
    
    $userEmail = $_SESSION['user_email'];
    $isAdmin = is_admin();
    
    // Get file ID from request
    $fileId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($fileId <= 0) {
        json_error('Valid file ID is required', 400);
    }
    
    $pdo = db();
    
    // Get file information
    $stmt = $pdo->prepare('SELECT * FROM user_files WHERE id = ? AND is_deleted = 0');
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        json_error('File not found', 404);
    }
    
    // Check if user has permission to download this file
    if (!$isAdmin && $file['user_email'] !== $userEmail) {
        json_error('You do not have permission to download this file', 403);
    }
    
    // Check if file exists on disk
    $filePath = __DIR__ . '/uploads/' . $file['stored_name'];
    if (!file_exists($filePath)) {
        json_error('File not found on server', 404);
    }
    
    // Update session activity
    update_session_activity();
    
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . $file['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Output file content
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    json_error('An error occurred while downloading the file', 500);
}
?>