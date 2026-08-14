<?php
namespace Backend\Services;

class CookieGovernanceService {
    private $pdo;
    private $cookieModel;
    private $consentService;

    public function __construct(\PDO $pdo, $cookieModel = null, $consentService = null) {
        $this->pdo = $pdo;
        if ($cookieModel instanceof \Backend\Models\CookieGovernance) {
            $this->cookieModel = $cookieModel;
            $this->consentService = $consentService;
        } else if ($cookieModel instanceof \Backend\Services\ConsentService) {
            $this->consentService = $cookieModel;
            $this->cookieModel = new \Backend\Models\CookieGovernance($pdo);
        } else {
            $this->cookieModel = new \Backend\Models\CookieGovernance($pdo);
            $this->consentService = $consentService;
        }
    }

    public function getDashboard() {
        return $this->cookieModel->getDashboardMetrics();
    }

    public function getCookies($search = '', $category = '', $partyType = '', $status = '', $riskLevel = '', $provider = '', $sortBy = 'id', $sortOrder = 'DESC', $page = 1, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->cookieModel->getCookies($search, $category, $partyType, $status, $riskLevel, $provider, $sortBy, $sortOrder, $pageSize, $offset);
    }

    public function createCookie($data, $userId) {
        $name = trim($data['name'] ?? '');
        $domain = trim($data['domain'] ?? 'privacyhq.com');
        $categoryId = (int)($data['category_id'] ?? 1);
        $provider = trim($data['provider'] ?? $domain);
        $partyType = trim($data['party_type'] ?? 'first_party');
        $riskLevel = trim($data['risk_level'] ?? 'low');
        $purpose = trim($data['purpose'] ?? '');
        $retention = trim($data['retention'] ?? 'Session');
        $status = trim($data['status'] ?? 'active');

        if (empty($name) || empty($domain)) {
            throw new \Exception("Cookie name and domain are required.");
        }

        try {
            $this->pdo->beginTransaction();
            $cookieId = $this->cookieModel->createCookie($name, $domain, $categoryId, $provider, $partyType, $riskLevel, $purpose, $retention, $status);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Cookie Governance', 'Create Cookie', $userId, $cookieId, null, json_encode(['name' => $name, 'domain' => $domain]));
            }

            $this->pdo->commit();
            return $cookieId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateCookie($id, $data, $userId) {
        $name = trim($data['name'] ?? '');
        $domain = trim($data['domain'] ?? '');
        $categoryId = (int)($data['category_id'] ?? 1);
        $provider = trim($data['provider'] ?? '');
        $partyType = trim($data['party_type'] ?? 'first_party');
        $riskLevel = trim($data['risk_level'] ?? 'low');
        $purpose = trim($data['purpose'] ?? '');
        $retention = trim($data['retention'] ?? 'Session');
        $status = trim($data['status'] ?? 'active');

        if (empty($id) || empty($name) || empty($domain)) {
            throw new \Exception("Cookie ID, name, and domain are required.");
        }

        $existing = $this->cookieModel->getCookieById($id);
        if (!$existing) {
            throw new \Exception("Cookie not found.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->cookieModel->updateCookie($id, $name, $domain, $categoryId, $provider, $partyType, $riskLevel, $purpose, $retention, $status);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Cookie Governance', 'Update Cookie', $userId, $id, json_encode(['name' => $existing['name'], 'status' => $existing['status']]), json_encode(['name' => $name, 'status' => $status]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteCookie($id, $userId) {
        $existing = $this->cookieModel->getCookieById($id);
        if (!$existing) {
            throw new \Exception("Cookie not found.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->cookieModel->deleteCookie($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Cookie Governance', 'Delete Cookie', $userId, $id, json_encode(['name' => $existing['name']]), null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getCategories() {
        return $this->cookieModel->getCategories();
    }

    public function createCategory($data, $userId) {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $isNecessary = !empty($data['is_necessary']) ? 1 : 0;

        if (empty($name)) {
            throw new \Exception("Category name is required.");
        }

        try {
            $this->pdo->beginTransaction();
            $catId = $this->cookieModel->createCategory($name, $description, $isNecessary);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Cookie Governance', 'Create Category', $userId, $catId, null, json_encode(['name' => $name]));
            }

            $this->pdo->commit();
            return $catId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateCategory($id, $data, $userId) {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $isNecessary = !empty($data['is_necessary']) ? 1 : 0;

        if (empty($id) || empty($name)) {
            throw new \Exception("Category ID and name are required.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->cookieModel->updateCategory($id, $name, $description, $isNecessary);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Cookie Governance', 'Update Category', $userId, $id, null, json_encode(['name' => $name]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteCategory($id, $userId) {
        try {
            $this->pdo->beginTransaction();
            $this->cookieModel->deleteCategory($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Cookie Governance', 'Delete Category', $userId, $id, null, null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function reassignCookiesCategory($cookieIds, $targetCategoryId, $userId) {
        if (empty($cookieIds) || empty($targetCategoryId)) {
            throw new \Exception("Please select cookies and a target category.");
        }
        $res = $this->cookieModel->reassignCookiesCategory($cookieIds, $targetCategoryId);
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Cookie Governance', 'Reassign Category', $userId, $targetCategoryId, null, json_encode(['cookies' => $cookieIds]));
        }
        return $res;
    }

    // Scanner Control Engine
    public function getScannerState($domain = 'privacyhq.com') {
        return $this->cookieModel->getLatestScan($domain);
    }

    public function controlScan($action, $domain = 'privacyhq.com', $userId = null) {
        $scan = $this->cookieModel->getLatestScan($domain);

        try {
            $this->pdo->beginTransaction();

            if ($action === 'start') {
                $this->cookieModel->updateScanStatus($scan['id'], 'scanning', 10, 5, 2, 2);
                if (function_exists('log_audit_event')) {
                    log_audit_event($this->pdo, 'Cookie Governance', 'Start Scan', $userId, $scan['id'], null, $domain);
                }
            } else if ($action === 'pause') {
                $this->cookieModel->updateScanStatus($scan['id'], 'paused');
                if (function_exists('log_audit_event')) {
                    log_audit_event($this->pdo, 'Cookie Governance', 'Pause Scan', $userId, $scan['id'], null, null);
                }
            } else if ($action === 'resume') {
                $this->cookieModel->updateScanStatus($scan['id'], 'scanning');
                if (function_exists('log_audit_event')) {
                    log_audit_event($this->pdo, 'Cookie Governance', 'Resume Scan', $userId, $scan['id'], null, null);
                }
            } else if ($action === 'cancel') {
                $this->cookieModel->updateScanStatus($scan['id'], 'cancelled', 0, 0, 0, 0);
                if (function_exists('log_audit_event')) {
                    log_audit_event($this->pdo, 'Cookie Governance', 'Cancel Scan', $userId, $scan['id'], null, null);
                }
            } else if ($action === 'complete') {
                $countRes = $this->cookieModel->getCookies();
                $totalFound = $countRes['total'] ?: 12;
                $this->cookieModel->updateScanStatus($scan['id'], 'completed', 100, 48, $totalFound, 14);
            }

            $this->pdo->commit();
            return $this->cookieModel->getLatestScan($domain);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Consent Banner & Preferences
    public function getBannerConfig($domain = 'privacyhq.com') {
        return $this->cookieModel->getBannerConfig($domain);
    }

    public function updateBannerConfig($data, $userId) {
        $domain = trim($data['domain'] ?? 'privacyhq.com');
        $title = trim($data['banner_title'] ?? 'We Value Your Privacy');
        $text = trim($data['banner_text'] ?? 'We use cookies to enhance your experience.');
        $position = trim($data['position'] ?? 'bottom');
        $theme = trim($data['theme'] ?? 'light');
        $primaryColor = trim($data['primary_color'] ?? '#4F46E5');
        $bgColor = trim($data['background_color'] ?? '#FFFFFF');
        $textColor = trim($data['text_color'] ?? '#1F2937');
        $privacyUrl = trim($data['privacy_policy_url'] ?? '/privacy-policy.php');
        $cookieUrl = trim($data['cookie_policy_url'] ?? '/cookie-policy.php');
        $isActive = !empty($data['is_active']) ? 1 : 0;

        $res = $this->cookieModel->updateBannerConfig($domain, $title, $text, $position, $theme, $primaryColor, $bgColor, $textColor, $privacyUrl, $cookieUrl, $isActive);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Cookie Governance', 'Update Banner Config', $userId, null, null, json_encode(['domain' => $domain, 'position' => $position]));
        }

        return $res;
    }

    public function logConsent($choice, $categoriesAccepted, $userId = null, $ip = null, $ua = null) {
        $res = $this->cookieModel->logConsent($userId, $ip, $ua, $choice, $categoriesAccepted);
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Cookie Governance', 'Record Consent', $userId, null, null, json_encode(['choice' => $choice]));
        }
        return $res;
    }

    public function getConsentLogs($userId = null) {
        return $this->cookieModel->getConsentLogs($userId);
    }

    // Export Reports
    public function exportData($format = 'csv', $reportType = 'inventory', $search = '', $category = '') {
        $res = $this->cookieModel->getCookies($search, $category, '', '', '', '', 'id', 'DESC', 10000, 0);
        $items = $res['items'] ?? [];

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=Cookie_Governance_' . ucfirst($reportType) . '_' . date('Y-m-d_H-i') . '.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Cookie ID', 'Cookie Name', 'Domain', 'Category', 'Party Type', 'Provider', 'Risk Level', 'Purpose', 'Retention', 'Status']);
            foreach ($items as $r) {
                fputcsv($out, [
                    $r['id'],
                    $r['name'],
                    $r['domain'],
                    $r['category_name'] ?: 'Unclassified',
                    strtoupper(str_replace('_', ' ', $r['party_type'])),
                    $r['provider'],
                    strtoupper($r['risk_level']),
                    $r['purpose'],
                    $r['retention'],
                    strtoupper($r['status'])
                ]);
            }
            fclose($out);
            exit;
        } else {
            // PDF / Print HTML Output
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Cookie Governance Report - ' . ucfirst($reportType) . '</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:12px;text-align:left;} th{background:#f3f4f6;}</style></head><body>';
            echo '<h2>PrivacyHQ - Cookie Governance ' . ucfirst($reportType) . ' Report</h2>';
            echo '<p>Generated on: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($items) . '</p>';
            echo '<table><thead><tr><th>Name</th><th>Domain</th><th>Category</th><th>Party</th><th>Provider</th><th>Risk</th><th>Retention</th><th>Status</th></tr></thead><tbody>';
            foreach ($items as $r) {
                echo '<tr><td><code>' . htmlspecialchars($r['name']) . '</code></td><td>' . htmlspecialchars($r['domain']) . '</td><td>' . htmlspecialchars($r['category_name'] ?: 'Unclassified') . '</td><td>' . htmlspecialchars(strtoupper(str_replace('_', ' ', $r['party_type']))) . '</td><td>' . htmlspecialchars($r['provider']) . '</td><td>' . htmlspecialchars(strtoupper($r['risk_level'])) . '</td><td>' . htmlspecialchars($r['retention']) . '</td><td>' . htmlspecialchars(strtoupper($r['status'])) . '</td></tr>';
            }
            echo '</tbody></table><script>window.print();</script></body></html>';
            exit;
        }
    }

    // Domain & Scan Extensions
    public function addDomain($domainName, $description, $userId = 1) {
        if (empty($domainName)) {
            throw new \Exception("Domain name cannot be empty.");
        }
        $domainName = strtolower(trim(parse_url($domainName, PHP_URL_HOST) ?: $domainName));

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("INSERT INTO cookie_domains (domain_name, description) VALUES (?, ?)");
            $stmt->execute([$domainName, $description]);
            $domainId = $this->pdo->lastInsertId();

            $stmtBanner = $this->pdo->prepare("
                INSERT INTO cookie_banner_configs (domain, banner_title, banner_text) 
                VALUES (?, 'Cookie Consent Preferences', 'We use cookies to enhance your experience. Configure your preferences below.')
            ");
            $stmtBanner->execute([$domainName]);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Cookie Governance', 'Add Website', $userId, $domainId, null, json_encode($domainName));
            }

            $this->pdo->commit();
            return $domainId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getDomains() {
        try {
            $stmt = $this->pdo->query("
                SELECT d.*, 
                       (SELECT COUNT(*) FROM cookies WHERE domain = d.domain_name AND deleted_at IS NULL) as cookie_count,
                       (SELECT status FROM cookie_scans WHERE domain = d.domain_name ORDER BY id DESC LIMIT 1) as last_scan_status,
                       (SELECT updated_at FROM cookie_scans WHERE domain = d.domain_name ORDER BY id DESC LIMIT 1) as last_scan_time
                FROM cookie_domains d
                ORDER BY d.domain_name ASC
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function updateClassification($cookieId, $category, $description, $userId = 1) {
        $existing = $this->cookieModel->getCookieById($cookieId);
        if (!$existing) {
            throw new \Exception("Cookie record not found.");
        }

        $stmtCat = $this->pdo->prepare("SELECT id FROM cookie_categories WHERE name = ? LIMIT 1");
        $stmtCat->execute([$category]);
        $catId = $stmtCat->fetchColumn() ?: 5;

        $stmtUpdate = $this->pdo->prepare("UPDATE cookies SET category_id = ?, purpose = ? WHERE id = ?");
        $stmtUpdate->execute([$catId, $description, $cookieId]);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Cookie Governance', 'Classification Changed', $userId, $cookieId, json_encode($existing['category_name']), json_encode($category));
        }

        return true;
    }

    public function saveConsentPreferences($email, $categories, $userId = 1, $ipAddress = null, $userAgent = null) {
        return $this->logConsent('custom', $categories, $userId, $ipAddress, $userAgent);
    }
}
