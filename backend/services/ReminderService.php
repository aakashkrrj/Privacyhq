<?php
namespace Backend\Services;

class ReminderService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Check and schedule reminders for impending deadlines.
     * This service is designed to be triggered by a scheduler/cron job.
     */
    public function processReminders() {
        $remindersSent = 0;

        // 1. Assessment due tomorrow
        $stmtAss = $this->pdo->query("
            SELECT p.id, p.title, p.assigned_to, p.due_date 
            FROM privacy_assessments p
            JOIN assessment_statuses s ON p.status_id = s.id
            WHERE s.status_name NOT IN ('Approved', 'Closed')
              AND p.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ");
        while ($row = $stmtAss->fetch(\PDO::FETCH_ASSOC)) {
            $this->triggerReminder($row['assigned_to'], 'Assessment', $row['id'], 'Assessment due tomorrow', "The assessment '{$row['title']}' is due on {$row['due_date']}.");
            $remindersSent++;
        }

        // 2. Tasks due tomorrow
        $stmtTsk = $this->pdo->query("
            SELECT id, title, assigned_to, due_date, module, record_id
            FROM tasks 
            WHERE status != 'Completed' 
              AND due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ");
        while ($row = $stmtTsk->fetch(\PDO::FETCH_ASSOC)) {
            $this->triggerReminder($row['assigned_to'], $row['module'], $row['record_id'], 'Task due tomorrow', "The task '{$row['title']}' is due on {$row['due_date']}.");
            $remindersSent++;
        }

        return $remindersSent;
    }

    private function triggerReminder($userId, $module, $recordId, $title, $message) {
        if (class_exists('\Backend\Services\WorkflowService')) {
            \Backend\Services\WorkflowService::dispatch('reminder.triggered', [
                'user_id' => $userId,
                'module' => $module,
                'record_id' => $recordId,
                'title' => $title,
                'message' => $message
            ]);
        }
    }
}
