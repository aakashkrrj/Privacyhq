<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class ConsentController extends BaseController {
    private $consentService;

    public function __construct($consentService) {
        $this->consentService = $consentService;
    }

    public function create() {
        $this->checkPermission('manage_consents');
        try {
            $email = trim($_POST['user_identifier'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $status = trim($_POST['status'] ?? 'Granted');
            $collectionMethod = trim($_POST['collection_method'] ?? 'web_portal');
            $source = trim($_POST['source'] ?? 'Manual');
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
            $expiresAt = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;

            $this->consentService->createConsent($email, $category, $status, $this->getUserId(), $collectionMethod, $source, $ipAddress, $userAgent, $expiresAt);
            ApiResponse::success('Consent logged successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function revoke() {
        $this->checkPermission('manage_consents');
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
        $this->checkPermission('view_dashboard');
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
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->consentService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function history() {
        $this->checkPermission('view_dashboard');
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) {
                throw new \Exception("Valid Consent ID is required.");
            }
            $data = $this->consentService->getConsentHistory($id);
            ApiResponse::success('Consent history fetched successfully', $data);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updatePreference() {
        $this->checkPermission('manage_consents');
        try {
            $id = filter_input(INPUT_POST, 'consent_id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new \Exception("Valid Consent ID is required.");
            }
            $status = trim($_POST['status'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            $this->consentService->updatePreference($id, $status, $this->getUserId(), $reason);
            ApiResponse::success('Consent preference updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
