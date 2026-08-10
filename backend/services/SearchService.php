<?php
namespace Backend\Services;

class SearchService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Perform global search across all entities.
     */
    public function search($query, $userId = null) {
        if (empty(trim($query))) {
            return [];
        }

        $term = "%" . $query . "%";
        $results = [];

        // Check user role if session is available
        $roleId = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 1;

        // 1. Assessments (respecting record-level security)
        $sqlAss = "SELECT id, title as name, 'Assessment' as type, 'assessments' as page 
                   FROM privacy_assessments 
                   WHERE (title LIKE ?)";
        $paramsAss = [$term];
        if ($userId !== null && $roleId != 1 && $roleId != 2) {
            $sqlAss .= " AND (assigned_to = ? OR reviewer_id = ?)";
            $paramsAss[] = $userId;
            $paramsAss[] = $userId;
        }
        $stmt = $this->pdo->prepare($sqlAss);
        $stmt->execute($paramsAss);
        $results = array_merge($results, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        // 2. DSRs (respecting ownership)
        $sqlDsr = "SELECT dr.id, dr.request_id_code as name, 'DSR' as type, 'dsr-management' as page 
                   FROM data_requests dr
                   JOIN data_subjects ds ON dr.data_subject_id = ds.id
                   WHERE (dr.request_id_code LIKE ? OR ds.identifier_hash LIKE ?)";
        $paramsDsr = [$term, $term];
        if ($userId !== null && $roleId != 1 && $roleId != 2) {
            $sqlDsr .= " AND dr.assigned_to = ?";
            $paramsDsr[] = $userId;
        }
        $stmt = $this->pdo->prepare($sqlDsr);
        $stmt->execute($paramsDsr);
        $results = array_merge($results, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        // 3. Incidents (respecting ownership)
        $sqlInc = "SELECT id, summary as name, 'Incident' as type, 'incident-management' as page 
                   FROM incidents 
                   WHERE (summary LIKE ? OR description LIKE ?)";
        $paramsInc = [$term, $term];
        if ($userId !== null && $roleId != 1 && $roleId != 2) {
            $sqlInc .= " AND created_by = ?";
            $paramsInc[] = $userId;
        }
        $stmt = $this->pdo->prepare($sqlInc);
        $stmt->execute($paramsInc);
        $results = array_merge($results, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        // 4. Risks
        $sqlRisk = "SELECT id, title as name, 'Risk' as type, 'risk-register' as page 
                    FROM risk_register 
                    WHERE (title LIKE ? OR mitigation LIKE ?)";
        $stmt = $this->pdo->prepare($sqlRisk);
        $stmt->execute([$term, $term]);
        $results = array_merge($results, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        // 5. Policies
        $sqlPol = "SELECT id, policy_name as name, 'Policy' as type, 'policies' as page 
                   FROM privacy_policies 
                   WHERE (policy_name LIKE ?)";
        $paramsPol = [$term];
        $stmt = $this->pdo->prepare($sqlPol);
        $stmt->execute($paramsPol);
        $results = array_merge($results, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        // 6. Vendors
        $sqlVend = "SELECT id, name, 'Vendor' as type, 'vendor-management' as page 
                    FROM vendors 
                    WHERE (name LIKE ? OR service_type LIKE ?) AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sqlVend);
        $stmt->execute([$term, $term]);
        $results = array_merge($results, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        // 7. Tasks
        $sqlTask = "SELECT id, title as name, 'Task' as type, 'my-tasks' as page 
                    FROM tasks 
                    WHERE (title LIKE ? OR description LIKE ?)";
        $paramsTask = [$term, $term];
        if ($userId !== null && $roleId != 1) {
            $sqlTask .= " AND assigned_to = ?";
            $paramsTask[] = $userId;
        }
        $stmt = $this->pdo->prepare($sqlTask);
        $stmt->execute($paramsTask);
        $results = array_merge($results, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        return $results;
    }
}
