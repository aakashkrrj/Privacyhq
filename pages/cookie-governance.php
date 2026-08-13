<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<div class="content-wrapper p-md space-y-md">
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-headline-lg text-headline-lg text-primary mb-1">Cookie Governance & Scan Center</h3>
            <p class="font-body-md text-body-md text-outline mb-0">Discover, categorize, and manage cookie compliance for your web domains.</p>
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl font-body-sm hover:bg-surface-container-low" onclick="openPreferenceCenterModal()">
                Preference Center Test
            </button>
            <button class="px-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl font-body-sm hover:bg-surface-container-low" onclick="openBannerConfigModal()">
                Banner Config
            </button>
            <button class="px-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl font-body-sm hover:bg-surface-container-low" onclick="openAddDomainModal()">
                Add Domain
            </button>
            <button class="px-4 py-2 bg-primary text-white rounded-xl font-body-sm hover:bg-opacity-95 flex items-center gap-1" onclick="startDomainScan()">
                <span class="material-symbols-outlined text-[18px]">search</span> Start Discovery Scan
            </button>
        </div>
    </div>

    <!-- Domain Selector Filter -->
    <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant flex items-center gap-md">
        <label class="font-label-md text-label-md text-outline uppercase tracking-wider">Select Domain Context:</label>
        <select id="domainSelect" onchange="loadCookieGovernance()" class="px-3 py-1.5 border border-outline-variant rounded-lg bg-surface text-on-surface">
            <option value="">All Registered Domains</option>
        </select>
        <span id="scanRunningIndicator" class="hidden flex items-center gap-1 text-primary animate-pulse font-body-sm">
            <span class="material-symbols-outlined spin">sync</span> Scan running...
        </span>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md shadow-sm">
            <small class="text-outline font-label-md uppercase tracking-wider">Total Active Cookies</small>
            <h3 class="font-display text-display text-on-surface mt-1" id="metric-total">...</h3>
        </div>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md shadow-sm">
            <small class="text-outline font-label-md uppercase tracking-wider">Uncategorized Cookies</small>
            <h3 class="font-display text-display text-error mt-1" id="metric-uncategorized">...</h3>
        </div>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md shadow-sm">
            <small class="text-outline font-label-md uppercase tracking-wider">Cookie Opt-In Rate</small>
            <h3 class="font-display text-display text-success mt-1" id="metric-opt-in">...</h3>
        </div>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md shadow-sm">
            <small class="text-outline font-label-md uppercase tracking-wider">Configured Banners</small>
            <h3 class="font-display text-display text-primary mt-1" id="metric-banners">...</h3>
        </div>
    </div>

    <!-- Categories Progress Bars -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <div class="col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant p-md">
            <div class="flex justify-between items-center mb-3">
                <h5 class="font-title-md text-title-md">Cookie Categories</h5>
            </div>
            <div class="space-y-sm">
                <div>
                    <div class="flex justify-between text-caption font-semibold mb-1">
                        <span>Necessary</span>
                        <span id="cat-necessary-pct">0%</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-3">
                        <div id="cat-necessary-bar" class="bg-success h-3 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-caption font-semibold mb-1">
                        <span>Analytics</span>
                        <span id="cat-analytics-pct">0%</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-3">
                        <div id="cat-analytics-bar" class="bg-primary h-3 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-caption font-semibold mb-1">
                        <span>Preferences / Functional</span>
                        <span id="cat-preferences-pct">0%</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-3">
                        <div id="cat-preferences-bar" class="bg-warning h-3 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-caption font-semibold mb-1">
                        <span>Advertising</span>
                        <span id="cat-advertising-pct">0%</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-3">
                        <div id="cat-advertising-bar" class="bg-error h-3 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md">
            <h5 class="font-title-md text-title-md mb-3">Recent Scan Status</h5>
            <div class="space-y-sm text-body-md text-on-surface-variant">
                <p><strong>Domain:</strong> <span id="scan-domain">...</span></p>
                <p><strong>Status:</strong> <span class="px-2 py-0.5 rounded text-caption font-bold" id="scan-status">...</span></p>
                <p><strong>Cookies Found:</strong> <span id="scan-cookies">...</span></p>
                <p><strong>Last Scan:</strong> <span id="scan-time">...</span></p>
            </div>
        </div>
    </div>

    <!-- Cookie Inventory Table -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
        <div class="p-md border-b border-outline-variant flex justify-between items-center">
            <h5 class="font-title-md text-title-md">Discovered Cookies & Trackers</h5>
            <div class="flex items-center gap-2">
                <input id="searchCookies" type="text" placeholder="Search..." oninput="loadCookieGovernance()" class="px-3 py-1 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                <select id="filterCategory" onchange="loadCookieGovernance()" class="px-3 py-1 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                    <option value="">All Categories</option>
                    <option value="Essential">Essential</option>
                    <option value="Functional">Functional</option>
                    <option value="Performance">Performance</option>
                    <option value="Analytics">Analytics</option>
                    <option value="Advertising">Advertising</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant font-label-md">
                        <th class="p-md">Cookie Name</th>
                        <th class="p-md">Domain</th>
                        <th class="p-md">Category</th>
                        <th class="p-md">Type</th>
                        <th class="p-md">Duration</th>
                        <th class="p-md">Actions</th>
                    </tr>
                </thead>
                <tbody id="cookieTableBody">
                    <tr><td colspan="6" class="text-center py-4 text-outline">Loading cookies...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="p-md flex justify-between items-center border-t border-outline-variant">
            <small class="text-outline" id="inventory-count">Showing 0-0 of 0 Cookies</small>
            <div class="flex gap-1" id="paginationControls">
                <!-- Page buttons injected dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Domain -->
<div id="addDomainModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-1">
            <span class="material-symbols-outlined text-primary">add_link</span> Add Domain/Website
        </h2>
        <form id="addDomainForm" onsubmit="submitAddDomain(event)" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Website Domain Name</label>
                <input type="text" name="domain_name" placeholder="example.com" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeAddDomainModal()" class="px-4 py-2 border border-outline-variant rounded-lg hover:bg-surface-container-low text-body-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 text-body-sm">Register</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Cookie -->
<div id="editCookieModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-1">
            <span class="material-symbols-outlined text-primary">edit_attributes</span> Edit Classification
        </h2>
        <form id="editCookieForm" onsubmit="submitEditCookie(event)" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="cookie_id" id="editCookieId">
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Cookie Name</label>
                <input type="text" id="editCookieName" readonly class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface-container-low text-on-surface" style="cursor: not-allowed;">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Category</label>
                <select name="category" id="editCookieCategory" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                    <option value="Essential">Essential</option>
                    <option value="Functional">Functional</option>
                    <option value="Performance">Performance</option>
                    <option value="Analytics">Analytics</option>
                    <option value="Advertising">Advertising</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Description</label>
                <textarea name="description" id="editCookieDescription" rows="2" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeEditCookieModal()" class="px-4 py-2 border border-outline-variant rounded-lg hover:bg-surface-container-low text-body-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 text-body-sm">Save Classification</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Banner Config -->
<div id="bannerConfigModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 w-full max-w-lg shadow-xl relative overflow-y-auto max-h-[90vh]">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-1">
            <span class="material-symbols-outlined text-primary">branding_watermark</span> Consent Banner Config
        </h2>
        <form id="bannerConfigForm" onsubmit="submitBannerConfig(event)" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Target Domain</label>
                <select name="domain_id" id="bannerConfigDomainSelect" onchange="loadBannerConfigForDomain(this.value)" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface"></select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Banner Title</label>
                <input type="text" name="banner_title" id="bannerTitle" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Banner Message / Text</label>
                <textarea name="banner_text" id="bannerText" rows="3" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold uppercase text-outline mb-1">Language</label>
                    <input type="text" name="language" id="bannerLanguage" value="en" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-outline mb-1">Branding Color</label>
                    <input type="text" name="branding_color" id="bannerColor" value="#005faa" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-xs font-semibold uppercase text-outline mb-1">Accept Button</label>
                    <input type="text" name="accept_all_text" id="bannerAcceptText" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-outline mb-1">Reject Button</label>
                    <input type="text" name="reject_all_text" id="bannerRejectText" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-outline mb-1">Preferences Button</label>
                    <input type="text" name="preferences_text" id="bannerPrefText" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeBannerConfigModal()" class="px-4 py-2 border border-outline-variant rounded-lg hover:bg-surface-container-low text-body-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 text-body-sm">Save Config</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Preference Center -->
<div id="preferenceCenterModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-1">
            <span class="material-symbols-outlined text-primary">tune</span> Cookie Preference Center
        </h2>
        <form id="preferenceCenterForm" onsubmit="submitConsentPreferences(event)" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div>
                <label class="block text-xs font-semibold uppercase text-outline mb-1">Your Email</label>
                <input type="email" name="email" required placeholder="user@example.com" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div class="space-y-2 border-t border-outline-variant pt-3">
                <div class="flex items-center justify-between">
                    <div>
                        <strong class="text-body-sm">Necessary Cookies</strong>
                        <p class="text-caption text-outline mb-0">Required for system authentication and security operations.</p>
                    </div>
                    <input type="checkbox" checked disabled class="rounded border-outline-variant text-primary focus:ring-primary">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <strong class="text-body-sm">Functional Cookies</strong>
                        <p class="text-caption text-outline mb-0">Remembers your user details and page options selection.</p>
                    </div>
                    <input type="checkbox" name="pref_Functional" value="1" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <strong class="text-body-sm">Performance Cookies</strong>
                        <p class="text-caption text-outline mb-0">Improves app speeds and request metrics tracking.</p>
                    </div>
                    <input type="checkbox" name="pref_Performance" value="1" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <strong class="text-body-sm">Analytics Cookies</strong>
                        <p class="text-caption text-outline mb-0">Gathers usage data to optimize features delivery.</p>
                    </div>
                    <input type="checkbox" name="pref_Analytics" value="1" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <strong class="text-body-sm">Advertising Cookies</strong>
                        <p class="text-caption text-outline mb-0">Provides customized notifications and promo items.</p>
                    </div>
                    <input type="checkbox" name="pref_Advertising" value="1" class="rounded border-outline-variant text-primary focus:ring-primary">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closePreferenceCenterModal()" class="px-4 py-2 border border-outline-variant rounded-lg hover:bg-surface-container-low text-body-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 text-body-sm">Save Preferences</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;

async function loadDomainsList() {
    try {
        const response = await fetch('backend/api/cookie-governance/list-domains.php');
        const res = await response.json();
        if (res.success && res.data) {
            const select = document.getElementById('domainSelect');
            const configSelect = document.getElementById('bannerConfigDomainSelect');
            
            // Keep default value
            select.innerHTML = '<option value="">All Registered Domains</option>';
            configSelect.innerHTML = '';

            res.data.forEach(d => {
                const opt = `<option value="${d.id}">${escapeHtml(d.domain_name)}</option>`;
                select.innerHTML += opt;
                configSelect.innerHTML += opt;
            });
        }
    } catch (e) {
        console.error("Failed to load domains dropdown list", e);
    }
}

async function loadCookieGovernance() {
    try {
        const domainId = document.getElementById('domainSelect').value;
        const search = document.getElementById('searchCookies').value;
        const category = document.getElementById('filterCategory').value;

        // 1. Load Dashboard Metrics
        let dashboardUrl = 'backend/api/cookie-governance/index.php';
        if (domainId) {
            dashboardUrl += `?domain_id=${domainId}`;
        }
        const dashResponse = await fetch(dashboardUrl);
        const dashRes = await dashResponse.json();
        
        if (dashRes.success && dashRes.data) {
            const data = dashRes.data;
            document.getElementById('metric-total').textContent = data.metrics.total_cookies;
            document.getElementById('metric-uncategorized').textContent = data.metrics.uncategorized;
            document.getElementById('metric-opt-in').textContent = data.metrics.opt_in_rate;
            document.getElementById('metric-banners').textContent = data.metrics.configured_banners;

            document.getElementById('scan-domain').textContent = data.recent_scan.domain;
            document.getElementById('scan-status').textContent = data.recent_scan.status;
            document.getElementById('scan-cookies').textContent = data.recent_scan.cookies_found;
            document.getElementById('scan-time').textContent = data.recent_scan.last_scan;

            const scanStatusSpan = document.getElementById('scan-status');
            if (data.recent_scan.status === 'Completed') {
                scanStatusSpan.className = 'px-2 py-0.5 rounded text-caption font-bold bg-success-subtle text-success';
            } else if (data.recent_scan.status === 'Failed') {
                scanStatusSpan.className = 'px-2 py-0.5 rounded text-caption font-bold bg-error-subtle text-error';
            } else if (data.recent_scan.status === 'Running') {
                scanStatusSpan.className = 'px-2 py-0.5 rounded text-caption font-bold bg-primary-subtle text-primary animate-pulse';
            } else {
                scanStatusSpan.className = 'px-2 py-0.5 rounded text-caption font-bold bg-surface-variant text-outline';
            }

            // Hydrate progress bars
            const cats = data.categories;
            const total = data.metrics.total_cookies || 1;
            const necPct = Math.round((cats.Essential / total) * 100) || 0;
            const anaPct = Math.round((cats.Analytics / total) * 100) || 0;
            const prefPct = Math.round(((cats.Preferences || cats.Functional || 0) / total) * 100) || 0;
            const advPct = Math.round((cats.Advertising / total) * 100) || 0;

            document.getElementById('cat-necessary-pct').textContent = `${necPct}%`;
            document.getElementById('cat-necessary-bar').style.width = `${necPct}%`;
            document.getElementById('cat-analytics-pct').textContent = `${anaPct}%`;
            document.getElementById('cat-analytics-bar').style.width = `${anaPct}%`;
            document.getElementById('cat-preferences-pct').textContent = `${prefPct}%`;
            document.getElementById('cat-preferences-bar').style.width = `${prefPct}%`;
            document.getElementById('cat-advertising-pct').textContent = `${advPct}%`;
            document.getElementById('cat-advertising-bar').style.width = `${advPct}%`;
        }

        // 2. Load Cookies Inventory List
        let listUrl = `backend/api/cookie-governance/list-cookies.php?page=${currentPage}&page_size=10`;
        if (domainId) listUrl += `&domain_id=${domainId}`;
        if (search) listUrl += `&search=${encodeURIComponent(search)}`;
        if (category) listUrl += `&category=${encodeURIComponent(category)}`;

        const listResponse = await fetch(listUrl);
        const listRes = await listResponse.json();

        const tbody = document.getElementById('cookieTableBody');
        tbody.innerHTML = '';

        if (listRes.success && listRes.data && listRes.data.items.length > 0) {
            listRes.data.items.forEach(cookie => {
                let badgeClass = 'bg-info-subtle text-info border border-info/20';
                if (cookie.category === 'Advertising') badgeClass = 'bg-danger-subtle text-danger border border-danger/20';
                else if (cookie.category === 'Essential') badgeClass = 'bg-success-subtle text-success border border-success/20';
                else if (cookie.category === 'Functional' || cookie.category === 'Performance') badgeClass = 'bg-warning-subtle text-warning border border-warning/20';

                const row = `
                    <tr class="hover:bg-surface-container-low transition-colors border-b border-outline-variant">
                        <td class="p-md font-mono text-body-sm"><code>${escapeHtml(cookie.name)}</code></td>
                        <td class="p-md text-body-sm">${escapeHtml(cookie.domain_name)}</td>
                        <td class="p-md"><span class="badge px-2.5 py-0.5 rounded-full text-caption font-semibold ${badgeClass}">${escapeHtml(cookie.category)}</span></td>
                        <td class="p-md text-body-sm">${escapeHtml(cookie.technology_type)} (${escapeHtml(cookie.party_type)})</td>
                        <td class="p-md text-body-sm">${escapeHtml(cookie.expiry || 'Session')}</td>
                        <td class="p-md">
                            <button onclick="openEditCookieModal(${cookie.id}, '${escapeHtml(cookie.name)}', '${escapeHtml(cookie.category)}', '${escapeHtml(cookie.description || '')}')" class="px-2.5 py-1 text-caption font-medium border border-outline-variant rounded hover:bg-surface text-primary">Edit</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            // Pagination Controls
            const totalItems = listRes.data.total;
            const totalPages = Math.ceil(totalItems / 10) || 1;
            document.getElementById('inventory-count').textContent = `Showing ${(currentPage-1)*10+1}-${Math.min(currentPage*10, totalItems)} of ${totalItems} Cookies`;

            const pag = document.getElementById('paginationControls');
            pag.innerHTML = '';
            for (let i = 1; i <= totalPages; i++) {
                const activeClass = i === currentPage ? 'bg-primary text-white' : 'border border-outline-variant hover:bg-surface-container-low';
                pag.innerHTML += `<button onclick="goToPage(${i})" class="px-3 py-1 rounded text-caption font-semibold ${activeClass}">${i}</button>`;
            }

        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-outline">No cookie inventory items found.</td></tr>`;
            document.getElementById('inventory-count').textContent = `Showing 0 of 0 Cookies`;
            document.getElementById('paginationControls').innerHTML = '';
        }

    } catch (e) {
        console.error("Failed to load cookie data metrics", e);
    }
}

function goToPage(p) {
    currentPage = p;
    loadCookieGovernance();
}

// Modal Handlers
function openAddDomainModal() { document.getElementById('addDomainModal').classList.remove('hidden'); }
function closeAddDomainModal() { document.getElementById('addDomainModal').classList.add('hidden'); }

function openEditCookieModal(id, name, cat, desc) {
    document.getElementById('editCookieId').value = id;
    document.getElementById('editCookieName').value = name;
    document.getElementById('editCookieCategory').value = cat;
    document.getElementById('editCookieDescription').value = desc;
    document.getElementById('editCookieModal').classList.remove('hidden');
}
function closeEditCookieModal() { document.getElementById('editCookieModal').classList.add('hidden'); }

function openPreferenceCenterModal() { document.getElementById('preferenceCenterModal').classList.remove('hidden'); }
function closePreferenceCenterModal() { document.getElementById('preferenceCenterModal').classList.add('hidden'); }

function openBannerConfigModal() {
    loadDomainsList().then(() => {
        const domainId = document.getElementById('domainSelect').value || document.getElementById('bannerConfigDomainSelect').value;
        if (domainId) {
            document.getElementById('bannerConfigDomainSelect').value = domainId;
            loadBannerConfigForDomain(domainId);
        }
        document.getElementById('bannerConfigModal').classList.remove('hidden');
    });
}
function closeBannerConfigModal() { document.getElementById('bannerConfigModal').classList.add('hidden'); }

// Submissions
function submitAddDomain(e) {
    e.preventDefault();
    const fd = new FormData(document.getElementById('addDomainForm'));
    fetch('backend/api/cookie-governance/add-domain.php', {
        method: 'POST',
        body: fd
    }).then(res => res.json()).then(res => {
        if (res.success) {
            alert('Domain added successfully.');
            closeAddDomainModal();
            loadDomainsList().then(loadCookieGovernance);
        } else {
            alert(res.message);
        }
    });
}

function submitEditCookie(e) {
    e.preventDefault();
    const fd = new FormData(document.getElementById('editCookieForm'));
    fetch('backend/api/cookie-governance/update-classification.php', {
        method: 'POST',
        body: fd
    }).then(res => res.json()).then(res => {
        if (res.success) {
            alert('Cookie classification saved.');
            closeEditCookieModal();
            loadCookieGovernance();
        } else {
            alert(res.message);
        }
    });
}

async function loadBannerConfigForDomain(domainId) {
    if (!domainId) return;
    try {
        const response = await fetch(`backend/api/cookie-governance/banner-config.php?domain_id=${domainId}`);
        const res = await response.json();
        if (res.success && res.data) {
            document.getElementById('bannerTitle').value = res.data.banner_title || 'Cookie Preferences';
            document.getElementById('bannerText').value = res.data.banner_text || '';
            document.getElementById('bannerLanguage').value = res.data.language || 'en';
            document.getElementById('bannerAcceptText').value = res.data.accept_all_text || 'Accept All';
            document.getElementById('bannerRejectText').value = res.data.reject_all_text || 'Reject';
            document.getElementById('bannerPrefText').value = res.data.preferences_text || 'Preferences';
            document.getElementById('bannerColor').value = res.data.branding_color || '#005faa';
        }
    } catch (e) {
        console.error(e);
    }
}

function submitBannerConfig(e) {
    e.preventDefault();
    const fd = new FormData(document.getElementById('bannerConfigForm'));
    fetch('backend/api/cookie-governance/banner-config.php', {
        method: 'POST',
        body: fd
    }).then(res => res.json()).then(res => {
        if (res.success) {
            alert('Banner configuration saved.');
            closeBannerConfigModal();
        } else {
            alert(res.message);
        }
    });
}

function submitConsentPreferences(e) {
    e.preventDefault();
    const fd = new FormData(document.getElementById('preferenceCenterForm'));
    
    // Construct preferences object
    const prefs = {
        Essential: true,
        Functional: document.getElementsByName('pref_Functional')[0].checked,
        Performance: document.getElementsByName('pref_Performance')[0].checked,
        Analytics: document.getElementsByName('pref_Analytics')[0].checked,
        Advertising: document.getElementsByName('pref_Advertising')[0].checked
    };

    const submitFd = new FormData();
    submitFd.append('csrf_token', fd.get('csrf_token'));
    submitFd.append('email', fd.get('email'));
    submitFd.append('preferences', JSON.stringify(prefs));

    fetch('backend/api/cookie-governance/save-consent.php', {
        method: 'POST',
        body: submitFd
    }).then(res => res.json()).then(res => {
        if (res.success) {
            alert('Cookie preferences updated in centralized repository.');
            closePreferenceCenterModal();
            loadCookieGovernance();
        } else {
            alert(res.message);
        }
    });
}

function startDomainScan() {
    const domainId = document.getElementById('domainSelect').value;
    if (!domainId) {
        alert('Please select a domain context before launching a scan.');
        return;
    }

    document.getElementById('scanRunningIndicator').classList.remove('hidden');
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= $csrf_token ?>');
    fd.append('domain_id', domainId);
    // Explicitly enforce real scanner vs mock adapter check based on local vs production hosts
    fd.append('force_mock', 'false'); 

    fetch('backend/api/cookie-governance/scan.php', {
        method: 'POST',
        body: fd
    }).then(res => res.json()).then(res => {
        document.getElementById('scanRunningIndicator').classList.add('hidden');
        if (res.success) {
            alert('Scan execution finished successfully.');
            loadCookieGovernance();
        } else {
            alert('Scan Failed: ' + res.message);
            loadCookieGovernance();
        }
    }).catch(err => {
        document.getElementById('scanRunningIndicator').classList.add('hidden');
        console.error(err);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

document.addEventListener('DOMContentLoaded', () => {
    loadDomainsList().then(loadCookieGovernance);
});
</script>