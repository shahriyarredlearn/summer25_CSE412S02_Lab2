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
    $data = read_json();
    $fileId = isset($data['file_id']) ? (int)$data['file_id'] : 0;
    
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
    
    // Check if user has permission to delete this file
    if (!$isAdmin && $file['user_email'] !== $userEmail) {
        json_error('You do not have permission to delete this file', 403);
    }
    
    // Mark file as deleted (soft delete)
    $stmt = $pdo->prepare('UPDATE user_files SET is_deleted = 1, deleted_at = NOW() WHERE id = ?');
    if (!$stmt->execute([$fileId])) {
        json_error('Failed to delete file', 500);
    }
    
    // Update session activity
    update_session_activity();
    
    json_ok([
        'message' => 'File deleted successfully',
        'file_id' => $fileId
    ]);
    
} catch (Exception $e) {
    error_log("Delete file error: " . $e->getMessage());
    json_error('An error occurred while deleting the file', 500);
}
?>