<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/config/database.php';
    start_app_session();
    
    // Check admin authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        echo json_encode(['ok' => false, 'error' => 'Admin access required']);
        exit;
    }
    
    $pdo = db();
    
    // Get user statistics
    $userStatsQuery = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
            COUNT(CASE WHEN role = 'user' THEN 1 END) as user_count,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30_days
        FROM users
    ");
    $userStats = $userStatsQuery->fetch();
    
    // Get file statistics
    $fileStatsQuery = $pdo->query("
        SELECT 
            COUNT(*) as total_files,
            COALESCE(SUM(file_size), 0) as total_size,
            COUNT(CASE WHEN upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as files_last_week
        FROM user_files 
        WHERE is_deleted = 0
    ");
    $fileStats = $fileStatsQuery->fetch();
    
    // Get all users with file counts
    $usersQuery = $pdo->query("
        SELECT 
            u.id,
            u.email,
            u.role,
            u.created_at,
            COUNT(uf.id) as file_count,
            COALESCE(SUM(uf.file_size), 0) as total_file_size,
            MAX(uf.upload_date) as last_upload
        FROM users u
        LEFT JOIN user_files uf ON u.email = uf.user_email AND uf.is_deleted = 0
        GROUP BY u.id, u.email, u.role, u.created_at
        ORDER BY u.created_at DESC
    ");
    $users = $usersQuery->fetchAll();
    
    // Get recent activity
    $recentActivityQuery = $pdo->query("
        SELECT 
            'user_registration' as type,
            u.email as user_email,
            'New user registered' as details,
            u.created_at as activity_date
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $recentActivity = $recentActivityQuery->fetchAll();
    
    echo json_encode([
        'ok' => true,
        'success' => true,
        'user_stats' => $userStats,
        'file_stats' => $fileStats,
        'users' => $users,
        'recent_activity' => $recentActivity
    ]);
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'error' => 'Failed to load admin dashboard: ' . $e->getMessage()
    ]);
}
?>