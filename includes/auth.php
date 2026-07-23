<?php
/**
 * Authentication Module
 */

require_once __DIR__ . '/db_helper.php';

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // ini_set('session.cookie_secure', 1); // Enable if HTTPS
    session_start();
}

/**
 * Log an audit event
 */
function logAuditEvent($userId, $action, $details = null) {
    $db = new DBHelper();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    
    $sql = "INSERT INTO audit_logs (user_id, module, action, record_id, new_value, ip_address, user_agent) 
            VALUES (:user_id, 'Authentication', :action, 0, :details, :ip_address, :user_agent)";
    
    $db->execute($sql, [
        'user_id' => $userId,
        'action' => $action,
        'details' => $details ? json_encode($details) : null,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent
    ]);
}

/**
 * Authenticate a user
 */
function loginUser($email, $password) {
    $db = new DBHelper();
    
    // Find user by email
    $sql = "SELECT id, password_hash, status FROM users WHERE email = :email AND deleted_at IS NULL";
    $user = $db->fetchOne($sql, ['email' => $email]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        if (strtolower($user['status']) !== 'active') {
            return ['success' => false, 'error' => 'Account is not active.'];
        }
        
        // Prevent Session Fixation
        session_regenerate_id(true);
        
        $userId = $user['id'];
        $token = bin2hex(random_bytes(32));
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['auth_token'] = $token;
        
        // Create session record
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours'));
        
        $sessionSql = "INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at) 
                       VALUES (:user_id, :token, :ip_address, :user_agent, :expires_at)";
        $db->execute($sessionSql, [
            'user_id' => $userId,
            'token' => $token,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt
        ]);
        
        // Update last login
        $db->execute("UPDATE users SET last_login_at = NOW() WHERE id = :id", ['id' => $userId]);
        
        // Audit log
        logAuditEvent($userId, 'LOGIN');
        
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Invalid email or password.'];
}

/**
 * Logout a user
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $token = $_SESSION['auth_token'] ?? '';
        
        $db = new DBHelper();
        
        // Remove active session record
        if ($token) {
            $db->execute("DELETE FROM sessions WHERE token = :token", ['token' => $token]);
        }
        
        // Audit log
        logAuditEvent($userId, 'LOGOUT');
    }
    
    // Destroy PHP session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Protect authenticated pages
 */
function requireLogin($isApi = false) {
    if (!isset($_SESSION['user_id'])) {
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        } else {
            // Adjust redirect path based on depth
            $path = '/governance/login.php';
            header("Location: $path");
            exit;
        }
    }
    
    // Additional validation against DB token
    $db = new DBHelper();
    $sql = "SELECT id FROM sessions WHERE token = :token AND expires_at > NOW()";
    $session = $db->fetchOne($sql, ['token' => $_SESSION['auth_token']]);
    
    if (!$session) {
        logoutUser();
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Session expired.']);
            exit;
        } else {
            header("Location: /governance/login.php");
            exit;
        }
    }
}

/**
 * Get current authenticated user details
 */
function currentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    
    $db = new DBHelper();
    $sql = "SELECT id, first_name, last_name, email, role_id, profile_image, status 
            FROM users WHERE id = :id AND deleted_at IS NULL";
    return $db->fetchOne($sql, ['id' => $_SESSION['user_id']]);
}

/**
 * Check if current user has a specific permission
 */
function hasPermission($permissionName) {
    if (!isset($_SESSION['user_id'])) return false;
    
    $user = currentUser();
    if (!$user) return false;
    
    $db = new DBHelper();
    $sql = "SELECT 1 FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.id 
            WHERE rp.role_id = :role_id AND p.name = :perm_name";
    
    $result = $db->fetchOne($sql, [
        'role_id' => $user['role_id'],
        'perm_name' => $permissionName
    ]);
    
    return $result !== false;
}
