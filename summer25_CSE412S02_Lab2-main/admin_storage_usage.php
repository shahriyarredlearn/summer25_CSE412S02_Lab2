<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Ensure user is logged in and is an admin
    require_admin();
    
    // Check if session is valid
    if (!is_session_valid()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session expired or invalid'
        ]);
        exit;
    }
    
    $pdo = db();
    
    // Get query parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $sortBy = $_GET['sort'] ?? 'total_bytes';
    $sortOrder = $_GET['order'] ?? 'DESC';
    
    // Validate sort parameters
    $allowedSortFields = ['total_bytes', 'file_count', 'last_upload', 'user_email'];
    $allowedSortOrders = ['ASC', 'DESC'];
    
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'total_bytes';
    }
    if (!in_array(strtoupper($sortOrder), $allowedSortOrders)) {
        $sortOrder = 'DESC';
    }
    
    // Get total count
    $countStmt = $pdo->query('SELECT COUNT(DISTINCT user_email) FROM user_files WHERE is_deleted = 0');
    $totalUsers = $countStmt->fetchColumn();
    
    // Get storage usage per user with pagination
    $sql = "SELECT 
                user_email, 
                COUNT(*) as file_count, 
                SUM(file_size) as total_bytes,
                MAX(upload_date) as last_upload,
                MIN(upload_date) as first_upload
             FROM user_files 
             WHERE is_deleted = 0
             GROUP BY user_email 
             ORDER BY $sortBy $sortOrder 
             LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $usage = $stmt->fetchAll();
    
    // Format file sizes and dates
    foreach ($usage as &$user) {
        $size = (int)$user['total_bytes'];
        $user['total_size'] = formatFileSize($size);
        $user['total_bytes'] = $size; // Keep raw bytes for sorting
        
        if ($user['last_upload']) {
            $user['last_upload'] = date('Y-m-d H:i:s', strtotime($user['last_upload']));
            $user['last_upload_ago'] = getTimeAgo(strtotime($user['last_upload']));
        }
        
        if ($user['first_upload']) {
            $user['first_upload'] = date('Y-m-d H:i:s', strtotime($user['first_upload']));
        }
        
        // Get user role
        $roleStmt = $pdo->prepare('SELECT role FROM users WHERE email = ?');
        $roleStmt->execute([$user['user_email']]);
        $user['role'] = $roleStmt->fetchColumn() ?: 'user';
        
        // Check if user is online
        $onlineThreshold = date('Y-m-d H:i:s', time() - (15 * 60));
        $onlineStmt = $pdo->prepare('SELECT COUNT(*) FROM user_sessions WHERE user_email = ? AND last_activity > ?');
        $onlineStmt->execute([$user['user_email'], $onlineThreshold]);
        $user['is_online'] = $onlineStmt->fetchColumn() > 0;
    }
    
    // Get system-wide statistics
    $totalStats = $pdo->query('SELECT 
        COUNT(*) as total_files,
        SUM(file_size) as total_storage,
        COUNT(DISTINCT user_email) as total_users
        FROM user_files WHERE is_deleted = 0')->fetch();
    
    // Update session activity
    update_session_activity();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'usage' => $usage,
        'system_stats' => [
            'total_files' => (int)$totalStats['total_files'],
            'total_storage' => formatFileSize((int)$totalStats['total_storage']),
            'total_users' => (int)$totalStats['total_users']
        ],
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalUsers / $limit),
            'users_per_page' => $limit
        ],
        'sort_by' => $sortBy,
        'sort_order' => $sortOrder
    ]);
    
} catch (Exception $e) {
    error_log("Admin Storage Usage Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve storage usage'
    ]);
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

/**
 * Get human readable time ago
 */
function getTimeAgo($timestamp) {
    $timeDiff = time() - $timestamp;
    
    if ($timeDiff < 60) {
        return 'Just now';
    } elseif ($timeDiff < 3600) {
        $minutes = floor($timeDiff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($timeDiff < 86400) {
        $hours = floor($timeDiff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($timeDiff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>