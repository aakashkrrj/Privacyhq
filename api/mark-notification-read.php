<?php
// governance/api/mark-notification-read.php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$mark_all = isset($_POST['all']) || isset($_GET['all']);

if (isset($conn) && !$conn->connect_error) {
    if ($mark_all) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($id > 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Get remaining unread count
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_count = 0;
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $unread_count = (int)$row['unread'];
        }
        $stmt->close();
    }

    echo json_encode(['success' => true, 'unread_count' => $unread_count]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Database connection failed']);
