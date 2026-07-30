<?php
// governance/includes/db.php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "privacyhq";
$port = 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

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
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO audit_logs (module, action, user_id, record_id, old_value, new_value, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$module, $action, $user_id, $record_id, $old_value, $new_value, $ip, $ua]);
        } catch (Exception $e) {}
    }
}
?>