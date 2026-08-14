<?php
namespace Backend\Services;

class WorkflowService {
    private static $pdo = null;
    private static $registry = [
        // Assessment events
        'assessment.assigned' => [
            'create_task' => [
                'type' => 'Perform Assessment',
                'title' => 'Perform Assessment: {title}',
                'description' => 'Perform and fill out the dynamic questionnaire sections.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'created_by',
                'priority' => 'priority'
            ],
            'notify' => [
                'user_id' => 'assigned_to',
                'category' => 'Assignment',
                'priority' => 'priority',
                'title' => 'New Assessment Assigned',
                'message' => 'You have been assigned to perform assessment: {title}'
            ],
            'timeline' => 'Assessment assigned to assessor.'
        ],
        'assessment.submitted' => [
            'complete_prior_tasks' => true,
            'create_task' => [
                'type' => 'Review Assessment',
                'title' => 'Review Assessment: {title}',
                'description' => 'Review responses and risk findings generated.',
                'assigned_to' => 'reviewer_id',
                'assigned_by' => 'assigned_to',
                'priority' => 'priority'
            ],
            'notify' => [
                'user_id' => 'reviewer_id',
                'category' => 'Approval',
                'priority' => 'priority',
                'title' => 'Assessment Submitted for Review',
                'message' => 'Assessment {title} has been submitted by assessor and requires your review.'
            ],
            'timeline' => 'Assessment submitted for review.'
        ],
        'assessment.approved' => [
            'complete_prior_tasks' => true,
            'notify' => [
                'user_id' => 'assigned_to',
                'category' => 'Approval',
                'priority' => 'priority',
                'title' => 'Assessment Approved',
                'message' => 'Assessment {title} has been approved.'
            ],
            'timeline' => 'Assessment approved.'
        ],
        'assessment.rejected' => [
            'complete_prior_tasks' => true,
            'create_task' => [
                'type' => 'Perform Assessment',
                'title' => 'Rework Assessment: {title}',
                'description' => 'Please update responses based on reviewer feedback.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'reviewer_id',
                'priority' => 'priority'
            ],
            'notify' => [
                'user_id' => 'assigned_to',
                'category' => 'Approval',
                'priority' => 'priority',
                'title' => 'Assessment Rework Requested',
                'message' => 'Assessment {title} has been rejected with requests for changes.'
            ],
            'timeline' => 'Assessment change request initiated.'
        ],

        // DSR events
        'dsr.created' => [
            'create_task' => [
                'type' => 'Verify DSR',
                'title' => 'Verify DSR Request: {subject_email}',
                'description' => 'Verify identity and validate details of DSR request.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'created_by',
                'priority' => 'priority'
            ],
            'notify' => [
                'user_id' => 'assigned_to',
                'category' => 'Assignment',
                'priority' => 'priority',
                'title' => 'New DSR Request Assigned',
                'message' => 'You have been assigned to verify DSR Request for {subject_email}'
            ],
            'timeline' => 'DSR request created for subject {subject_email}'
        ],
        'dsr.verified' => [
            'complete_prior_tasks' => true,
            'create_task' => [
                'type' => 'Collect DSR Data',
                'title' => 'Collect DSR Data: {subject_email}',
                'description' => 'Gather all personal data associated with subject.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'created_by',
                'priority' => 'priority'
            ],
            'timeline' => 'DSR request verified for subject {subject_email}'
        ],
        'dsr.completed' => [
            'complete_prior_tasks' => true,
            'notify' => [
                'user_id' => 'created_by',
                'category' => 'Assignment',
                'priority' => 'priority',
                'title' => 'DSR Request Completed',
                'message' => 'DSR Request for {subject_email} is now completed.'
            ],
            'timeline' => 'DSR request completed for subject {subject_email}'
        ],

        // Incident events
        'incident.created' => [
            'create_task' => [
                'type' => 'Review Incident',
                'title' => 'Review Incident: {summary}',
                'description' => 'Perform initial triage and log containment strategy.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'created_by',
                'priority' => 'priority'
            ],
            'timeline' => 'Incident created: {summary}'
        ],
        'incident.escalated' => [
            'notify' => [
                'user_id' => 'dpo_user_id',
                'category' => 'Escalation',
                'priority' => 'Critical',
                'title' => 'CRITICAL Incident Escalated',
                'message' => 'Incident {summary} is marked critical and DPO notification is triggered.'
            ],
            'timeline' => 'Incident escalated to DPO: {summary}'
        ],

        // Policy events
        'policy.created' => [
            'create_task' => [
                'type' => 'Approve Policy',
                'title' => 'Approve Policy: {title}',
                'description' => 'Review policy details and sign off.',
                'assigned_to' => 'approver_id',
                'assigned_by' => 'created_by',
                'priority' => 'Medium'
            ],
            'timeline' => 'Policy drafted: {title}'
        ],

        // Vendor events
        'vendor.created' => [
            'create_task' => [
                'type' => 'Review Vendor',
                'title' => 'Review Vendor: {name}',
                'description' => 'Perform vendor security and DPA assessments.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'created_by',
                'priority' => 'Medium'
            ],
            'timeline' => 'Vendor created: {name}'
        ],

        // Ropa events
        'ropa.created' => [
            'create_task' => [
                'type' => 'Review ROPA',
                'title' => 'Review ROPA: {activity_name}',
                'description' => 'Perform periodic register of processing activity review.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'created_by',
                'priority' => 'Medium'
            ],
            'timeline' => 'ROPA entry created: {activity_name}'
        ],

        // Risk events
        'risk.created' => [
            'create_task' => [
                'type' => 'Risk Review',
                'title' => 'Review Risk: {title}',
                'description' => 'Perform impact and likelihood mapping analysis.',
                'assigned_to' => 'assigned_to',
                'assigned_by' => 'created_by',
                'priority' => 'priority'
            ],
            'timeline' => 'New Risk logged: {title}'
        ],

        // Task complete triggers (internal dependency triggers)
        'task.completed' => [
            'timeline' => 'Task completed: {title}'
        ],
        'reminder.triggered' => [
            'notify' => [
                'user_id' => 'user_id',
                'category' => 'Reminder',
                'priority' => 'High',
                'title' => '{title}',
                'message' => '{message}'
            ]
        ],

        // Cookie Governance workflow events
        'cookie.scan.completed' => [
            'timeline' => 'Cookie discovery scan completed for domain: {domain}. Cookies found: {cookies_found}.'
        ],
        'cookie.scan.failed' => [
            'timeline' => 'Cookie discovery scan failed for domain. Error: {error}.'
        ],
        'cookie.classification.review_required' => [
            'timeline' => 'Cookie classification changed: {cookie_name} moved from {old_category} to {new_category}.'
        ],
        'cookie.consent.changed' => [
            'timeline' => 'Data subject cookie consent preferences changed for {subject_email}.'
        ]
    ];

    /**
     * Initialize connection
     */
    public static function setPdo(\PDO $pdo) {
        self::$pdo = $pdo;
    }

    /**
     * Dispatch standard workflow events
     */
    public static function dispatch($event, array $payload) {
        if (!self::$pdo) {
            // Lazy load from global $pdo variable if available
            global $pdo;
            if (isset($pdo)) {
                self::$pdo = $pdo;
            } else {
                throw new \Exception("WorkflowService PDO connection not set.");
            }
        }

        $rules = self::$registry[$event] ?? null;
        if (!$rules) {
            return; // No registered workflow rules for this event
        }

        // Extract standard identifiers
        $module = $payload['module'] ?? 'Global';
        $recordId = $payload['record_id'] ?? 0;
        $performedBy = $payload['performed_by'] ?? ($_SESSION['user_id'] ?? 1);

        // Parse placeholders in templates
        $parseTemplate = function($template) use ($payload) {
            if (!is_string($template)) return $template;
            $parsed = $template;
            foreach ($payload as $key => $val) {
                if (is_scalar($val)) {
                    $parsed = str_replace('{' . $key . '}', $val, $parsed);
                }
            }
            return $parsed;
        };

        // 1. Complete prior tasks for sequential workflow dependency execution
        if (!empty($rules['complete_prior_tasks'])) {
            $stmtComplete = self::$pdo->prepare("
                UPDATE tasks 
                SET status = 'Completed', completed_at = NOW() 
                WHERE module = ? AND record_id = ? AND status != 'Completed'
            ");
            $stmtComplete->execute([$module, $recordId]);
        }

        // 2. Create dynamic Task
        if (!empty($rules['create_task'])) {
            $taskConf = $rules['create_task'];
            
            $assignedTo = $payload[$taskConf['assigned_to']] ?? null;
            $assignedBy = $payload[$taskConf['assigned_by']] ?? $performedBy;
            $priority = $payload[$taskConf['priority']] ?? ($taskConf['priority'] ?? 'Medium');
            
            // Map priority if it contains invalid values
            if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) {
                $priority = 'Medium';
            }

            if ($assignedTo) {
                try {
                    $taskTitle = $parseTemplate($taskConf['title']);
                    $taskDesc = $parseTemplate($taskConf['description']);
                    $taskType = $taskConf['type'];
                    $dueDate = $payload['due_date'] ?? date('Y-m-d', strtotime('+7 days'));

                    $taskService = new TaskService(self::$pdo);
                    $taskId = $taskService->createTask($module, $recordId, $taskType, $taskTitle, $taskDesc, $assignedTo, $assignedBy, $priority, $dueDate);
                    
                    // Dispatch notification for task assignment
                    $notifyTitle = 'New Task Assigned';
                    $notifyMsg = "You have been assigned the task: " . $taskTitle;
                    
                    $notifyService = new NotificationService(self::$pdo);
                    $notifyService->createNotification($assignedTo, $module, $recordId, 'Assignment', $priority, $notifyTitle, $notifyMsg);
                } catch (\Throwable $te) {
                    // Log or ignore non-blocking task creation failure
                }
            }
        }

        // 3. Create Notification
        if (!empty($rules['notify'])) {
            $notifyConf = $rules['notify'];
            $userId = $payload[$notifyConf['user_id']] ?? null;
            if ($userId) {
                $notifyTitle = $parseTemplate($notifyConf['title']);
                $notifyMsg = $parseTemplate($notifyConf['message']);
                $priority = $payload[$notifyConf['priority']] ?? ($notifyConf['priority'] ?? 'Medium');
                
                if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) {
                    $priority = 'Medium';
                }

                $notifyService = new NotificationService(self::$pdo);
                $notifyService->createNotification($userId, $module, $recordId, $notifyConf['category'], $priority, $notifyTitle, $notifyMsg);
            }
        }

        // 4. Write Activity Timeline
        if (!empty($rules['timeline'])) {
            $activityMsg = $parseTemplate($rules['timeline']);
            $activityService = new ActivityService(self::$pdo);
            $activityService->logActivity($module, $recordId, $performedBy, $event, $payload['old_status'] ?? null, $payload['new_status'] ?? null, $payload);
        }

        // 5. Write Audit Log
        if (function_exists('log_audit_event')) {
            log_audit_event(self::$pdo, 'Workflow', $event, $performedBy, $recordId, $payload['old_status'] ?? null, $payload['new_status'] ?? null);
        }
    }
}
