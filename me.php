<?php
// api/me.php
require_once __DIR__ . '/../config/database.php';
start_app_session();
if (!isset($_SESSION['email'])) {
    json_error('No session', 401);
}
json_ok(['email' => $_SESSION['email']]);
