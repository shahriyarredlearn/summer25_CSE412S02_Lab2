<?php
// api/logout.php
require_once __DIR__ . '/../config/app.php';

try {
    start_app_session();

    // Clear all session data
    $_SESSION = [];

    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }

    // Destroy the session
    session_destroy();

    // Regenerate session ID for safety
    session_start();
    session_regenerate_id(true);

    json_response(true, ['message' => 'Logged out successfully']);

} catch (Exception $e) {
    log_error("Logout Error: " . $e->getMessage());
    json_response(false, ['error' => 'Logout failed'], 500);
}
