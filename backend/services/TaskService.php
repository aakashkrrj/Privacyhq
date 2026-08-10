<?php
namespace Backend\Services;

class TaskService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new task.
     */
    public function createTask($module, $recordId, $taskType, $title, $description, $assignedTo, $assignedBy, $priority = 'Medium', $dueDate = null, $parentTaskId = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO tasks (module, record_id, task_type, title, description, assigned_to, assigned_by, priority, status, due_date, parent_task_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)
        ");
        $stmt->execute([$module, $recordId, $taskType, $title, $description, $assignedTo, $assignedBy, $priority, $dueDate, $parentTaskId]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Complete a task by ID.
     */
    public function completeTask($taskId, $userId) {
        // Fetch task
        $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$task) {
            throw new \Exception("Task not found.");
        }

        if ($task['assigned_to'] != $userId && $_SESSION['role_id'] != 1) {
            throw new \Exception("Access Denied: You are not assigned to this task.");
        }

        if ($task['status'] === 'Completed') {
            return true;
        }

        $stmtUpd = $this->pdo->prepare("
            UPDATE tasks 
            SET status = 'Completed', completed_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpd->execute([$taskId]);

        // Trigger task completed event in WorkflowService
        if (class_exists('\Backend\Services\WorkflowService')) {
            \Backend\Services\WorkflowService::dispatch('task.completed', [
                'task' => $task,
                'completed_by' => $userId
            ]);
        }

        return true;
    }

    /**
     * Get tasks assigned to a user.
     */
    public function getMyTasks($userId, $statusFilter = 'Pending', $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT t.*, u.email as assigned_by_email 
                FROM tasks t 
                JOIN users u ON t.assigned_by = u.id 
                WHERE t.assigned_to = ?";
        
        $params = [$userId];

        if ($statusFilter === 'Completed') {
            $sql .= " AND t.status = 'Completed'";
        } elseif ($statusFilter === 'Overdue') {
            $sql .= " AND t.status != 'Completed' AND t.due_date < CURDATE()";
        } elseif ($statusFilter === 'DueToday') {
            $sql .= " AND t.status != 'Completed' AND t.due_date = CURDATE()";
        } else {
            $sql .= " AND t.status != 'Completed'";
        }

        $sql .= " ORDER BY t.due_date ASC, t.priority DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
