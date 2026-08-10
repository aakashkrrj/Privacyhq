<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class NotificationController extends BaseController {
    private $notificationService;

    public function __construct($notificationService) {
        $this->notificationService = $notificationService;
    }

    /**
     * Get recent notifications.
     */
    public function listNotifications() {
        try {
            $userId = $this->getUserId();
            $unreadOnly = filter_input(INPUT_GET, 'unread_only', FILTER_VALIDATE_BOOLEAN) || filter_input(INPUT_GET, 'unread_only', FILTER_VALIDATE_INT);
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;

            $data = $this->notificationService->getNotifications($userId, $unreadOnly, $limit);
            $unreadCount = $this->notificationService->getUnreadCount($userId);

            ApiResponse::success('Success', [
                'notifications' => $data,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Get unread notifications count.
     */
    public function getUnreadCount() {
        try {
            $userId = $this->getUserId();
            $count = $this->notificationService->getUnreadCount($userId);
            ApiResponse::success('Success', ['count' => $count]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead() {
        try {
            $id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new \Exception("Invalid notification ID.");
            }

            $userId = $this->getUserId();
            $this->notificationService->markAsRead($id, $userId);
            ApiResponse::success('Notification marked as read.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead() {
        try {
            $userId = $this->getUserId();
            $this->notificationService->markAllAsRead($userId);
            ApiResponse::success('All notifications marked as read.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Delete a notification.
     */
    public function delete() {
        try {
            $id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new \Exception("Invalid notification ID.");
            }

            $userId = $this->getUserId();
            $this->notificationService->deleteNotification($id, $userId);
            ApiResponse::success('Notification deleted.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
