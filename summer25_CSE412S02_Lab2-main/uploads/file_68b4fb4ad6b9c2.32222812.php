<?php
/**
 * Authentication helper functions
 */

require_once __DIR__ . '/config/database.php';

/**
 * Register a new user
 */
function register_user(string $email, string $password): bool {
    try {
        // Validate input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Connect to database
        $pdo = db();
        
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
        return $stmt->execute([$email, $password_hash]);
    } catch (Exception $e) {
        log_error("Registration error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a user is logged in
 */
function is_logged_in(): bool {
    start_app_session();
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function get_current_user_data(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        log_error("Error getting user data: " . $e->getMessage());
        return null;
    }
}

/**
 * Require user to be logged in
 */
function require_login(): void {
    if (!is_logged_in()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
}

/**
 * Require user to be an admin
 */
function require_admin(): void {
    require_login();
    $user = get_current_user_data();
    if ($user['role'] !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Admin access required'
        ]);
        exit;
    }
}

/**
 * Create a new user session record
 */
function create_user_session(string $email, string $role): void {
    start_app_session();
    
    // Store user info in session
    $_SESSION['user_id'] = get_user_id_by_email($email);
    $_SESSION['user_email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['created_at'] = time();
    $_SESSION['last_activity'] = time();
    
    // Track user session for online status
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $pdo = db();
    
    try {
        // Begin transaction for session management
        $pdo->beginTransaction();
        
        // Delete any existing session for this user
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_email = ?');
        $stmt->execute([$email]);
        
        // Create new session record
        $stmt = $pdo->prepare('INSERT INTO user_sessions (user_email, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $sessionId, $ipAddress, $userAgent]);
        
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_error("Failed to create user session: " . $e->getMessage());
    }
}

/**
 * End user session and remove from tracking
 */
function end_user_session(): void {
    start_app_session();
    
    // Get user email and session ID before clearing session
    $userEmail = $_SESSION['user_email'] ?? null;
    $sessionId = session_id();
    
    try {
        // Remove user session from tracking table if email exists
        if ($userEmail) {
            $pdo = db();
            
            // Use more specific query to prevent session confusion
            $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_email = ? AND session_id = ?');
            $stmt->execute([$userEmail, $sessionId]);
            
            // Log the logout for security auditing
            log_message('info', "User logged out: {$userEmail}");
        }
    } catch (Exception $e) {
        // Log error but continue with logout process
        log_error("Error removing session from tracking: " . $e->getMessage());
    }
    
    // Clear all session data
    $_SESSION = [];
    
    // Destroy session cookie with secure parameters
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params["path"],
                'domain' => $params["domain"],
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Regenerate session ID for safety
    session_start();
    session_regenerate_id(true);
}

/**
 * Get user ID by email
 */
function get_user_id_by_email(string $email): ?int {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ? (int)$result['id'] : null;
    } catch (Exception $e) {
        log_error("Error getting user ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if current user has admin role
 */
function is_admin(): bool {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    return $_SESSION['role'] === 'admin';
}

/**
 * Update session activity
 */
function update_session_activity(): void {
    if (isset($_SESSION['user_email'])) {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('UPDATE user_sessions SET last_activity = NOW() WHERE user_email = ? AND session_id = ?');
            $stmt->execute([$_SESSION['user_email'], session_id()]);
        } catch (Exception $e) {
            log_error("Error updating session activity: " . $e->getMessage());
        }
    }
}

/**
 * Check if session is still valid
 */
function is_session_valid(): bool {
    if (!isset($_SESSION['user_email']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Check if session has expired (30 minutes)
    $sessionTimeout = 30 * 60; // 30 minutes in seconds
    if (time() - $_SESSION['last_activity'] > $sessionTimeout) {
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    update_session_activity();
    
    return true;
}
?>