<?php
// api/list_files.php
require_once __DIR__ . '/../config/database.php';
$email = require_login();

$pdo = db();
$stmt = $pdo->prepare('SELECT id, original_name, file_type, file_size, upload_date FROM user_files WHERE user_email = ? ORDER BY upload_date DESC');
$stmt->execute([$email]);
$rows = $stmt->fetchAll();

json_ok(['files' => $rows]);
