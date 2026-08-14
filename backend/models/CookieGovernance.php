<?php
namespace Backend\Models;

class CookieGovernance {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    // 1. Dashboard Metrics & Analytics
    public function getDashboardMetrics() {
        // Basic Counts
        $sql = "
            SELECT 
                COUNT(*) as total_cookies,
                SUM(IF(party_type = 'first_party', 1, 0)) as first_party,
                SUM(IF(party_type = 'third_party', 1, 0)) as third_party,
                SUM(IF(status = 'awaiting_review', 1, 0)) as awaiting_review,
                SUM(IF(status = 'active', 1, 0)) as active_count,
                SUM(IF(risk_level = 'high', 1, 0)) as high_risk,
                SUM(IF(risk_level = 'medium', 1, 0)) as medium_risk,
                SUM(IF(risk_level = 'low', 1, 0)) as low_risk
            FROM cookies
            WHERE deleted_at IS NULL
        ";
        $counts = $this->pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);

        $total = (int)($counts['total_cookies'] ?? 0);
        $active = (int)($counts['active_count'] ?? 0);
        $compliancePct = $total > 0 ? round(($active / $total) * 100, 1) . '%' : '100%';

        // Category Breakdown
        $catSql = "
            SELECT c.name as cat_name, COUNT(ck.id) as cookie_count
            FROM cookie_categories c
            LEFT JOIN cookies ck ON ck.category_id = c.id AND ck.deleted_at IS NULL
            GROUP BY c.id, c.name
        ";
        $catRows = $this->pdo->query($catSql)->fetchAll(\PDO::FETCH_ASSOC);

        $categories = [
            'Necessary' => 0,
            'Analytics' => 0,
            'Marketing' => 0,
            'Preferences' => 0,
            'Unclassified' => 0
        ];
        foreach ($catRows as $cr) {
            $categories[$cr['cat_name']] = (int)$cr['cookie_count'];
        }

        // Recent Scan Summary
        $scanSql = "SELECT * FROM cookie_scans ORDER BY id DESC LIMIT 1";
        $recentScan = $this->pdo->query($scanSql)->fetch(\PDO::FETCH_ASSOC) ?: [
            'domain' => 'privacyhq.com',
            'status' => 'completed',
            'cookies_found' => $total,
            'last_scan_at' => date('Y-m-d H:i:s')
        ];

        // Opt-In Rate from Consent Logs
        $consentSql = "
            SELECT 
                COUNT(*) as total_consents,
                SUM(IF(consent_choice IN ('accept_all', 'custom'), 1, 0)) as opt_ins
            FROM cookie_consent_logs
        ";
        $consentData = $this->pdo->query($consentSql)->fetch(\PDO::FETCH_ASSOC);
        $totalConsents = (int)($consentData['total_consents'] ?? 0);
        $optIns = (int)($consentData['opt_ins'] ?? 0);
        $optInRate = $totalConsents > 0 ? round(($optIns / $totalConsents) * 100, 1) . '%' : '100%';

        // Recently Detected Cookies (Latest 5)
        $recentCookiesSql = "
            SELECT ck.*, c.name as category_name
            FROM cookies ck
            LEFT JOIN cookie_categories c ON ck.category_id = c.id
            WHERE ck.deleted_at IS NULL
            ORDER BY ck.id DESC LIMIT 5
        ";
        $recentCookies = $this->pdo->query($recentCookiesSql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'metrics' => [
                'total_cookies' => $total,
                'first_party' => (int)($counts['first_party'] ?? 0),
                'third_party' => (int)($counts['third_party'] ?? 0),
                'awaiting_review' => (int)($counts['awaiting_review'] ?? 0),
                'compliance_pct' => $compliancePct,
                'opt_in_rate' => $optInRate
            ],
            'categories' => $categories,
            'risk_summary' => [
                'high' => (int)($counts['high_risk'] ?? 0),
                'medium' => (int)($counts['medium_risk'] ?? 0),
                'low' => (int)($counts['low_risk'] ?? 0)
            ],
            'recent_scan' => $recentScan,
            'recent_cookies' => $recentCookies
        ];
    }

    // 2. Cookies Listing, Search, Filter, Sort & Pagination
    public function getCookies($search = '', $category = '', $partyType = '', $status = '', $riskLevel = '', $provider = '', $sortBy = 'id', $sortOrder = 'DESC', $limit = 10, $offset = 0) {
        $whereClauses = ["ck.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(ck.name LIKE ? OR ck.domain LIKE ? OR ck.provider LIKE ? OR ck.purpose LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }
        if (!empty($category)) {
            if (is_numeric($category)) {
                $whereClauses[] = "ck.category_id = ?";
                $params[] = (int)$category;
            } else {
                $whereClauses[] = "c.name = ?";
                $params[] = $category;
            }
        }
        if (!empty($partyType)) {
            $whereClauses[] = "ck.party_type = ?";
            $params[] = $partyType;
        }
        if (!empty($status)) {
            $whereClauses[] = "ck.status = ?";
            $params[] = $status;
        }
        if (!empty($riskLevel)) {
            $whereClauses[] = "ck.risk_level = ?";
            $params[] = $riskLevel;
        }
        if (!empty($provider)) {
            $whereClauses[] = "ck.provider LIKE ?";
            $params[] = "%$provider%";
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count Total
        $countSql = "SELECT COUNT(*) FROM cookies ck LEFT JOIN cookie_categories c ON ck.category_id = c.id $whereSql";
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Allowed Sorts
        $allowedSort = ['id', 'name', 'domain', 'party_type', 'risk_level', 'status', 'created_at'];
        $sortCol = in_array($sortBy, $allowedSort) ? "ck.$sortBy" : "ck.id";
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT ck.*, c.name as category_name, c.is_necessary
            FROM cookies ck
            LEFT JOIN cookie_categories c ON ck.category_id = c.id
            $whereSql
            ORDER BY $sortCol $sortDir
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function getCookieById($id) {
        $stmt = $this->pdo->prepare("
            SELECT ck.*, c.name as category_name 
            FROM cookies ck 
            LEFT JOIN cookie_categories c ON ck.category_id = c.id 
            WHERE ck.id = ? AND ck.deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createCookie($name, $domain, $categoryId, $provider, $partyType = 'first_party', $riskLevel = 'low', $purpose = null, $retention = 'Session', $status = 'awaiting_review') {
        // Prevent duplicate cookie name + domain
        $check = $this->pdo->prepare("SELECT id FROM cookies WHERE name = ? AND domain = ? AND deleted_at IS NULL LIMIT 1");
        $check->execute([$name, $domain]);
        if ($check->fetchColumn()) {
            throw new \Exception("A cookie with name '{$name}' on domain '{$domain}' already exists.");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO cookies (name, domain, category_id, provider, party_type, risk_level, purpose, retention, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $domain, $categoryId ?: null, $provider ?: $domain, $partyType, $riskLevel, $purpose, $retention ?: 'Session', $status]);
        return $this->pdo->lastInsertId();
    }

    public function updateCookie($id, $name, $domain, $categoryId, $provider, $partyType, $riskLevel, $purpose, $retention, $status) {
        $check = $this->pdo->prepare("SELECT id FROM cookies WHERE name = ? AND domain = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
        $check->execute([$name, $domain, $id]);
        if ($check->fetchColumn()) {
            throw new \Exception("Another cookie with name '{$name}' on domain '{$domain}' already exists.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE cookies 
            SET name = ?, domain = ?, category_id = ?, provider = ?, party_type = ?, risk_level = ?, purpose = ?, retention = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$name, $domain, $categoryId ?: null, $provider, $partyType, $riskLevel, $purpose, $retention, $status, $id]);
    }

    public function deleteCookie($id) {
        $stmt = $this->pdo->prepare("UPDATE cookies SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // 3. Cookie Categories CRUD & Reassignment
    public function getCategories() {
        $sql = "
            SELECT c.*, COUNT(ck.id) as cookie_count
            FROM cookie_categories c
            LEFT JOIN cookies ck ON ck.category_id = c.id AND ck.deleted_at IS NULL
            GROUP BY c.id
            ORDER BY c.id ASC
        ";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createCategory($name, $description = null, $isNecessary = 0) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $check = $this->pdo->prepare("SELECT id FROM cookie_categories WHERE name = ? OR slug = ? LIMIT 1");
        $check->execute([$name, $slug]);
        if ($check->fetchColumn()) {
            throw new \Exception("Category '{$name}' already exists.");
        }

        $stmt = $this->pdo->prepare("INSERT INTO cookie_categories (name, slug, description, is_necessary) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $isNecessary ? 1 : 0]);
        return $this->pdo->lastInsertId();
    }

    public function updateCategory($id, $name, $description = null, $isNecessary = 0) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $check = $this->pdo->prepare("SELECT id FROM cookie_categories WHERE (name = ? OR slug = ?) AND id != ? LIMIT 1");
        $check->execute([$name, $slug, $id]);
        if ($check->fetchColumn()) {
            throw new \Exception("Category '{$name}' already exists.");
        }

        $stmt = $this->pdo->prepare("UPDATE cookie_categories SET name = ?, slug = ?, description = ?, is_necessary = ? WHERE id = ?");
        return $stmt->execute([$name, $slug, $description, $isNecessary ? 1 : 0, $id]);
    }

    public function deleteCategory($id) {
        // Reassign existing cookies to Unclassified (id = 5) before deleting
        $unclassified = $this->pdo->query("SELECT id FROM cookie_categories WHERE name = 'Unclassified' LIMIT 1")->fetchColumn();
        $unclassifiedId = $unclassified ?: 5;

        $reassign = $this->pdo->prepare("UPDATE cookies SET category_id = ? WHERE category_id = ?");
        $reassign->execute([$unclassifiedId, $id]);

        $stmt = $this->pdo->prepare("DELETE FROM cookie_categories WHERE id = ? AND is_necessary = 0");
        return $stmt->execute([$id]);
    }

    public function reassignCookiesCategory($cookieIds, $targetCategoryId) {
        if (empty($cookieIds)) return false;
        $inQuery = implode(',', array_map('intval', (array)$cookieIds));
        $stmt = $this->pdo->prepare("UPDATE cookies SET category_id = ?, updated_at = NOW() WHERE id IN ($inQuery) AND deleted_at IS NULL");
        return $stmt->execute([$targetCategoryId]);
    }

    // 4. Scanner Simulation & State Machine
    public function getLatestScan($domain = 'privacyhq.com') {
        $stmt = $this->pdo->prepare("SELECT * FROM cookie_scans WHERE domain = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$domain]);
        $scan = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$scan) {
            $stmtInit = $this->pdo->prepare("INSERT INTO cookie_scans (domain, status, progress_percentage, pages_scanned, cookies_found, time_taken_seconds, last_scan_at, next_scan_at) VALUES (?, 'completed', 100, 48, 7, 12, NOW(), NOW() + INTERVAL 7 DAY)");
            $stmtInit->execute([$domain]);
            $stmt->execute([$domain]);
            $scan = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        return $scan;
    }

    public function updateScanStatus($scanId, $status, $progress = null, $pagesScanned = null, $cookiesFound = null, $timeTaken = null) {
        $fields = ["status = ?"];
        $params = [$status];

        if ($progress !== null) {
            $fields[] = "progress_percentage = ?";
            $params[] = (int)$progress;
        }
        if ($pagesScanned !== null) {
            $fields[] = "pages_scanned = ?";
            $params[] = (int)$pagesScanned;
        }
        if ($cookiesFound !== null) {
            $fields[] = "cookies_found = ?";
            $params[] = (int)$cookiesFound;
        }
        if ($timeTaken !== null) {
            $fields[] = "time_taken_seconds = ?";
            $params[] = (int)$timeTaken;
        }
        if ($status === 'completed') {
            $fields[] = "last_scan_at = NOW()";
            $fields[] = "next_scan_at = NOW() + INTERVAL 7 DAY";
        }

        $fields[] = "updated_at = NOW()";
        $params[] = $scanId;

        $sql = "UPDATE cookie_scans SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // 5. Consent Banner Config & Consent Logs
    public function getBannerConfig($domain = 'privacyhq.com') {
        $stmt = $this->pdo->prepare("SELECT * FROM cookie_banner_configs WHERE domain = ? LIMIT 1");
        $stmt->execute([$domain]);
        $cfg = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$cfg) {
            $this->pdo->prepare("
                INSERT INTO cookie_banner_configs (domain, banner_title, banner_text, position, theme, primary_color, background_color, text_color, privacy_policy_url, cookie_policy_url, is_active)
                VALUES (?, 'We Value Your Privacy', 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our web traffic. By clicking Accept All, you consent to our use of cookies.', 'bottom', 'light', '#4F46E5', '#FFFFFF', '#1F2937', '/privacy-policy.php', '/cookie-policy.php', 1)
            ")->execute([$domain]);

            $stmt->execute([$domain]);
            $cfg = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        return $cfg;
    }

    public function updateBannerConfig($domain, $title, $text, $position, $theme, $primaryColor, $bgColor, $textColor, $privacyUrl, $cookieUrl, $isActive = 1) {
        $stmt = $this->pdo->prepare("
            INSERT INTO cookie_banner_configs (domain, banner_title, banner_text, position, theme, primary_color, background_color, text_color, privacy_policy_url, cookie_policy_url, is_active, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                banner_title = VALUES(banner_title),
                banner_text = VALUES(banner_text),
                position = VALUES(position),
                theme = VALUES(theme),
                primary_color = VALUES(primary_color),
                background_color = VALUES(background_color),
                text_color = VALUES(text_color),
                privacy_policy_url = VALUES(privacy_policy_url),
                cookie_policy_url = VALUES(cookie_policy_url),
                is_active = VALUES(is_active),
                updated_at = NOW()
        ");
        return $stmt->execute([$domain, $title, $text, $position, $theme, $primaryColor, $bgColor, $textColor, $privacyUrl, $cookieUrl, $isActive ? 1 : 0]);
    }

    public function logConsent($userId, $ipAddress, $userAgent, $consentChoice, $categoriesAccepted = []) {
        $catsJson = is_array($categoriesAccepted) ? json_encode($categoriesAccepted) : $categoriesAccepted;
        $stmt = $this->pdo->prepare("
            INSERT INTO cookie_consent_logs (user_id, ip_address, user_agent, consent_choice, categories_accepted, consent_version, created_at)
            VALUES (?, ?, ?, ?, ?, 'v1.0', NOW())
        ");
        return $stmt->execute([$userId ?: null, $ipAddress, $userAgent, $consentChoice, $catsJson]);
    }

    public function getConsentLogs($userId = null, $limit = 20) {
        $where = $userId ? "WHERE user_id = " . (int)$userId : "";
        $sql = "SELECT * FROM cookie_consent_logs $where ORDER BY id DESC LIMIT " . (int)$limit;
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
