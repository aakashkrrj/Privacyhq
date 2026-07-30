<?php
// api/vendor-crud.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// 1. CSRF Validation
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed.']);
    exit;
}

$action = trim($_POST['action'] ?? '');
if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'Action is required.']);
    exit;
}

// Ensure user_id exists for audit logs (mocking 1 for now if not logged in)
$user_id = $_SESSION['user_id'] ?? 1;

try {
    if ($action === 'create') {
        $vendor_name = trim($_POST['vendor_name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $dpa_status  = trim($_POST['dpa_status'] ?? 'Pending');
        $risk_level  = trim($_POST['risk_level'] ?? 'Low');
        
        if (empty($vendor_name) || empty($category)) {
            throw new Exception("Vendor Name and Category are required.");
        }

        $pdo->beginTransaction();
        
        // Insert Vendor
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type) VALUES (?, ?)");
        $stmt->execute([$vendor_name, $category]);
        $vendor_id = $pdo->lastInsertId();
        
        // Insert Assessment
        $risk_score = match($risk_level) {
            'High', 'Critical' => 90,
            'Medium' => 50,
            default => 10
        };
        $status = match($dpa_status) {
            'Signed' => 'Compliant',
            default => 'Under Audit'
        };
        
        $stmt_va = $pdo->prepare("INSERT INTO vendor_assessments (vendor_id, risk_score, status) VALUES (?, ?, ?)");
        $stmt_va->execute([$vendor_id, $risk_score, $status]);
        
        // Log
        log_audit_event($pdo, 'Vendor Management', 'Create', $user_id, $vendor_id, null, json_encode(['name' => $vendor_name, 'service_type' => $category]));
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Vendor created successfully.']);
        
    } elseif ($action === 'update') {
        $vendor_id   = filter_var($_POST['vendor_id'] ?? 0, FILTER_VALIDATE_INT);
        $vendor_name = trim($_POST['vendor_name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $dpa_status  = trim($_POST['dpa_status'] ?? 'Pending');
        $risk_level  = trim($_POST['risk_level'] ?? 'Low');

        if (!$vendor_id || empty($vendor_name) || empty($category)) {
            throw new Exception("Valid Vendor ID, Name, and Category are required.");
        }

        $pdo->beginTransaction();
        
        // Verify existence
        $stmt = $pdo->prepare("SELECT id FROM vendors WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$vendor_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Vendor not found or already deleted.");
        }

        // Update Vendor
        $stmt_upd = $pdo->prepare("UPDATE vendors SET name = ?, service_type = ? WHERE id = ?");
        $stmt_upd->execute([$vendor_name, $category, $vendor_id]);
        
        // Update Assessment
        $risk_score = match($risk_level) {
            'High', 'Critical' => 90,
            'Medium' => 50,
            default => 10
        };
        $status = match($dpa_status) {
            'Signed' => 'Compliant',
            default => 'Under Audit'
        };
        
        $stmt_va = $pdo->prepare("UPDATE vendor_assessments SET risk_score = ?, status = ? WHERE vendor_id = ?");
        $stmt_va->execute([$risk_score, $status, $vendor_id]);
        
        // Log
        log_audit_event($pdo, 'Vendor Management', 'Update', $user_id, $vendor_id, null, json_encode(['name' => $vendor_name, 'service_type' => $category]));
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Vendor updated successfully.']);

    } elseif ($action === 'delete') {
        $vendor_id = filter_var($_POST['vendor_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$vendor_id) {
            throw new Exception("Valid Vendor ID required for deletion.");
        }

        $pdo->beginTransaction();
        
        // Verify existence
        $stmt = $pdo->prepare("SELECT id FROM vendors WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$vendor_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Vendor not found or already deleted.");
        }

        // Soft Delete
        $stmt_del = $pdo->prepare("UPDATE vendors SET deleted_at = NOW() WHERE id = ?");
        $stmt_del->execute([$vendor_id]);
        
        // Log
        log_audit_event($pdo, 'Vendor Management', 'Soft Delete', $user_id, $vendor_id, null, null);
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Vendor deleted successfully.']);
        
    } else {
        throw new Exception("Unknown action.");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
