<?php
// governance/pages/notification-preferences.php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = (int)($_SESSION['user_id'] ?? 1);

// Fetch user notifications for Notification Center
$all_notifications = [];
if (isset($conn) && !$conn->connect_error) {
    $stmt = $conn->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $all_notifications[] = $row;
        }
        $stmt->close();
    }
}
?>

<div class="space-y-6 max-w-5xl mx-auto pb-10">

    <!-- Top Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#EDEBE9] pb-4">
        <div>
            <h2 class="font-headline-lg text-2xl font-bold text-on-surface">Notifications & Preferences</h2>
            <p class="font-body-md text-sm text-on-surface-variant mt-0.5">Manage system alerts, unread notifications, and notification settings.</p>
        </div>
        <!-- Tab Navigation Buttons -->
        <div class="flex items-center bg-surface-container-low p-1 rounded-xl border border-[#EDEBE9]">
            <button id="tabBtnCenter" onclick="switchNotifTab('center')" class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-surface text-primary shadow-sm transition-all">
                Notification Center
            </button>
            <button id="tabBtnPref" onclick="switchNotifTab('pref')" class="px-4 py-1.5 text-xs font-medium rounded-lg text-outline hover:text-on-surface transition-all">
                Preferences Settings
            </button>
        </div>
    </div>

    <!-- TAB 1: NOTIFICATION CENTER -->
    <div id="notifTabCenter" class="space-y-4">
        
        <!-- Filter & Search Toolbar -->
        <div class="bg-surface-container-lowest p-4 rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col md:flex-row items-center justify-between gap-3">
            <!-- Filter Pills -->
            <div class="flex items-center gap-2 w-full md:w-auto">
                <button onclick="filterNotifList('all')" id="filterBtnAll" class="notif-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white transition-all">
                    All
                </button>
                <button onclick="filterNotifList('unread')" id="filterBtnUnread" class="notif-filter-btn px-3 py-1.5 rounded-lg text-xs font-medium bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high transition-all">
                    Unread
                </button>
                <button onclick="filterNotifList('read')" id="filterBtnRead" class="notif-filter-btn px-3 py-1.5 rounded-lg text-xs font-medium bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high transition-all">
                    Read
                </button>
            </div>

            <!-- Search & Actions -->
            <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                <div class="relative w-full md:w-64">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-outline text-sm">search</span>
                    <input type="text" id="notifSearchInput" oninput="searchNotifList()" placeholder="Search notifications..." class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low border-none rounded-lg text-xs focus:ring-2 focus:ring-primary" />
                </div>
                <button onclick="markAllNotificationsReadPage()" class="px-3 py-1.5 bg-secondary/10 text-secondary hover:bg-secondary/20 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors">
                    Mark All Read
                </button>
            </div>
        </div>

        <!-- Notifications List Table Container -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] overflow-hidden">
            <div id="pageNotifList" class="divide-y divide-[#EDEBE9]">
                <?php if (!empty($all_notifications)): ?>
                    <?php foreach ($all_notifications as $n): ?>
                        <div data-id="<?= (int)$n['id'] ?>" data-read="<?= $n['is_read'] ? '1' : '0' ?>" data-text="<?= htmlspecialchars(strtolower($n['title'] . ' ' . $n['message'])) ?>" class="notif-page-item p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-surface-container-low/50 transition-colors <?= empty($n['is_read']) ? 'bg-primary/5 font-medium' : 'opacity-80' ?>">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 <?= empty($n['is_read']) ? 'bg-primary/10 text-primary' : 'bg-surface-container-high text-outline' ?>">
                                    <span class="material-symbols-outlined text-lg">
                                        <?= empty($n['is_read']) ? 'mail' : 'mark_email_read' ?>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-xs md:text-sm font-semibold text-on-surface"><?= htmlspecialchars($n['title']) ?></h4>
                                        <?php if (empty($n['is_read'])): ?>
                                            <span class="px-2 py-0.5 bg-primary text-white text-[10px] font-bold rounded-full">UNREAD</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1 leading-relaxed"><?= htmlspecialchars($n['message']) ?></p>
                                    <span class="text-[11px] text-outline mt-1.5 block"><?= date('M d, Y • H:i', strtotime($n['created_at'])) ?></span>
                                </div>
                            </div>
                            <!-- Actions -->
                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <?php if (empty($n['is_read'])): ?>
                                    <button onclick="markSingleNotifPage(<?= (int)$n['id'] ?>, this)" class="p-1.5 text-primary hover:bg-primary/10 rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors" title="Mark as Read">
                                        <span class="material-symbols-outlined text-base">done</span>
                                        <span class="hidden md:inline">Mark Read</span>
                                    </button>
                                <?php endif; ?>
                                <button onclick="deleteSingleNotifPage(<?= (int)$n['id'] ?>, this)" class="p-1.5 text-error hover:bg-error/10 rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors" title="Delete Notification">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                    <span class="hidden md:inline">Delete</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-outline">
                        <span class="material-symbols-outlined text-4xl mb-2 text-outline/40">notifications_off</span>
                        <p class="text-xs font-semibold">No notifications found.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Empty State Filter Notice (Hidden by default) -->
            <div id="emptyFilterState" class="hidden p-8 text-center text-outline">
                <span class="material-symbols-outlined text-4xl mb-2 text-outline/40">search_off</span>
                <p class="text-xs font-semibold">No notifications match your filter or search criteria.</p>
            </div>
        </div>

    </div>

    <!-- TAB 2: NOTIFICATION PREFERENCES SETTINGS -->
    <div id="notifTabPref" class="hidden bg-surface-container-lowest rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] p-6">
        <h3 class="font-title-md text-base md:text-lg font-semibold text-on-surface mb-2">Notification Preferences</h3>
        <p class="font-body-md text-xs text-outline mb-6">Choose how and when you receive system alerts and updates.</p>

        <form id="notificationPreferencesForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="space-y-4 divide-y divide-[#EDEBE9]">
                <label class="flex items-center justify-between pt-3 cursor-pointer">
                    <div>
                        <span class="text-xs md:text-sm font-semibold text-on-surface block">Email Notifications</span>
                        <span class="text-xs text-outline">Receive email summaries and critical alerts.</span>
                    </div>
                    <input type="checkbox" name="email_notifications" id="email_notifications" class="w-4 h-4 text-primary rounded focus:ring-primary">
                </label>

                <label class="flex items-center justify-between pt-3 cursor-pointer">
                    <div>
                        <span class="text-xs md:text-sm font-semibold text-on-surface block">In-App Notifications</span>
                        <span class="text-xs text-outline">Display notification badges and bell drop-down updates.</span>
                    </div>
                    <input type="checkbox" name="in_app_notifications" id="in_app_notifications" class="w-4 h-4 text-primary rounded focus:ring-primary">
                </label>

                <label class="flex items-center justify-between pt-3 cursor-pointer">
                    <div>
                        <span class="text-xs md:text-sm font-semibold text-on-surface block">Privacy Incident Alerts</span>
                        <span class="text-xs text-outline">High-priority alerts when data breach incidents occur.</span>
                    </div>
                    <input type="checkbox" name="privacy_incident_alerts" id="privacy_incident_alerts" class="w-4 h-4 text-primary rounded focus:ring-primary">
                </label>

                <label class="flex items-center justify-between pt-3 cursor-pointer">
                    <div>
                        <span class="text-xs md:text-sm font-semibold text-on-surface block">Consent Updates</span>
                        <span class="text-xs text-outline">Notifications when data subject consents are updated or withdrawn.</span>
                    </div>
                    <input type="checkbox" name="consent_updates" id="consent_updates" class="w-4 h-4 text-primary rounded focus:ring-primary">
                </label>

                <label class="flex items-center justify-between pt-3 cursor-pointer">
                    <div>
                        <span class="text-xs md:text-sm font-semibold text-on-surface block">Assessment Reminders</span>
                        <span class="text-xs text-outline">Reminders for pending DPIA assessments and vendor reviews.</span>
                    </div>
                    <input type="checkbox" name="assessment_reminders" id="assessment_reminders" class="w-4 h-4 text-primary rounded focus:ring-primary">
                </label>

                <label class="flex items-center justify-between pt-3 cursor-pointer">
                    <div>
                        <span class="text-xs md:text-sm font-semibold text-on-surface block">Risk Alerts</span>
                        <span class="text-xs text-outline">Notifications for high risk vendor reviews and risk register items.</span>
                    </div>
                    <input type="checkbox" name="risk_alerts" id="risk_alerts" class="w-4 h-4 text-primary rounded focus:ring-primary">
                </label>

                <label class="flex items-center justify-between pt-3 cursor-pointer">
                    <div>
                        <span class="text-xs md:text-sm font-semibold text-on-surface block">System Announcements</span>
                        <span class="text-xs text-outline">System updates and administrative announcements.</span>
                    </div>
                    <input type="checkbox" name="system_announcements" id="system_announcements" class="w-4 h-4 text-primary rounded focus:ring-primary">
                </label>
            </div>

            <div id="message" class="mt-4 text-xs font-semibold"></div>

            <div class="mt-6 pt-4 border-t border-[#EDEBE9] flex justify-end">
                <button type="submit" class="bg-primary text-on-primary hover:brightness-95 px-5 py-2.5 rounded-xl font-semibold text-xs transition-all">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Script for Notification Management & Preferences JS -->
<script src="assets/js/notification-preferences.js"></script>

<script>
function switchNotifTab(tab) {
    const center = document.getElementById('notifTabCenter');
    const pref = document.getElementById('notifTabPref');
    const btnCenter = document.getElementById('tabBtnCenter');
    const btnPref = document.getElementById('tabBtnPref');

    if (tab === 'center') {
        center.classList.remove('hidden');
        pref.classList.add('hidden');
        btnCenter.className = "px-4 py-1.5 text-xs font-semibold rounded-lg bg-surface text-primary shadow-sm transition-all";
        btnPref.className = "px-4 py-1.5 text-xs font-medium rounded-lg text-outline hover:text-on-surface transition-all";
    } else {
        center.classList.add('hidden');
        pref.classList.remove('hidden');
        btnPref.className = "px-4 py-1.5 text-xs font-semibold rounded-lg bg-surface text-primary shadow-sm transition-all";
        btnCenter.className = "px-4 py-1.5 text-xs font-medium rounded-lg text-outline hover:text-on-surface transition-all";
    }
}

let activeFilter = 'all';

function filterNotifList(filter) {
    activeFilter = filter;
    document.querySelectorAll('.notif-filter-btn').forEach(btn => {
        btn.className = "notif-filter-btn px-3 py-1.5 rounded-lg text-xs font-medium bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high transition-all";
    });
    const activeBtn = document.getElementById('filterBtn' + filter.charAt(0).toUpperCase() + filter.slice(1));
    if (activeBtn) {
        activeBtn.className = "notif-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white transition-all";
    }
    applyNotifFilters();
}

function searchNotifList() {
    applyNotifFilters();
}

function applyNotifFilters() {
    const query = document.getElementById('notifSearchInput').value.toLowerCase().trim();
    const items = document.querySelectorAll('.notif-page-item');
    let visibleCount = 0;

    items.forEach(item => {
        const isRead = item.getAttribute('data-read') === '1';
        const text = item.getAttribute('data-text');

        let matchesFilter = true;
        if (activeFilter === 'unread') matchesFilter = !isRead;
        if (activeFilter === 'read') matchesFilter = isRead;

        let matchesSearch = true;
        if (query.length > 0) matchesSearch = text.includes(query);

        if (matchesFilter && matchesSearch) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });

    const emptyState = document.getElementById('emptyFilterState');
    if (visibleCount === 0 && items.length > 0) {
        emptyState.classList.remove('hidden');
    } else {
        emptyState.classList.add('hidden');
    }
}

function markSingleNotifPage(id, btn) {
    fetch('api/mark-notification-read.php?id=' + id, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notif-page-item[data-id="${id}"]`);
                if (item) {
                    item.setAttribute('data-read', '1');
                    item.classList.add('opacity-80');
                    item.classList.remove('bg-primary/5', 'font-medium');
                    const badge = item.querySelector('span.bg-primary');
                    if (badge) badge.remove();
                    if (btn) btn.remove();
                }
                if (typeof updateBadge === 'function') updateBadge(data.unread_count);
                applyNotifFilters();
            }
        }).catch(err => console.error(err));
}

function deleteSingleNotifPage(id, btn) {
    if (!confirm('Are you sure you want to delete this notification?')) return;
    fetch('api/delete-notification.php?id=' + id, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notif-page-item[data-id="${id}"]`);
                if (item) item.remove();
                applyNotifFilters();
            }
        }).catch(err => console.error(err));
}

function markAllNotificationsReadPage() {
    fetch('api/mark-notification-read.php?all=1', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notif-page-item').forEach(item => {
                    item.setAttribute('data-read', '1');
                    item.classList.add('opacity-80');
                    item.classList.remove('bg-primary/5', 'font-medium');
                    const badge = item.querySelector('span.bg-primary');
                    if (badge) badge.remove();
                    const readBtn = item.querySelector('button[title="Mark as Read"]');
                    if (readBtn) readBtn.remove();
                });
                if (typeof updateBadge === 'function') updateBadge(0);
                applyNotifFilters();
            }
        }).catch(err => console.error(err));
}
</script>