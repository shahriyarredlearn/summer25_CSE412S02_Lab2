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
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Define online threshold (15 minutes)
    $onlineThreshold = date('Y-m-d H:i:s', time() - (15 * 60));
    
    // Get total count of online users
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM user_sessions WHERE last_activity > ?');
    $countStmt->execute([$onlineThreshold]);
    $totalOnline = $countStmt->fetchColumn();
    
    // Fetch online users with pagination
    $stmt = $pdo->prepare(
        'SELECT us.user_email, us.last_activity, us.ip_address, us.user_agent, u.role
         FROM user_sessions us
         JOIN users u ON us.user_email = u.email
         WHERE us.last_activity > ? 
         ORDER BY us.last_activity DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$onlineThreshold, $limit, $offset]);
    $users = $stmt->fetchAll();
    
    // Format user data
    foreach ($users as &$user) {
        $user['last_activity'] = date('Y-m-d H:i:s', strtotime($user['last_activity']));
        $user['time_ago'] = getTimeAgo(strtotime($user['last_activity']));
        
        // Get user's current file count
        $fileStmt = $pdo->prepare('SELECT COUNT(*) FROM user_files WHERE user_email = ? AND is_deleted = 0');
        $fileStmt->execute([$user['user_email']]);
        $user['file_count'] = (int)$fileStmt->fetchColumn();
    }
    
    // Update session activity
    update_session_activity();
    
    echo json_encode([
        'success' => true,
        'ok' => true,
        'users' => $users,
        'total_online' => $totalOnline,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalOnline / $limit),
            'online_users_per_page' => $limit
        ],
        'online_threshold' => '15 minutes'
    ]);
    exit;
    
} catch (Exception $e) {
    error_log("Admin Online Users Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve online users']);
    http_response_code(500);
    exit;
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