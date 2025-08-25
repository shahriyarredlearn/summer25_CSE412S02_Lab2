<?php
// api/delete_file.php
require_once __DIR__ . '/../config/app.php';

// Ensure user is logged in
$email = require_login();

// Parse JSON input
$data = read_json();
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) {
    json_response(false, ['error' => 'Invalid file ID'], 400);
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Lock the row to prevent race conditions
    $stmt = $pdo->prepare('SELECT stored_name FROM user_files WHERE id = ? AND user_email = ? FOR UPDATE');
    $stmt->execute([$id, $email]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        json_response(false, ['error' => 'File not found'], 404);
    }

    $storedName = $row['stored_name'];
    $filePath = realpath(__DIR__ . '/../uploads/' . $storedName);

    // Delete record from database
    $stmt = $pdo->prepare('DELETE FROM user_files WHERE id = ? AND user_email = ?');
    $stmt->execute([$id, $email]);

    $pdo->commit();

    // Delete physical file if exists
    if ($filePath && is_file($filePath)) {
        if (!@unlink($filePath)) {
            log_error("Failed to delete file: " . $filePath);
        }
    }

    json_response(true, [
        'message' => 'File deleted successfully',
        'id' => $id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_error("Delete File Error: " . $e->getMessage());
    json_response(false, ['error' => 'Internal server error'], 500);
}
