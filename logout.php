<?php
// api/logout.php
require_once __DIR__ . '/../config/database.php';
start_app_session();
session_unset();
session_destroy();
json_ok(['message' => 'Logged out']);
