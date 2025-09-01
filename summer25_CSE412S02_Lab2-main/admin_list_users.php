<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

try {
    // Ensure user is logged in and is an admin
    require_admin();
    
    // Check if session is valid
    if (!is_session_valid()) {
        echo json_encode(['success' => false, 'error' => 'Session expired or invalid']);
        http_response_code(401);
        exit;
    }
    
    $pdo = db();
    
    // Get query parameters
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Build query
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND email LIKE ?";
        $params[] = "%$search%";
    }
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    
    // Get users with pagination
    $sql = "SELECT id, email, role, created_at FROM users $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get online status for each user
    $onlineThreshold = date('Y-m-d H:i:s', time() - (15 * 60)); // 15 minutes
    
    foreach ($users as &$user) {
        // Check if user is online
        $onlineStmt = $pdo->prepare('SELECT COUNT(*) FROM user_sessions WHERE user_email = ? AND last_activity > ?');
        $onlineStmt->execute([$user['email'], $onlineThreshold]);
        $user['is_online'] = $onlineStmt->fetchColumn() > 0;
        
        // Get file count and storage usage
        $fileStmt = $pdo->prepare('SELECT COUNT(*) as file_count, SUM(file_size) as total_size FROM user_files WHERE user_email = ? AND is_deleted = 0');
        $fileStmt->execute([$user['email']]);
        $fileInfo = $fileStmt->fetch();
        
        $user['file_count'] = (int)$fileInfo['file_count'];
        $user['total_storage'] = (int)$fileInfo['total_size'];
        $user['total_storage_formatted'] = formatFileSize($user['total_storage']);
    }
    
    // Update session activity
    update_session_activity();
    
    echo json_encode([
        'success' => true,
        'ok' => true,
        'users' => $users,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalUsers / $limit),
            'total_users' => $totalUsers,
            'users_per_page' => $limit
        ]
    ]);
    exit;
    
} catch (Exception $e) {
    error_log("Admin List Users Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve users']);
    http_response_code(500);
    exit;
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