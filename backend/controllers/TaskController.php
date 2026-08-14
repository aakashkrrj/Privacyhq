<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class TaskController extends BaseController {
    private $taskService;

    public function __construct($taskService) {
        $this->taskService = $taskService;
    }

    /**
     * Get tasks assigned to the logged-in user.
     */
    public function listMyTasks() {
        try {
            $userId = $this->getUserId();
            $statusFilter = trim($_GET['status'] ?? 'Pending');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $tasks = $this->taskService->getMyTasks($userId, $statusFilter, $page);
            ApiResponse::success('Success', $tasks);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Complete a task.
     */
    public function complete() {
        try {
            $taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
            if (!$taskId) {
                throw new \Exception("Invalid task ID.");
            }

            $userId = $this->getUserId();
            $this->taskService->completeTask($taskId, $userId);
            ApiResponse::success('Task marked as completed successfully.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
