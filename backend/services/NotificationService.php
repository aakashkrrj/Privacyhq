<?php
namespace Backend\Services;

class NotificationService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createNotification($userId, $module, $recordId, $category, $priority, $title, $message, $type = 'info') {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, module, record_id, category, priority, title, message, type, is_read)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$userId, $module, $recordId, $category, $priority, $title, $message, $type]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Get recent notifications.
     */
    public function getNotifications($userId, $unreadOnly = false, $limit = 20) {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . intval($limit);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get unread notifications count.
     */
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        return true;
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        return true;
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification($notificationId, $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        return true;
    }
}
