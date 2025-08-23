<?php
// api/delete_file.php
require_once __DIR__ . '/../config/database.php';
$email = require_login();

$data = read_json();
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) json_error('Invalid id');

$pdo = db();
$pdo->beginTransaction();

$stmt = $pdo->prepare('SELECT stored_name FROM user_files WHERE id = ? AND user_email = ? FOR UPDATE');
$stmt->execute([$id, $email]);
$row = $stmt->fetch();
if (!$row) {
    $pdo->rollBack();
    json_error('File not found', 404);
}

$path = realpath(__DIR__ . '/../uploads/' . $row['stored_name']);
$pdo->prepare('DELETE FROM user_files WHERE id = ? AND user_email = ?')->execute([$id, $email]);
$pdo->commit();

if ($path && is_file($path)) @unlink($path);

json_ok(['message' => 'Deleted', 'id' => $id]);
