<?php
// pages/data-requests.php

// 1. Database Connection Setup (MySQLi)
if (!isset($conn) && !isset($pdo)) {
    if (file_exists(__DIR__ . '/../includes/db.php')) {
        require_once __DIR__ . '/../includes/db.php';
    }
}

if (!isset($conn) && isset($pdo) && $pdo instanceof mysqli) {
    $conn = $pdo;
}

$message = '';
$error = '';

// Auto-create table if not exists (Ensures seamless execution)
if (isset($conn) && $conn) {
    $table_check = "CREATE TABLE IF NOT EXISTS data_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        req_code VARCHAR(50) NOT NULL,
        email VARCHAR(255) NOT NULL,
        request_type VARCHAR(50) NOT NULL,
        priority VARCHAR(20) DEFAULT 'Medium',
        status VARCHAR(50) DEFAULT 'Pending',
        progress INT DEFAULT 0,
        due_date VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($table_check);
}

// 2. Handle New Request Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_request') {
    $email = trim($_POST['email'] ?? '');
    $request_type = trim($_POST['request_type'] ?? 'Access');
    $priority = trim($_POST['priority'] ?? 'Medium');

    if (!empty($email)) {
        if (isset($conn) && $conn) {
            $req_code = 'REQ-' . rand(100, 999);
            $due_date = date('M d', strtotime('+30 days'));
            $progress = 10;
            $status = 'Pending';

            $stmt = $conn->prepare("INSERT INTO data_requests (req_code, email, request_type, priority, status, progress, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssssis", $req_code, $email, $request_type, $priority, $status, $progress, $due_date);
                if ($stmt->execute()) {
                    $message = "New request $req_code created successfully!";
                } else {
                    $error = "Execution Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Query Error: " . $conn->error;
            }
        }
    } else {
        $error = "Email address is required.";
    }
}

// 3. Handle Status Updates (e.g. Escalate or Archive)
if (isset($_GET['action_type']) && isset($_GET['req_id'])) {
    $req_id = intval($_GET['req_id']);
    $action_type = $_GET['action_type'];

    if (isset($conn) && $conn) {
        if ($action_type === 'escalate') {
            $stmt = $conn->prepare("UPDATE data_requests SET priority = 'Urgent', status = 'Escalated' WHERE id = ?");
            $stmt->bind_param("i", $req_id);
            $stmt->execute();
            $message = "Request escalated to urgent priority!";
            $stmt->close();
        } elseif ($action_type === 'archive') {
            $stmt = $conn->prepare("UPDATE data_requests SET status = 'Completed', progress = 100 WHERE id = ?");
            $stmt->bind_param("i", $req_id);
            $stmt->execute();
            $message = "Request completed and archived!";
            $stmt->close();
        }
    }
}

// 4. Fetch Existing Requests
$requests = [];
if (isset($conn) && $conn) {
    $result = $conn->query("SELECT * FROM data_requests ORDER BY id DESC");
    if ($result) {
        $requests = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Fallback seed data if database table is completely empty
if (empty($requests)) {
    $requests = [
        ['id' => 1, 'req_code' => 'REQ-402', 'email' => 'sarah.j@enterprise.com', 'request_type' => 'Deletion', 'priority' => 'High', 'status' => 'In Progress', 'progress' => 85, 'due_date' => 'Oct 24'],
        ['id' => 2, 'req_code' => 'REQ-405', 'email' => 'marcus.k@global.io', 'request_type' => 'Access', 'priority' => 'Medium', 'status' => 'Pending', 'progress' => 12, 'due_date' => 'Nov 05'],
        ['id' => 3, 'req_code' => 'REQ-398', 'email' => 'linda.v@corp.com', 'request_type' => 'Portability', 'priority' => 'Low', 'status' => 'Completed', 'progress' => 100, 'due_date' => 'Oct 20'],
        ['id' => 4, 'req_code' => 'REQ-410', 'email' => 'robert.chen@tech.net', 'request_type' => 'Access', 'priority' => 'Medium', 'status' => 'Assigned', 'progress' => 5, 'due_date' => 'Nov 19'],
        ['id' => 5, 'req_code' => 'REQ-395', 'email' => 'legal-team@partner.com', 'request_type' => 'Regulatory escalation', 'priority' => 'Urgent', 'status' => 'Final Verification', 'progress' => 94, 'due_date' => 'Today']
    ];
}

$critical_count = count(array_filter($requests, fn($r) => strtolower($r['priority']) === 'urgent' || strtolower($r['priority']) === 'high'));
$active_count = count(array_filter($requests, fn($r) => strtolower($r['status']) !== 'completed'));
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Data Requests</h1>
            <p class="text-sm text-gray-500">Manage and respond to Subject Access Requests (DSAR)</p>
        </div>
        <div class="flex items-center gap-4 text-xs font-semibold">
            <span class="flex items-center gap-1.5 text-red-600 bg-red-50 px-2.5 py-1 rounded-full border border-red-100">
                <span class="w-2 h-2 rounded-full bg-red-500"></span> <?php echo $critical_count; ?> Critical
            </span>
            <span class="flex items-center gap-1.5 text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span> <?php echo $active_count; ?> Active
            </span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Controls Row (Search, Filters, New Request) -->
    <div class="flex flex-wrap gap-3 justify-between items-center">
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <div class="relative w-full max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-lg">search</span>
                <input type="text" id="searchInput" onkeyup="filterCards()" placeholder="Search by ID or email..." class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>

            <select id="typeFilter" onchange="filterCards()" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 text-gray-600">
                <option value="All">Type: All</option>
                <option value="Access">Access</option>
                <option value="Deletion">Deletion</option>
                <option value="Portability">Portability</option>
            </select>

            <select id="priorityFilter" onchange="filterCards()" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 text-gray-600">
                <option value="All">Priority: All</option>
                <option value="Urgent">Urgent</option>
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
            </select>
        </div>

        <button onclick="toggleModal(true)" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm flex items-center gap-1.5 shadow-sm transition-colors">
            <span class="material-symbols-outlined text-sm">add</span> New Request
        </button>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="cardsGrid">
        <?php foreach ($requests as $r): 
            $is_urgent = strtolower($r['priority']) === 'urgent';
            $is_completed = strtolower($r['status']) === 'completed';
        ?>
            <div class="request-card bg-white p-5 rounded-xl border <?php echo $is_urgent ? 'border-red-200 shadow-sm md:col-span-2 bg-red-50/10' : 'border-gray-100 shadow-sm'; ?>"
                 data-code="<?php echo strtolower($r['req_code']); ?>"
                 data-email="<?php echo strtolower($r['email']); ?>"
                 data-type="<?php echo $r['request_type']; ?>"
                 data-priority="<?php echo $r['priority']; ?>">
                
                <?php if ($is_urgent): ?>
                    <!-- Urgent Card Design -->
                    <div class="flex justify-between items-start mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            Priority: Urgent
                        </span>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-red-600 block">Today</span>
                            <span class="text-[10px] text-gray-400">Awaiting Legal Clearance</span>
                        </div>
                    </div>

                    <h3 class="text-base font-bold text-gray-800 mb-0.5"><?php echo htmlspecialchars($r['req_code']); ?> (<?php echo htmlspecialchars($r['request_type']); ?>)</h3>
                    <p class="text-xs text-gray-500 mb-4"><?php echo htmlspecialchars($r['email']); ?></p>

                    <div class="space-y-1.5 mb-4">
                        <div class="flex justify-between text-xs font-medium text-gray-600">
                            <span><?php echo htmlspecialchars($r['status']); ?></span>
                            <span><?php echo $r['progress']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden flex">
                            <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $r['progress']; ?>%"></div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                        <div class="flex items-center gap-1 text-xs text-red-600 font-medium">
                            <span class="material-symbols-outlined text-sm">warning</span>
                            Immediate action required
                        </div>
                        <a href="index.php?page=data-requests&action_type=escalate&req_id=<?php echo $r['id']; ?>" class="bg-red-600 hover:bg-red-700 text-white font-medium text-xs px-4 py-2 rounded-lg transition-colors">
                            Escalate
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Regular Card Design -->
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700">
                            <?php echo htmlspecialchars($r['request_type']); ?>
                        </span>
                        <?php if ($is_completed): ?>
                            <span class="flex items-center gap-1 text-xs text-green-600 font-medium">
                                <span class="material-symbols-outlined text-base">check_circle</span>
                                Done <?php echo htmlspecialchars($r['due_date']); ?>
                            </span>
                        <?php else: ?>
                            <div class="text-right">
                                <span class="text-xs font-bold text-red-600 block"><?php echo htmlspecialchars($r['due_date']); ?> Left</span>
                                <span class="text-[10px] text-gray-400">Due Date</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-base font-bold text-gray-800"><?php echo htmlspecialchars($r['req_code']); ?></h3>
                    <p class="text-xs text-gray-500 mb-4"><?php echo htmlspecialchars($r['email']); ?></p>

                    <div class="space-y-1.5 mb-4">
                        <div class="flex justify-between text-xs font-medium text-gray-600">
                            <span><?php echo htmlspecialchars($r['status']); ?></span>
                            <span><?php echo $r['progress']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="<?php echo $is_completed ? 'bg-blue-600' : ($r['progress'] > 50 ? 'bg-red-500' : 'bg-blue-500'); ?> h-1.5 rounded-full" style="width: <?php echo $r['progress']; ?>%"></div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2 border-t border-gray-100 text-xs">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-[10px]">
                            <?php echo strtoupper(substr($r['email'], 0, 2)); ?>
                        </span>
                        <?php if ($is_completed): ?>
                            <a href="index.php?page=data-requests&action_type=archive&req_id=<?php echo $r['id']; ?>" class="text-gray-500 hover:text-gray-700 flex items-center gap-1 font-medium">
                                Archive <span class="material-symbols-outlined text-sm">archive</span>
                            </a>
                        <?php else: ?>
                            <a href="javascript:void(0)" onclick="alert('Viewing details for <?php echo $r['req_code']; ?>')" class="text-blue-600 hover:underline flex items-center gap-0.5 font-medium">
                                Details <span class="material-symbols-outlined text-sm">chevron_right</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- New Request Modal -->
<div id="newRequestModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Submit New Request</h2>
            <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php?page=data-requests" class="space-y-4">
            <input type="hidden" name="action" value="create_request">

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Subject Email Address</label>
                <input type="email" name="email" required placeholder="user@domain.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Request Type</label>
                <select name="request_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="Access">Access (DSAR)</option>
                    <option value="Deletion">Deletion (Right to be Forgotten)</option>
                    <option value="Portability">Data Portability</option>
                    <option value="Rectification">Rectification</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                    <option value="Low">Low</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <button type="button" onclick="toggleModal(false)" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium shadow-sm">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(show) {
    const modal = document.getElementById('newRequestModal');
    if (show) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function filterCards() {
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    const typeVal = document.getElementById('typeFilter').value;
    const priorityVal = document.getElementById('priorityFilter').value;
    const cards = document.querySelectorAll('.request-card');

    cards.forEach(card => {
        const code = card.getAttribute('data-code');
        const email = card.getAttribute('data-email');
        const type = card.getAttribute('data-type');
        const priority = card.getAttribute('data-priority');

        const matchesSearch = code.includes(searchVal) || email.includes(searchVal);
        const matchesType = (typeVal === 'All') || (type === typeVal);
        const matchesPriority = (priorityVal === 'All') || (priority === priorityVal);

        if (matchesSearch && matchesType && matchesPriority) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>