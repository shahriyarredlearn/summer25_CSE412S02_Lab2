<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/config/database.php';
    start_app_session();
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'error' => 'Please login first']);
        exit;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['file'];
    $userEmail = $_SESSION['user_email'] ?? 'unknown';
    
    // Validate file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'File too large (max 10MB)']);
        exit;
    }
    
    // Create uploads directory
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $storedName = uniqid('file_', true) . '.' . $extension;
    $filePath = $uploadDir . $storedName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['ok' => false, 'error' => 'Failed to save file']);
        exit;
    }
    
    // Save to database
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO user_files (user_email, stored_name, original_name, file_type, file_size, is_deleted) 
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    
    $success = $stmt->execute([
        $userEmail,
        $storedName,
        $file['name'],
        $file['type'],
        $file['size']
    ]);
    
    if ($success) {
        echo json_encode([
            'ok' => true, 
            'message' => 'File uploaded successfully',
            'file' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type']
            ]
        ]);
    } else {
        // Delete the uploaded file if database insert failed
        unlink($filePath);
        echo json_encode(['ok' => false, 'error' => 'Failed to save file info to database']);
    }
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Upload failed: ' . $e->getMessage()]);
}
?>
