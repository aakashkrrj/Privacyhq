<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class AuditLogController extends BaseController {
    private $auditService;

    public function __construct($auditService) {
        $this->auditService = $auditService;
    }

    /**
     * Get filtered audit log list.
     */
    public function listLogs() {
        try {
            // Require Super Admin or DPO
            $roleId = $_SESSION['role_id'] ?? 0;
            if ($roleId != 1 && $roleId != 2) {
                throw new \Exception("Access Denied: You do not have permission to view audit logs.");
            }

            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;
            $filters = [
                'date' => $_GET['date'] ?? '',
                'user' => $_GET['user'] ?? '',
                'module' => $_GET['module'] ?? '',
                'action' => $_GET['action'] ?? '',
                'search' => $_GET['search'] ?? '',
            ];

            $data = $this->auditService->getLogs($filters, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
