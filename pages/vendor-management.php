<?php
// governance/pages/vendor-management.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';

/** @var PDO $pdo */

// Audit Log Helper
if (!function_exists('log_audit_event')) {
    function log_audit_event($user, $action, $module, $severity = 'Info', $details = '') {
        global $pdo;
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO audit_logs (user_name, action, module, severity, details) VALUES (?, ?, ?, ?, ?)");
                return $stmt->execute([$user, $action, $module, $severity, $details]);
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vendor'])) {
    $vendor_name = trim($_POST['vendor_name'] ?? '');
    $category    = trim($_POST['category'] ?? 'Software');
    $dpa_status  = trim($_POST['dpa_status'] ?? 'Pending');
    $risk_level  = trim($_POST['risk_level'] ?? 'Low');
    $data_shared = trim($_POST['data_shared'] ?? '');

    if (!empty($vendor_name)) {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO vendors (vendor_name, category, dpa_status, risk_level, data_shared) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$vendor_name, $category, $dpa_status, $risk_level, $data_shared])) {
                $message = "Vendor added successfully!";
                log_audit_event('admin@privacyhq.io', 'Added Vendor', 'Vendor Management', 'Info', "Vendor: $vendor_name");
            }
        }
    }
}

$vendors = [];
try {
    if (isset($pdo)) {
        $vendors = $pdo->query("SELECT * FROM vendors ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
?>


    <div class="max-w-screen-xl mx-auto px-6 pt-10 pb-6">
        <h2 class="text-3xl font-bold text-on-surface"><i class="bi bi-building text-primary me-2"></i>Vendor Risk Management</h2>
        <p class="text-on-surface-variant mt-1">Track external software, sub-processors, and Data Processing Agreements (DPAs).</p>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Add Vendor Form -->
        <div class="bg-surface-container-lowest rounded-xl border border-[#EDEBE9] p-6 shadow-sm mb-6">
            <h5 class="fw-bold mb-3">+ Add New Vendor</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Vendor Name</label>
                        <input type="text" name="vendor_name" class="form-control" placeholder="e.g., AWS, Stripe, Google" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Category</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g., Cloud Hosting, Payment">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">DPA Status</label>
                        <select name="dpa_status" class="form-select">
                            <option value="Signed">Signed</option>
                            <option value="Pending" selected>Pending</option>
                            <option value="Not Required">Not Required</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Risk Level</label>
                        <select name="risk_level" class="form-select">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Data Shared / Processed</label>
                        <textarea name="data_shared" class="form-control" rows="2" placeholder="e.g., Customer email, payment tokens..."></textarea>
                    </div>
                    <div class="col-12">
    <button
        type="submit"
        name="add_vendor"
        class="btn btn-primary fw-semibold">
        Save Vendor
    </button>
</div>
                </div>
            </form>
        </div>

        <!-- Vendor Table -->
        <div class="card p-4 border-0 shadow-sm">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">Total Vendors</h4>
<p class="text-3xl font-bold"><?= count($vendors) ?></p>
</div>

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">High Risk</h4>
<p class="text-3xl font-bold text-red-600">2</p>
</div>

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">Pending DPA</h4>
<p class="text-3xl font-bold text-yellow-600">4</p>
</div>

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">Critical</h4>
<p class="text-3xl font-bold text-red-700">1</p>
</div>

</div>
            <h5 class="text-2xl font-bold text-on-surface mb-4">Vendor Inventory</h5>
            <div class="flex gap-4 mb-5 flex-wrap">

<input
class="border rounded-xl px-4 py-3 flex-1"
placeholder="Search Vendor">

<select class="border rounded-xl px-4 py-3">
<option>All Categories</option>
</select>

<select class="border rounded-xl px-4 py-3">
<option>All Risks</option>
</select>

</div>
            <div class="table-responsive">
                <table class="w-full border-collapse">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th>Vendor Name</th>
                            <th>Category</th>
                            <th>DPA Status</th>
                            <th>Risk Level</th>
                            <th>Data Shared</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if (!empty($vendors)): ?>
    <?php foreach ($vendors as $v): ?>

        <?php
            $dpaClass = match($v['dpa_status']) {
                'Signed' => 'bg-green-100 text-green-700',
                'Pending' => 'bg-yellow-100 text-yellow-700',
                default => 'bg-gray-100 text-gray-700'
            };

            $riskClass = match($v['risk_level']) {
                'Critical' => 'bg-red-100 text-red-700',
                'High' => 'bg-red-100 text-red-700',
                'Medium' => 'bg-yellow-100 text-yellow-700',
                default => 'bg-green-100 text-green-700'
            };
        ?>

        <tr class="border-b hover:bg-gray-50 transition">

            <td class="py-4">
                <strong><?= htmlspecialchars($v['vendor_name']) ?></strong>
            </td>

            <td>
                <?= htmlspecialchars($v['category']) ?>
            </td>

            <td>
                <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= $dpaClass ?>">
                    <?= htmlspecialchars($v['dpa_status']) ?>
                </span>
            </td>

            <td>
                <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= $riskClass ?>">
                    <?= htmlspecialchars($v['risk_level']) ?>
                </span>
            </td>

            <td class="text-gray-600">
                <?= htmlspecialchars($v['data_shared']) ?>
            </td>

        </tr>

    <?php endforeach; ?>

<?php else: ?>

<tr>
    <td colspan="5" class="text-center py-8 text-gray-500">
        No vendors registered yet.
    </td>
</tr>

<?php endif; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>
    