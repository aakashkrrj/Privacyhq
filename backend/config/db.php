<?php
// governance/includes/db.php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "privacyhq";

$port = 3306;
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, $dbname, 3306);
if ($conn->connect_error) {
    $conn = @new mysqli($host, $user, $pass, $dbname, 3307);
    $port = 3307;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode([
        "status" => "error",
        "message" => "PDO connection failed: " . $e->getMessage()
    ]));
}

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

// Session Management & CSRF Token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Audit Logging Helper
if (!function_exists('log_audit_event')) {
    function log_audit_event($pdo, $module, $action, $user_id, $record_id, $old_value = null, $new_value = null) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
            $stmt = $pdo->prepare("INSERT INTO audit_logs (module, action, user_id, record_id, old_value, new_value, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$module, $action, $user_id, $record_id, $old_value, $new_value, $ip, $ua]);
        } catch (\Throwable $e) {}
    }
}

if (!function_exists('has_permission')) {
    function has_permission($permission) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (($_SESSION['role_id'] ?? null) == 1) {
            return true;
        }
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array($permission, $permissions);
    }
}

if (!function_exists('require_permission')) {
    function require_permission($permission) {
        if (!has_permission($permission)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 403 Forbidden');
                echo json_encode([
                    "success" => false,
                    "status" => "error",
                    "message" => "Unauthorized access. Permission required: " . $permission
                ]);
                exit;
            } else {
                header('Location: index.php?page=dashboard&error=' . urlencode("Unauthorized access to that section."));
                exit;
            }
        }
    }
}

if (!function_exists('has_any_permission')) {
    function has_any_permission(array $permissions) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (($_SESSION['role_id'] ?? null) == 1) {
            return true;
        }
        $userPerms = $_SESSION['permissions'] ?? [];
        foreach ($permissions as $p) {
            if (in_array($p, $userPerms)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('require_any_permission')) {
    function require_any_permission(array $permissions) {
        if (!has_any_permission($permissions)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 403 Forbidden');
                echo json_encode([
                    "success" => false,
                    "status" => "error",
                    "message" => "Unauthorized access. Any of the following permissions required: " . implode(', ', $permissions)
                ]);
                exit;
            } else {
                header('Location: index.php?page=dashboard&error=' . urlencode("Unauthorized access to that section."));
                exit;
            }
        }
    }
}

if (!function_exists('has_ownership')) {
    function has_ownership($record) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (($_SESSION['role_id'] ?? null) == 1) {
            return true;
        }
        $currentUserId = $_SESSION['user_id'] ?? null;
        if ($currentUserId === null) {
            return false;
        }
        
        $ownerFields = ['created_by', 'assigned_to', 'reviewer_id', 'owner', 'user_id', 'answered_by', 'uploaded_by'];
        
        if (is_array($record)) {
            foreach ($ownerFields as $field) {
                if (isset($record[$field]) && (int)$record[$field] === (int)$currentUserId) {
                    return true;
                }
            }
        } elseif (is_object($record)) {
            foreach ($ownerFields as $field) {
                if (isset($record->$field) && (int)$record->$field === (int)$currentUserId) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

if (!function_exists('require_ownership_or_permission')) {
    function require_ownership_or_permission($permission, $record) {
        if (has_permission($permission)) {
            return;
        }
        if (!has_ownership($record)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 403 Forbidden');
                echo json_encode([
                    "success" => false,
                    "status" => "error",
                    "message" => "Unauthorized access. Ownership or permission '" . $permission . "' required."
                ]);
                exit;
            } else {
                header('Location: index.php?page=dashboard&error=' . urlencode("Unauthorized access: ownership or permission required."));
                exit;
            }
        }
    }
}

if (!function_exists('getProfileImageUrl')) {
    function getProfileImageUrl($dbPath) {
        $defaultAvatar = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';
        if (empty($dbPath)) {
            return $defaultAvatar;
        }
        // Normalize any absolute Windows paths to relative uploads paths if present
        $cleanPath = str_replace('\\', '/', $dbPath);
        if (preg_match('/uploads\/(.+)/i', $cleanPath, $matches)) {
            $relativePath = 'uploads/' . $matches[1];
        } else {
            $relativePath = $cleanPath;
        }
        
        $fullLocalPath = 'd:/New folder/governance/' . $relativePath;
        if (file_exists($fullLocalPath) && is_readable($fullLocalPath)) {
            return $relativePath;
        }
        return $defaultAvatar;
    }
}
?>