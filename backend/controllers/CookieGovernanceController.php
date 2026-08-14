<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class CookieGovernanceController extends BaseController {
    private $cookieService;

    public function __construct($cookieService) {
        $this->cookieService = $cookieService;
        $this->checkPermission('view_dashboard');
    }

    public function index() {
        try {
            $data = $this->cookieService->getDashboard();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listCookies() {
        try {
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $partyType = trim($_GET['party_type'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $riskLevel = trim($_GET['risk_level'] ?? '');
            $provider = trim($_GET['provider'] ?? '');
            $sortBy = trim($_GET['sort_by'] ?? 'id');
            $sortOrder = trim($_GET['sort_order'] ?? 'DESC');
            $page = (int)($_GET['p'] ?? 1) ?: 1;
            $pageSize = (int)($_GET['limit'] ?? 10) ?: 10;

            $data = $this->cookieService->getCookies($search, $category, $partyType, $status, $riskLevel, $provider, $sortBy, $sortOrder, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function createCookie() {
        try {
            $cookieId = $this->cookieService->createCookie($_POST, $this->getUserId());
            ApiResponse::success('Cookie created successfully', ['id' => $cookieId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updateCookie() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Cookie ID");
            $this->cookieService->updateCookie($id, $_POST, $this->getUserId());
            ApiResponse::success('Cookie updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteCookie() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Cookie ID");
            $this->cookieService->deleteCookie($id, $this->getUserId());
            ApiResponse::success('Cookie deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listCategories() {
        try {
            $data = $this->cookieService->getCategories();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function createCategory() {
        try {
            $catId = $this->cookieService->createCategory($_POST, $this->getUserId());
            ApiResponse::success('Category created successfully', ['id' => $catId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updateCategory() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Category ID");
            $this->cookieService->updateCategory($id, $_POST, $this->getUserId());
            ApiResponse::success('Category updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteCategory() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Category ID");
            $this->cookieService->deleteCategory($id, $this->getUserId());
            ApiResponse::success('Category deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function reassignCategory() {
        try {
            $cookieIds = $_POST['cookie_ids'] ?? [];
            $targetCategoryId = (int)($_POST['category_id'] ?? 0);
            $this->cookieService->reassignCookiesCategory($cookieIds, $targetCategoryId, $this->getUserId());
            ApiResponse::success('Cookies reassigned successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function scanner() {
        try {
            $action = trim($_POST['action'] ?? ($_GET['action'] ?? 'status'));
            $domain = trim($_POST['domain'] ?? ($_GET['domain'] ?? 'privacyhq.com'));

            if ($action === 'status') {
                $data = $this->cookieService->getScannerState($domain);
            } else {
                $data = $this->cookieService->controlScan($action, $domain, $this->getUserId());
            }

            ApiResponse::success('Scanner status updated', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function banner() {
        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $domain = trim($_REQUEST['domain'] ?? 'privacyhq.com');

            if ($method === 'POST') {
                $this->cookieService->updateBannerConfig($_POST, $this->getUserId());
                ApiResponse::success('Banner configuration saved successfully');
            } else {
                $data = $this->cookieService->getBannerConfig($domain);
                ApiResponse::success('Success', $data);
            }
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function consent() {
        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($method === 'POST') {
                $choice = trim($_POST['choice'] ?? 'accept_all');
                $categories = $_POST['categories'] ?? [];
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Browser';
                $this->cookieService->logConsent($choice, $categories, $this->getUserId(), $ip, $ua);
                ApiResponse::success('Consent preference recorded');
            } else {
                $logs = $this->cookieService->getConsentLogs($this->getUserId());
                ApiResponse::success('Success', $logs);
            }
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export() {
        try {
            $format = strtolower(trim($_GET['format'] ?? 'csv'));
            $reportType = strtolower(trim($_GET['type'] ?? 'inventory'));
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');

            $this->cookieService->exportData($format, $reportType, $search, $category);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
