<?php
namespace Backend\Models;

class DataAsset {
    private $pdo;

    public function __construct(\PDO $pdo = null) {
        $this->pdo = $pdo ?? $GLOBALS['pdo'];
    }

    public function createAsset($data, $userId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO data_assets (
                asset_name, description, asset_type, owner, business_unit, 
                status, criticality, source_id, environment, discovery_method, 
                classification, sensitivity_indicators, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['asset_name'],
            $data['description'] ?? '',
            $data['asset_type'] ?? '',
            $data['owner'] ?? '',
            $data['business_unit'] ?? '',
            $data['status'] ?? 'Active',
            $data['criticality'] ?? 'Medium',
            !empty($data['source_id']) ? $data['source_id'] : null,
            $data['environment'] ?? 'Production',
            $data['discovery_method'] ?? 'Manual',
            $data['classification'] ?? 'Internal',
            isset($data['sensitivity_indicators']) ? json_encode($data['sensitivity_indicators']) : null,
            $userId
        ]);
        $id = $this->pdo->lastInsertId();
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'DSPM', 'Create Data Asset', $userId, $id, null, json_encode(['asset_name' => $data['asset_name']]));
        }
        return $id;
    }

    public function getAsset($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM data_assets WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateAsset($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE data_assets SET
                asset_name = ?, description = ?, asset_type = ?, owner = ?, business_unit = ?, 
                status = ?, criticality = ?, source_id = ?, environment = ?, classification = ?, 
                sensitivity_indicators = ?
            WHERE id = ? AND deleted_at IS NULL
        ");
        $res = $stmt->execute([
            $data['asset_name'],
            $data['description'] ?? '',
            $data['asset_type'] ?? '',
            $data['owner'] ?? '',
            $data['business_unit'] ?? '',
            $data['status'] ?? 'Active',
            $data['criticality'] ?? 'Medium',
            !empty($data['source_id']) ? $data['source_id'] : null,
            $data['environment'] ?? 'Production',
            $data['classification'] ?? 'Internal',
            isset($data['sensitivity_indicators']) ? json_encode($data['sensitivity_indicators']) : null,
            $id
        ]);
        if ($res && function_exists('log_audit_event')) {
            // we assume user id is not passed, but we can try to guess it or pass it. 
            // In API we don't pass userId to updateAsset right now. I need to fix that or use session.
            $userId = $_SESSION['user_id'] ?? 0;
            log_audit_event($this->pdo, 'DSPM', 'Update Data Asset', $userId, $id, null, json_encode(['classification' => $data['classification'] ?? '']));
        }
        return $res;
    }

    public function linkCategory($assetId, $categoryName) {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO data_asset_categories (asset_id, category_name) VALUES (?, ?)");
        $res = $stmt->execute([$assetId, $categoryName]);
        if ($res && function_exists('log_audit_event')) {
            $userId = $_SESSION['user_id'] ?? 0;
            log_audit_event($this->pdo, 'DSPM', 'Link Asset to Category', $userId, $assetId, null, json_encode(['category' => $categoryName]));
        }
        return $res;
    }

    public function linkRopa($assetId, $ropaId) {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO data_asset_ropa (asset_id, processing_activity_id) VALUES (?, ?)");
        $res = $stmt->execute([$assetId, $ropaId]);
        if ($res && function_exists('log_audit_event')) {
            $userId = $_SESSION['user_id'] ?? 0;
            log_audit_event($this->pdo, 'DSPM', 'Link Asset to RoPA', $userId, $assetId, null, json_encode(['ropa_id' => $ropaId]));
        }
        return $res;
    }

    public function linkRisk($assetId, $riskId) {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO data_asset_risks (asset_id, risk_id) VALUES (?, ?)");
        $res = $stmt->execute([$assetId, $riskId]);
        if ($res && function_exists('log_audit_event')) {
            $userId = $_SESSION['user_id'] ?? 0;
            log_audit_event($this->pdo, 'DSPM', 'Link Asset to Risk', $userId, $assetId, null, json_encode(['risk_id' => $riskId]));
        }
        return $res;
    }

    public function importAssets($assets, $userId) {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($assets as $idx => $row) {
            if (empty($row['asset_name'])) {
                $failed++;
                $errors[] = "Row $idx: Missing asset_name";
                continue;
            }

            // check duplicate
            $stmt = $this->pdo->prepare("SELECT id FROM data_assets WHERE asset_name = ? AND deleted_at IS NULL");
            $stmt->execute([$row['asset_name']]);
            if ($stmt->fetch()) {
                $failed++;
                $errors[] = "Row $idx: Duplicate asset_name";
                continue;
            }

            $sourceId = null;
            if (!empty($row['source_name'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM discovery_sources WHERE name = ? AND deleted_at IS NULL");
                $stmt->execute([$row['source_name']]);
                $src = $stmt->fetch();
                if ($src) {
                    $sourceId = $src['id'];
                } else {
                    $failed++;
                    $errors[] = "Row $idx: Invalid source_name";
                    continue;
                }
            }

            $allowedClassifications = ['Public', 'Internal', 'Confidential', 'Restricted'];
            $classification = in_array($row['classification'] ?? '', $allowedClassifications) ? $row['classification'] : 'Internal';

            $data = [
                'asset_name' => $row['asset_name'],
                'description' => $row['description'] ?? '',
                'asset_type' => $row['asset_type'] ?? '',
                'owner' => $row['owner'] ?? '',
                'classification' => $classification,
                'source_id' => $sourceId,
                'discovery_method' => 'Import'
            ];
            $this->createAsset($data, $userId);
            $imported++;
        }

        if ($imported > 0 && function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'DSPM', 'Import Data Assets', $userId, null, null, json_encode(['imported' => $imported, 'failed' => $failed]));
        }

        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
    }

    public function getDashboardMetrics() {
        $metrics = [];

        $metrics['total_assets'] = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE deleted_at IS NULL")->fetchColumn();
        $metrics['active_assets'] = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE status = 'Active' AND deleted_at IS NULL")->fetchColumn();
        $metrics['critical_assets'] = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE criticality = 'Critical' AND deleted_at IS NULL")->fetchColumn();
        $metrics['restricted_assets'] = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE classification = 'Restricted' AND deleted_at IS NULL")->fetchColumn();
        
        $metrics['unclassified_assets'] = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE (classification IS NULL OR classification = '') AND deleted_at IS NULL")->fetchColumn();
        $metrics['ownerless_assets'] = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE (owner IS NULL OR owner = '') AND deleted_at IS NULL")->fetchColumn();
        
        $metrics['assets_without_ropa'] = (int)$this->pdo->query("
            SELECT COUNT(*) FROM data_assets da 
            LEFT JOIN data_asset_ropa dar ON da.id = dar.asset_id 
            WHERE dar.asset_id IS NULL AND da.deleted_at IS NULL
        ")->fetchColumn();

        $metrics['assets_with_high_risks'] = (int)$this->pdo->query("
            SELECT COUNT(DISTINCT da.id) FROM data_assets da 
            JOIN data_asset_risks dar ON da.id = dar.asset_id 
            JOIN assessment_risks ar ON dar.risk_id = ar.id 
            WHERE ar.residual_level IN ('High', 'Critical') AND da.deleted_at IS NULL
        ")->fetchColumn();

        $metrics['by_classification'] = $this->pdo->query("
            SELECT classification, COUNT(*) as cnt FROM data_assets WHERE deleted_at IS NULL GROUP BY classification
        ")->fetchAll(\PDO::FETCH_KEY_PAIR);

        $metrics['by_source_type'] = $this->pdo->query("
            SELECT ds.source_type, COUNT(da.id) as cnt FROM data_assets da 
            JOIN discovery_sources ds ON da.source_id = ds.id 
            WHERE da.deleted_at IS NULL AND ds.deleted_at IS NULL 
            GROUP BY ds.source_type
        ")->fetchAll(\PDO::FETCH_KEY_PAIR);

        return $metrics;
    }
}
