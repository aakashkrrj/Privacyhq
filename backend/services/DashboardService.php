<?php
/**
 * Dashboard Service
 *
 * Handles data aggregation for the dashboard UI.
 */
require_once __DIR__ . '/../../includes/db_helper.php';

class DashboardService {
    private $db;

    // Constants for Consents
    public const CONSENT_STATUS_ACTIVE = 'opt_in';

    // Constants for Data Requests
    public const REQUEST_STATUS_PENDING = 'open'; // Treating 'open' as pending
    public const REQUEST_PRIORITY_HIGH = 'High';
    public const REQUEST_PRIORITY_URGENT = 'Urgent';

    // Constants for Assessments
    public const ASSESSMENT_STATUS_APPROVED = 'Approved';
    public const VENDOR_STATUS_COMPLIANT = 'Compliant';

    // Constants for Risk Levels
    public const RISK_LOW = 0;
    public const RISK_MEDIUM = 50;
    public const RISK_HIGH = 80;
    public const RISK_CRITICAL = 95;

    public function __construct() {
        $this->db = new DBHelper();
    }

    /**
     * Get aggregate statistics for the dashboard.
     *
     * @return array Associative array of dashboard stats.
     */
    public function getDashboardStats(): array {
        return [
            'active_consents' => $this->getActiveConsentsCount(),
            'pending_requests' => $this->getPendingRequestsCount(),
            'completed_assessments' => $this->getCompletedAssessmentsCount(),
            'high_risk_vendors' => $this->getHighRiskVendorsCount(),
            'active_incidents' => $this->getActiveIncidentsCount(),
            'vendor_risk_label' => $this->getOverallVendorRiskLabel()
        ];
    }

    /**
     * Get overall compliance scores.
     *
     * @return array
     */
    public function getComplianceOverview(): array {
        // Privacy Score: We don't have completion_percentage in privacy_assessments. 
        // We will mock this or return 100 based on completed assessments ratio.
        $sql = "SELECT 
                    COUNT(*) as total_assessments,
                    SUM(CASE WHEN s.status_name = :approved THEN 1 ELSE 0 END) as approved_assessments
                FROM privacy_assessments pa
                JOIN assessment_statuses s ON pa.status_id = s.id";
        
        $result = $this->db->fetchOne($sql, ['approved' => self::ASSESSMENT_STATUS_APPROVED]);
        
        $total = (int)($result['total_assessments'] ?? 0);
        $approved = (int)($result['approved_assessments'] ?? 0);
        
        $dpdpScore = $total > 0 ? round(($approved / $total) * 100, 1) : 100;
        
        // Let's use average vendor risk for Privacy Score inverted
        $vendorRes = $this->db->fetchOne("SELECT AVG(risk_score) as avg_risk FROM vendor_assessments");
        $avgRisk = (float)($vendorRes['avg_risk'] ?? 0);
        $privacyScore = 100 - $avgRisk;

        return [
            'privacy_score' => round($privacyScore, 1),
            'dpdp_score' => $dpdpScore
        ];
    }

    /**
     * Get recent consents.
     *
     * @param int $limit
     * @return array
     */
    public function getRecentConsents(int $limit = 5): array {
        $limit = max(1, $limit);
        $sql = "SELECT c.id, c.status, c.granted_at, p.purpose_name as purpose_name, ds.identifier_hash as email 
                FROM consents c
                JOIN consent_purposes p ON c.consent_purpose_id = p.id
                JOIN data_subjects ds ON c.data_subject_id = ds.id
                ORDER BY c.created_at DESC LIMIT $limit";
        $result = $this->db->fetchAll($sql);
        return $result ?: [];
    }

    /**
     * Get recent requests.
     *
     * @param int $limit
     * @return array
     */
    public function getRecentRequests(int $limit = 5): array {
        $limit = max(1, $limit);
        $sql = "SELECT request_id_code, request_type as type, status, priority, created_at 
                FROM data_requests 
                ORDER BY created_at DESC LIMIT $limit";
        $result = $this->db->fetchAll($sql);
        return $result ?: [];
    }

    /**
     * Get recent assessments.
     * We join assessment_statuses to get the string representation, and mock risk/completion for UI since they don't exist.
     *
     * @param int $limit
     * @return array
     */
    public function getRecentAssessments(int $limit = 5): array {
        $limit = max(1, $limit);
        $sql = "SELECT pa.title, 'N/A' as risk_level, s.status_name as status, 100 as completion_percentage, pa.created_at 
                FROM privacy_assessments pa
                JOIN assessment_statuses s ON pa.status_id = s.id
                ORDER BY pa.created_at DESC LIMIT $limit";
        $result = $this->db->fetchAll($sql);
        return $result ?: [];
    }

    /**
     * Get recent audit logs.
     *
     * @param int $limit
     * @return array
     */
    public function getRecentAuditLogs(int $limit = 5): array {
        $limit = max(1, $limit);
        $sql = "SELECT module, action, created_at 
                FROM audit_logs 
                ORDER BY created_at DESC LIMIT $limit";
        $result = $this->db->fetchAll($sql);
        return $result ?: [];
    }

    // --- Private Helper Methods ---

    private function getActiveConsentsCount(): int {
        $sql = "SELECT COUNT(*) as count FROM consents WHERE status = :status";
        $result = $this->db->fetchOne($sql, ['status' => self::CONSENT_STATUS_ACTIVE]);
        return (int)($result['count'] ?? 0);
    }

    private function getPendingRequestsCount(): int {
        $sql = "SELECT COUNT(*) as count FROM data_requests WHERE status = :status";
        $result = $this->db->fetchOne($sql, ['status' => self::REQUEST_STATUS_PENDING]);
        return (int)($result['count'] ?? 0);
    }

    private function getCompletedAssessmentsCount(): int {
        $sql = "SELECT COUNT(*) as count 
                FROM privacy_assessments pa
                JOIN assessment_statuses s ON pa.status_id = s.id
                WHERE s.status_name = :status";
        $result = $this->db->fetchOne($sql, ['status' => self::ASSESSMENT_STATUS_APPROVED]);
        return (int)($result['count'] ?? 0);
    }

    private function getHighRiskVendorsCount(): int {
        $sql = "SELECT COUNT(*) as count FROM vendor_assessments WHERE risk_score >= :risk_high";
        $result = $this->db->fetchOne($sql, ['risk_high' => self::RISK_HIGH]);
        return (int)($result['count'] ?? 0);
    }

    private function getActiveIncidentsCount(): int {
        $sql = "SELECT COUNT(*) as count FROM data_requests 
                WHERE status = :status 
                AND (priority = :prio_high OR priority = :prio_urgent)";
        $result = $this->db->fetchOne($sql, [
            'status' => self::REQUEST_STATUS_PENDING,
            'prio_high' => self::REQUEST_PRIORITY_HIGH,
            'prio_urgent' => self::REQUEST_PRIORITY_URGENT
        ]);
        return (int)($result['count'] ?? 0);
    }

    private function getOverallVendorRiskLabel(): string {
        $sql = "SELECT MAX(risk_score) as max_risk FROM vendor_assessments";
        $result = $this->db->fetchOne($sql);
        $maxRisk = (int)($result['max_risk'] ?? 0);

        if ($maxRisk >= self::RISK_CRITICAL) {
            return 'Critical';
        } elseif ($maxRisk >= self::RISK_HIGH) {
            return 'High';
        } elseif ($maxRisk >= self::RISK_MEDIUM) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
}
