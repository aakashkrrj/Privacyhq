<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class ConsentController extends BaseController {
    private $consentService;

    public function __construct($consentService) {
        $this->consentService = $consentService;
    }

    private function respond($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    

    public function create() {
        try {
            $email = trim($_POST['user_identifier'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $status = trim($_POST['status'] ?? 'Granted');

            $this->consentService->createConsent($email, $category, $status, $this->getUserId());
            ApiResponse::success('Consent logged successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function revoke() {
        try {
            $id = filter_input(INPUT_POST, 'revoke_id', FILTER_VALIDATE_INT);
            if (!$id) {
                // Check if it was sent via GET parameters during fallback logic
                $id = filter_input(INPUT_GET, 'revoke_id', FILTER_VALIDATE_INT);
            }
            if (!$id) throw new \Exception("Invalid request ID");

            $this->consentService->revokeConsent($id, $this->getUserId());
            ApiResponse::success('Consent revoked successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listConsents() {
        try {
            $search = trim($_GET['search'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $categoryFilter = trim($_GET['category'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->consentService->getList($search, $statusFilter, $categoryFilter, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard() {
        try {
            $data = $this->consentService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
