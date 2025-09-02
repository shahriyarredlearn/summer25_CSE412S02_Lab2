<?php
error_reporting(0);
ini_set('display_errors', 0);

// Include database configuration and auth functions
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';



try {
    // Parse JSON input
    $data = read_json();

    
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = $data['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        json_error('Email and password are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email format');
    }
    
    if (strlen($password) < 6) {
        json_error('Password must be at least 6 characters');
    }
    
    // Check if user already exists
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->fetchColumn() > 0) {
        json_error('Email already registered');
    }
    
    // Hash password and create user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, "user")');
    
    if (!$stmt->execute([$email, $passwordHash])) {
        json_error('Failed to create user account');
    }
    
    $userId = $pdo->lastInsertId();
    
    // Create user session automatically after registration
    create_user_session($email, 'user');
    
    json_ok([
        'message' => 'Registration successful',
        'email' => $email,
        'role' => 'user',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    json_error('An error occurred during registration: ' . $e->getMessage(), 500);
}
?>

<script>
document.getElementById('loginFormElement').addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('loginEmail').value;
  const password = document.getElementById('loginPassword').value;

  try {
    const res = await fetch('./login.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email, password})
    });
    
    // Try to parse response as JSON
    let data;
    const text = await res.text();
    
    try {
      data = JSON.parse(text);
    } catch (parseError) {
      showMessage('Server returned invalid data', 'error');
      return;
    }
    
    if (data.ok || data.success) {
      showMessage('Login successful!', 'success');
      setTimeout(() => window.location.href = './files.html', 1000);
    } else {
      showMessage(data.error || 'Login failed', 'error');
    }
  } catch (err) {
    showMessage('Network error: ' + (err.message || 'Unable to connect'), 'error');
  }
});

// Similar updates for register form...
</script>