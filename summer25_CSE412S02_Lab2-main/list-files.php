<?php
// Start output buffering to prevent any accidental output
ob_start();

error_reporting(0);
ini_set('display_errors', 0); // Don't display errors in output

// Set JSON header first
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/database.php';
    
    // Start session and check authentication
    start_app_session();
    
    if (!isset($_SESSION['user_id'])) {
        // Clean buffer and send error
        ob_clean();
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    $userEmail = $_SESSION['user_email'] ?? '';
    $userRole = $_SESSION['user_role'] ?? 'user';
    
    $pdo = db();
    
    // Admin can see all files, users only see their own
    if ($userRole === 'admin') {
        $stmt = $pdo->prepare("
            SELECT uf.*, u.email as owner_email 
            FROM user_files uf 
            LEFT JOIN users u ON uf.user_email = u.email 
            WHERE uf.is_deleted = 0 
            ORDER BY uf.upload_date DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM user_files 
            WHERE user_email = ? AND is_deleted = 0 
            ORDER BY upload_date DESC
        ");
        $stmt->execute([$userEmail]);
    }
    
    $files = $stmt->fetchAll();
    
    // Clean buffer and send success response
    ob_clean();
    echo json_encode([
        'ok' => true,
        'success' => true,
        'files' => $files,
        'count' => count($files),
        'user_role' => $userRole
    ]);
    
} catch (Exception $e) {
    // Clean buffer and send error
    ob_clean();
    error_log("List files error: " . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'success' => false,
        'error' => 'An error occurred while listing files: ' . $e->getMessage()
    ]);
}
exit;
?>