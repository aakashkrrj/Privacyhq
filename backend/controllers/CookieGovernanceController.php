<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class CookieGovernanceController extends BaseController {
    private $cookieService;

    public function __construct($cookieService) {
        $this->cookieService = $cookieService;
    }

    public function getDashboard() {
        $this->checkPermission('view_cookie_governance');
        try {
            $domainId = $_GET['domain_id'] ?? null;
            $data = $this->cookieService->getDashboardMetrics($domainId);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listDomains() {
        $this->checkPermission('view_cookie_governance');
        try {
            $data = $this->cookieService->getDomains();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function addDomain() {
        $this->checkPermission('manage_cookie_sources');
        try {
            $domain = $_POST['domain_name'] ?? null;
            $desc = $_POST['description'] ?? '';
            $userId = $this->getUserId();

            $domainId = $this->cookieService->addDomain($domain, $desc, $userId);
            ApiResponse::success('Website domain registered successfully', ['domain_id' => $domainId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function scan() {
        $this->checkPermission('run_cookie_scans');
        try {
            $domainId = $_POST['domain_id'] ?? null;
            $forceMock = isset($_POST['force_mock']) && ($_POST['force_mock'] === '1' || $_POST['force_mock'] === 'true');
            $userId = $this->getUserId();

            if (empty($domainId)) {
                throw new \Exception("Domain ID is required.");
            }

            $this->cookieService->startScan($domainId, $userId, $forceMock);
            ApiResponse::success('Scan run completed successfully.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listCookies() {
        $this->checkPermission('view_cookie_governance');
        try {
            $domainId = $_GET['domain_id'] ?? null;
            $category = $_GET['category'] ?? null;
            $partyType = $_GET['party_type'] ?? null;
            $search = $_GET['search'] ?? null;
            $page = intval($_GET['page'] ?? 1);
            $pageSize = intval($_GET['page_size'] ?? 10);

            $data = $this->cookieService->getInventory($domainId, $category, $partyType, $search, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updateClassification() {
        $this->checkPermission('classify_cookies');
        try {
            $cookieId = $_POST['cookie_id'] ?? null;
            $category = $_POST['category'] ?? null;
            $description = $_POST['description'] ?? '';
            $userId = $this->getUserId();

            if (empty($cookieId) || empty($category)) {
                throw new \Exception("Cookie ID and Category classification are required.");
            }

            $this->cookieService->updateClassification($cookieId, $category, $description, $userId);
            ApiResponse::success('Cookie classification updated successfully.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getBannerConfig() {
        // Can be publicly accessible or user permissions
        try {
            $domainId = $_GET['domain_id'] ?? null;
            if (empty($domainId)) {
                throw new \Exception("Domain ID is required.");
            }
            $data = $this->cookieService->getBannerConfig($domainId);
            ApiResponse::success('Success', $data ?: new \stdClass());
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveBannerConfig() {
        $this->checkPermission('manage_cookie_banner');
        try {
            $domainId = $_POST['domain_id'] ?? null;
            $title = $_POST['banner_title'] ?? 'Cookie Preferences';
            $text = $_POST['banner_text'] ?? '';
            $lang = $_POST['language'] ?? 'en';
            $categories = $_POST['categories_presented'] ?? 'Essential,Functional,Performance,Analytics,Advertising';
            $acceptText = $_POST['accept_all_text'] ?? 'Accept All';
            $rejectText = $_POST['reject_all_text'] ?? 'Reject';
            $prefText = $_POST['preferences_text'] ?? 'Preferences';
            $color = $_POST['branding_color'] ?? '#005faa';
            $userId = $this->getUserId();

            if (empty($domainId)) {
                throw new \Exception("Domain ID is required.");
            }

            $this->cookieService->saveBannerConfig($domainId, $title, $text, $lang, $categories, $acceptText, $rejectText, $prefText, $color, $userId);
            ApiResponse::success('Banner configuration saved successfully.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveConsentPreferences() {
        try {
            $email = $_POST['email'] ?? null;
            $preferences = $_POST['preferences'] ?? null; // JSON string or array
            $userId = $this->getUserId();

            if (empty($email) || empty($preferences)) {
                throw new \Exception("Email and preferences are required.");
            }

            if (is_string($preferences)) {
                $preferences = json_decode($preferences, true);
            }

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $this->cookieService->saveConsentPreferences($email, $preferences, $userId, $ipAddress, $userAgent);
            ApiResponse::success('Preferences saved successfully.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
