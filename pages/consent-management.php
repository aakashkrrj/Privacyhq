<?php
// pages/consent-management.php

// 1. Database Connection check (MySQLi Compatible)
if (!isset($conn) && !isset($pdo)) {
    if (file_exists(__DIR__ . '/../includes/db.php')) {
        require_once __DIR__ . '/../includes/db.php';
    }
}

// Ensure $conn is the active MySQLi connection variable
if (!isset($conn) && isset($pdo) && $pdo instanceof mysqli) {
    $conn = $pdo;
}

$message = '';
$error = '';

// 2. Handle Form Submission (Save Consent)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_consent') {
    $user_identifier = trim($_POST['user_identifier'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = trim($_POST['status'] ?? 'Granted');

    if (!empty($user_identifier) && !empty($category)) {
        if (isset($conn) && $conn) {
            $stmt = $conn->prepare("INSERT INTO consent_management (user_identifier, category, status, captured_at) VALUES (?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("sss", $user_identifier, $category, $status);
                if ($stmt->execute()) {
                    $message = "Consent logged successfully!";
                    
                    if (function_exists('log_audit_event')) {
                        log_audit_event($conn, 'CONSENT_LOGGED', "Consent recorded for: $user_identifier ($category)");
                    }
                } else {
                    $error = "Execution Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Query Error: " . $conn->error;
            }
        } else {
            $error = "Database connection not available.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// 3. Handle Revoke Action
if (isset($_GET['revoke_id'])) {
    $revoke_id = intval($_GET['revoke_id']);
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("UPDATE consent_management SET status = 'Revoked' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $revoke_id);
            if ($stmt->execute()) {
                $message = "Consent revoked successfully!";
            } else {
                $error = "Error revoking consent: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 4. Fetch Existing Consents (MySQLi Compatible)
$consents = [];
if (isset($conn) && $conn) {
    $result = $conn->query("SELECT * FROM consent_management ORDER BY captured_at DESC");
    if ($result) {
        $consents = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-green-600">check_circle</span>
            Consent Management
        </h1>
        <p class="text-sm text-gray-500">Capture, audit, and revoke user consent preferences across digital properties.</p>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Log User Consent Form -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-md font-semibold text-gray-700 mb-4">+ Log User Consent</h2>
        
        <form method="POST" action="index.php?page=consent" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="action" value="save_consent">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">User Identifier (Email / ID)</label>
                <input type="text" name="user_identifier" required placeholder="user@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Consent Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="Marketing Emails">Marketing Emails</option>
                    <option value="Analytics Cookies">Analytics Cookies</option>
                    <option value="Third-party Sharing">Third-party Sharing</option>
                    <option value="Terms of Service">Terms of Service</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Initial Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="Granted">Granted</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">add</span> Save Consent
                </button>
            </div>
        </form>
    </div>

    <!-- Consent Activity Log Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-md font-semibold text-gray-700">Consent Activity Log</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3">User Identifier</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Captured At</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($consents)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">No consent records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consents as $c): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($c['user_identifier']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($c['category']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full font-medium <?php 
                                        echo $c['status'] === 'Granted' ? 'bg-green-100 text-green-700' : 
                                            ($c['status'] === 'Revoked' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); 
                                    ?>">
                                        <?php echo htmlspecialchars($c['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500"><?php echo htmlspecialchars($c['captured_at']); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($c['status'] !== 'Revoked'): ?>
                                        <a href="index.php?page=consent&revoke_id=<?php echo $c['id']; ?>" class="text-xs text-red-600 hover:underline">Revoke</a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>