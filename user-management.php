<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

try {
    start_app_session();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Admin access required'
        ]);
        exit;
    }
    
    $data = read_json();
    $action = $data['action'] ?? '';
    
    $pdo = db();
    
    switch ($action) {
        case 'delete_user':
            $userId = $data['user_id'] ?? 0;
            if (!$userId) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'User ID required'
                ]);
                exit;
            }
            
            // Don't allow deleting yourself
            if ($userId == $_SESSION['user_id']) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Cannot delete your own account'
                ]);
                exit;
            }
            
            // Delete user and their files
            $pdo->beginTransaction();
            try {
                // Mark user files as deleted
                $stmt = $pdo->prepare("UPDATE user_files SET is_deleted = 1, deleted_at = NOW() WHERE user_email = (SELECT email FROM users WHERE id = ?)");
                $stmt->execute([$userId]);
                
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                
                $pdo->commit();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'ok' => true,
                    'message' => 'User deleted successfully'
                ]);
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;
            
        case 'reset_password':
            $userId = $data['user_id'] ?? 0;
            $newPassword = $data['new_password'] ?? '';
            
            if (!$userId || !$newPassword) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'User ID and new password required'
                ]);
                exit;
            }
            
            if (strlen($newPassword) < 6) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Password must be at least 6 characters'
                ]);
                exit;
            }
            
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $userId]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'ok' => true,
                'message' => 'Password reset successfully'
            ]);
            break;
            
        case 'add_user':
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $role = $data['role'] ?? 'user';
            
            if (!$email || !$password) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Email and password required'
                ]);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid email format'
                ]);
                exit;
            }
            
            if (!in_array($role, ['user', 'admin'])) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid role'
                ]);
                exit;
            }
            
            // Check if email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Email already exists'
                ]);
                exit;
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$email, $passwordHash, $role]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'ok' => true,
                'message' => 'User added successfully'
            ]);
            break;
            
        default:
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            exit;
    }
    
} catch (Exception $e) {
    error_log("User management error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Operation failed: ' . $e->getMessage()
    ]);
    exit;
}
?>