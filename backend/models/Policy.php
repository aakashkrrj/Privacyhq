<?php
namespace Backend\Models;

class Policy {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create($name, $version, $status, $documentPath = null) {
        $effectiveDate = date('Y-m-d');
        $stmt = $this->pdo->prepare("
            INSERT INTO privacy_policies (policy_name, version, effective_date, status, document_path)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $version, $effectiveDate, strtolower($status), $documentPath]);
        return $this->pdo->lastInsertId();
    }

    public function getList($search = '', $status = '') {
        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "policy_name LIKE ?";
            $params[] = "%$search%";
        }
        if (!empty($status)) {
            $whereClauses[] = "status = ?";
            $params[] = strtolower($status);
        }

        $whereSql = '';
        if (count($whereClauses) > 0) {
            $whereSql = "WHERE " . implode(" AND ", $whereClauses);
        }

        $sql = "SELECT * FROM privacy_policies $whereSql ORDER BY updated_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHistory($policyName) {
        $stmt = $this->pdo->prepare("SELECT * FROM privacy_policies WHERE policy_name = ? ORDER BY id DESC");
        $stmt->execute([$policyName]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE privacy_policies SET status = ? WHERE id = ?");
        return $stmt->execute([strtolower($status), $id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM privacy_policies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getDashboardMetrics() {
        $total = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies")->fetchColumn();
        $active = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'active'")->fetchColumn();
        $draft = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'draft'")->fetchColumn();
        $archived = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'archived'")->fetchColumn();

        return [
            'total_policies' => $total ?? 0,
            'active_policies' => $active ?? 0,
            'draft_policies' => $draft ?? 0,
            'archived_policies' => $archived ?? 0
        ];
    }
}
