<?php
// admin_delete_user.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

try {
    // Ensure user is logged in and is an admin
    require_admin();
    $data = read_json();
    $userId = isset($data['userId']) ? (int)$data['userId'] : 0;

    if ($userId <= 0) {
        json_response(false, ['error' => 'Invalid user ID'], 400);
    }

    // Get user email before deletion for file cleanup
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userToDelete = $stmt->fetch();

    if (!$userToDelete) {
        json_response(false, ['error' => 'User not found'], 404);
    }

    // Start transaction
    $pdo->beginTransaction();

    // Delete user's session
    $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_email = ?');
    $stmt->execute([$userToDelete['email']]);

    // Mark user's files as deleted
    $stmt = $pdo->prepare(
        'UPDATE user_files 
         SET is_deleted = 1, deleted_at = NOW() 
         WHERE user_email = ?'
    );
    $stmt->execute([$userToDelete['email']]);

    // Delete the user
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);

    // Commit transaction
    $pdo->commit();

    json_response(true, ['message' => 'User deleted successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
} catch (Exception $e) {
    log_error("Admin Delete User Error: " . $e->getMessage());
    json_response(false, ['error' => 'Failed to delete user'], 500);
}