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
    
    // Get query parameters
    $search = $_GET['search'] ?? '';
    $sortBy = $_GET['sort'] ?? 'upload_date';
    $sortOrder = $_GET['order'] ?? 'DESC';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Validate sort parameters
    $allowedSortFields = ['upload_date', 'file_size', 'original_name', 'file_type'];
    $allowedSortOrders = ['ASC', 'DESC'];
    
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'upload_date';
    }
    if (!in_array(strtoupper($sortOrder), $allowedSortOrders)) {
        $sortOrder = 'DESC';
    }
    
    $pdo = db();
    
    // Build query based on user role
    if ($isAdmin) {
        // Admin can see all files
        $whereClause = "WHERE is_deleted = 0";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (original_name LIKE ? OR user_email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
    } else {
        // Regular users can only see their own files
        $whereClause = "WHERE user_email = ? AND is_deleted = 0";
        $params = [$userEmail];
        
        if (!empty($search)) {
            $whereClause .= " AND original_name LIKE ?";
            $params[] = "%$search%";
        }
    }
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_files $whereClause");
    $countStmt->execute($params);
    $totalFiles = $countStmt->fetchColumn();
    
    // Get files with pagination
    $sql = "SELECT id, user_email, original_name, file_type, file_size, upload_date 
            FROM user_files 
            $whereClause 
            ORDER BY $sortBy $sortOrder 
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $files = $stmt->fetchAll();
    
    // Format file data
    $formattedFiles = [];
    foreach ($files as $file) {
        $formattedFiles[] = [
            'id' => $file['id'],
            'user_email' => $file['user_email'],
            'original_name' => $file['original_name'],
            'file_type' => $file['file_type'],
            'file_size' => $file['file_size'],
            'file_size_formatted' => formatFileSize($file['file_size']),
            'upload_date' => $file['upload_date'],
            'can_delete' => $isAdmin || $file['user_email'] === $userEmail
        ];
    }
    
    // Update session activity
    update_session_activity();
    
    json_ok([
        'files' => $formattedFiles,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalFiles / $limit),
            'total_files' => $totalFiles,
            'files_per_page' => $limit
        ],
        'search' => $search,
        'sort_by' => $sortBy,
        'sort_order' => $sortOrder
    ]);
    
} catch (Exception $e) {
    error_log("List files error: " . $e->getMessage());
    json_error('An error occurred while listing files', 500);
}

/**
 * Format file size in human readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>