// assets/js/cookie-governance.js
// Enterprise Cookie Governance Engine

let currentPage = 1;
let currentLimit = 10;
let currentSortBy = 'id';
let currentSortOrder = 'DESC';
let scannerInterval = null;

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 1. Dashboard Metrics Loader
async function loadDashboardMetrics() {
    try {
        const res = await fetch('backend/api/cookie-governance/index.php');
        const data = await res.json();
        if (data.success && data.data) {
            const d = data.data;
            const m = d.metrics || {};
            const cats = d.categories || {};
            const risk = d.risk_summary || {};
            const scan = d.recent_scan || {};

            document.getElementById('metric-total').innerText = m.total_cookies || 0;
            document.getElementById('metric-first-party').innerText = m.first_party || 0;
            document.getElementById('metric-third-party').innerText = m.third_party || 0;
            document.getElementById('metric-awaiting').innerText = m.awaiting_review || 0;
            document.getElementById('metric-compliance').innerText = m.compliance_pct || '100%';
            document.getElementById('metric-opt-in').innerText = m.opt_in_rate || '100%';

            // Categories Progress Bars
            const total = m.total_cookies || 1;
            const necPct = Math.round(((cats.Necessary || 0) / total) * 100);
            const anaPct = Math.round(((cats.Analytics || 0) / total) * 100);
            const mktPct = Math.round(((cats.Marketing || 0) / total) * 100);
            const prefPct = Math.round(((cats.Preferences || 0) / total) * 100);
            const unclassPct = Math.round(((cats.Unclassified || 0) / total) * 100);

            setCatProgress('cat-necessary', `Necessary (${cats.Necessary || 0})`, necPct, 'bg-emerald-500');
            setCatProgress('cat-analytics', `Analytics (${cats.Analytics || 0})`, anaPct, 'bg-blue-500');
            setCatProgress('cat-marketing', `Marketing (${cats.Marketing || 0})`, mktPct, 'bg-rose-500');
            setCatProgress('cat-preferences', `Preferences (${cats.Preferences || 0})`, prefPct, 'bg-amber-500');
            setCatProgress('cat-unclassified', `Unclassified (${cats.Unclassified || 0})`, unclassPct, 'bg-gray-400');

            // Risk Summary
            document.getElementById('risk-low').innerText = risk.low || 0;
            document.getElementById('risk-medium').innerText = risk.medium || 0;
            document.getElementById('risk-high').innerText = risk.high || 0;

            // Scanner Status Hydration
            updateScannerUI(scan);
        }
    } catch (e) {
        console.error('Failed to load Cookie Governance metrics', e);
    }
}

function setCatProgress(id, label, pct, colorClass) {
    const el = document.getElementById(id);
    if (el) {
        el.style.width = `${pct}%`;
        el.innerText = label;
    }
}

// 2. Cookie Inventory Listing
async function loadCookieInventory() {
    const search = document.getElementById('filter-search').value.trim();
    const category = document.getElementById('filter-category').value;
    const partyType = document.getElementById('filter-party').value;
    const status = document.getElementById('filter-status').value;
    const riskLevel = document.getElementById('filter-risk').value;

    const queryParams = new URLSearchParams({
        p: currentPage,
        limit: currentLimit,
        search: search,
        category: category,
        party_type: partyType,
        status: status,
        risk_level: riskLevel,
        sort_by: currentSortBy,
        sort_order: currentSortOrder
    });

    const tbody = document.getElementById('cookieTableBody');
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-gray-500"><span class="material-symbols-outlined animate-spin text-2xl text-indigo-600 block mb-1">sync</span>Loading cookie inventory...</td></tr>`;

    try {
        const res = await fetch(`backend/api/cookie-governance/cookies.php?${queryParams.toString()}`);
        const data = await res.json();

        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;

            document.getElementById('inventoryCountInfo').innerText = `Total Cookies: ${total}`;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-gray-500"><span class="material-symbols-outlined text-3xl text-gray-400 block mb-1">cookie_off</span>No matching cookies found.</td></tr>`;
            } else {
                items.forEach(c => {
                    let catBadgeClass = 'bg-gray-100 text-gray-800';
                    if (c.category_name === 'Necessary') catBadgeClass = 'bg-emerald-100 text-emerald-800';
                    else if (c.category_name === 'Analytics') catBadgeClass = 'bg-blue-100 text-blue-800';
                    else if (c.category_name === 'Marketing') catBadgeClass = 'bg-rose-100 text-rose-800';
                    else if (c.category_name === 'Preferences') catBadgeClass = 'bg-amber-100 text-amber-800';

                    let riskBadgeClass = 'bg-gray-100 text-gray-700';
                    if (c.risk_level === 'medium') riskBadgeClass = 'bg-amber-100 text-amber-800';
                    else if (c.risk_level === 'high') riskBadgeClass = 'bg-red-100 text-red-800 font-bold';

                    let statusBadgeClass = 'bg-gray-100 text-gray-800';
                    if (c.status === 'active') statusBadgeClass = 'bg-emerald-100 text-emerald-800';
                    else if (c.status === 'awaiting_review') statusBadgeClass = 'bg-amber-100 text-amber-800';
                    else if (c.status === 'blocked') statusBadgeClass = 'bg-rose-100 text-rose-800';

                    const row = `
                        <tr class="hover:bg-indigo-50/40 transition-colors border-b border-gray-100">
                            <td class="p-3.5"><input type="checkbox" class="cookie-checkbox rounded" value="${c.id}"></td>
                            <td class="p-3.5 font-bold text-gray-900"><code>${escapeHtml(c.name)}</code></td>
                            <td class="p-3.5 text-gray-600 text-xs">${escapeHtml(c.domain)}</td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full ${catBadgeClass}">
                                    ${escapeHtml(c.category_name || 'Unclassified')}
                                </span>
                            </td>
                            <td class="p-3.5 text-gray-700 text-xs font-semibold uppercase">${escapeHtml(c.party_type.replace('_', ' '))}</td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full ${riskBadgeClass}">
                                    ${escapeHtml(c.risk_level)}
                                </span>
                            </td>
                            <td class="p-3.5 text-gray-600 text-xs">${escapeHtml(c.retention)}</td>
                            <td class="p-3.5 text-right whitespace-nowrap">
                                <button onclick="openEditCookieModal(${c.id})" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs mx-1">Edit</button>
                                <button onclick="deleteCookie(${c.id})" class="text-rose-600 hover:text-rose-900 font-semibold text-xs mx-1">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / currentLimit) || 1;
            const startItem = total > 0 ? (currentPage - 1) * currentLimit + 1 : 0;
            const endItem = Math.min(currentPage * currentLimit, total);

            document.getElementById('pageInfo').innerText = `Showing ${startItem}-${endItem} of ${total} cookies (Page ${currentPage} of ${totalPages})`;
            document.getElementById('btnPrev').disabled = (currentPage <= 1);
            document.getElementById('btnNext').disabled = (currentPage >= totalPages);
        }
    } catch (e) {
        console.error('Failed to load cookie inventory', e);
    }
}

function executeSearch() {
    currentPage = 1;
    loadCookieInventory();
}

function resetFilters() {
    document.getElementById('filter-search').value = '';
    document.getElementById('filter-category').value = '';
    document.getElementById('filter-party').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-risk').value = '';
    currentPage = 1;
    loadCookieInventory();
}

function sortTable(column) {
    if (currentSortBy === column) {
        currentSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
    } else {
        currentSortBy = column;
        currentSortOrder = 'DESC';
    }
    loadCookieInventory();
}

function changePage(direction) {
    currentPage += direction;
    if (currentPage < 1) currentPage = 1;
    loadCookieInventory();
}

// 3. Category Select Loader
async function loadCategories() {
    try {
        const res = await fetch('backend/api/cookie-governance/categories.php');
        const data = await res.json();

        if (data.success && data.data) {
            const selects = [
                document.getElementById('cookie_category_id'),
                document.getElementById('edit_cookie_category_id'),
                document.getElementById('reassign_category_id'),
                document.getElementById('filter-category')
            ];

            selects.forEach(select => {
                if (!select) return;
                const isFilter = select.id === 'filter-category';
                select.innerHTML = isFilter ? '<option value="">All Categories</option>' : '<option value="">Select Category...</option>';
                data.data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${escapeHtml(cat.name)} (${cat.cookie_count || 0})</option>`;
                });
            });

            // Populate Categories Table inside Category Modal
            const catTbody = document.getElementById('categoryTableBody');
            if (catTbody) {
                catTbody.innerHTML = '';
                data.data.forEach(cat => {
                    catTbody.innerHTML += `
                        <tr class="border-b border-gray-100">
                            <td class="p-3 font-semibold text-gray-900">${escapeHtml(cat.name)} ${cat.is_necessary == 1 ? '<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Required</span>' : ''}</td>
                            <td class="p-3 text-xs text-gray-500">${escapeHtml(cat.description || 'No description')}</td>
                            <td class="p-3 font-bold text-indigo-600 text-xs">${cat.cookie_count || 0}</td>
                            <td class="p-3 text-right">
                                <button onclick="editCategory(${cat.id}, '${escapeHtml(cat.name)}', '${escapeHtml(cat.description || '')}', ${cat.is_necessary})" class="text-indigo-600 hover:text-indigo-900 text-xs font-semibold mx-1">Edit</button>
                                ${cat.is_necessary == 0 ? `<button onclick="deleteCategory(${cat.id})" class="text-rose-600 hover:text-rose-900 text-xs font-semibold mx-1">Delete</button>` : ''}
                            </td>
                        </tr>
                    `;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load categories', e);
    }
}

// 4. Scanner Controls & Progress Simulation
async function controlScan(action) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('domain', 'privacyhq.com');
    fd.append('csrf_token', G_CSRF_TOKEN);

    try {
        const res = await fetch('backend/api/cookie-governance/scanner.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success && data.data) {
            updateScannerUI(data.data);
            if (action === 'start' || action === 'resume') {
                startScannerAnimation();
            } else if (action === 'pause' || action === 'cancel' || action === 'complete') {
                stopScannerAnimation();
            }
        }
    } catch (e) {
        alert('Scanner action failed');
    }
}

function updateScannerUI(scan) {
    document.getElementById('scan-domain').innerText = scan.domain || 'privacyhq.com';
    document.getElementById('scan-status').innerText = (scan.status || 'idle').toUpperCase();
    document.getElementById('scan-status').className = `px-2.5 py-1 text-xs font-semibold rounded-full capitalize ${scan.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : (scan.status === 'scanning' ? 'bg-indigo-100 text-indigo-800 animate-pulse' : 'bg-gray-100 text-gray-800')}`;
    
    document.getElementById('scan-progress-bar').style.width = `${scan.progress_percentage || 0}%`;
    document.getElementById('scan-progress-pct').innerText = `${scan.progress_percentage || 0}%`;
    document.getElementById('scan-pages').innerText = scan.pages_scanned || 0;
    document.getElementById('scan-cookies-found').innerText = scan.cookies_found || 0;
    document.getElementById('scan-time').innerText = `${scan.time_taken_seconds || 0}s`;
    document.getElementById('scan-last-time').innerText = scan.last_scan_at || 'Never';

    // Button states
    const isScanning = scan.status === 'scanning';
    document.getElementById('btnStartScan').disabled = isScanning;
    document.getElementById('btnPauseScan').disabled = !isScanning;
    document.getElementById('btnResumeScan').disabled = scan.status !== 'paused';
    document.getElementById('btnCancelScan').disabled = scan.status === 'idle' || scan.status === 'completed';
}

function startScannerAnimation() {
    stopScannerAnimation();
    let currentPct = parseInt(document.getElementById('scan-progress-pct').innerText) || 0;
    
    scannerInterval = setInterval(async () => {
        currentPct += 15;
        if (currentPct >= 100) {
            currentPct = 100;
            stopScannerAnimation();
            await controlScan('complete');
            loadDashboardMetrics();
            loadCookieInventory();
        } else {
            const fd = new FormData();
            fd.append('action', 'status');
            fd.append('domain', 'privacyhq.com');
            fd.append('csrf_token', G_CSRF_TOKEN);
            document.getElementById('scan-progress-bar').style.width = `${currentPct}%`;
            document.getElementById('scan-progress-pct').innerText = `${currentPct}%`;
        }
    }, 1500);
}

function stopScannerAnimation() {
    if (scannerInterval) {
        clearInterval(scannerInterval);
        scannerInterval = null;
    }
}

// 5. Consent Banner Config & Live Customizer
async function loadBannerConfig() {
    try {
        const res = await fetch('backend/api/cookie-governance/banner.php');
        const data = await res.json();
        if (data.success && data.data) {
            const b = data.data;
            document.getElementById('banner_title').value = b.banner_title || '';
            document.getElementById('banner_text').value = b.banner_text || '';
            document.getElementById('banner_position').value = b.position || 'bottom';
            document.getElementById('banner_theme').value = b.theme || 'light';
            document.getElementById('banner_primary_color').value = b.primary_color || '#4F46E5';
            document.getElementById('banner_bg_color').value = b.background_color || '#FFFFFF';
            document.getElementById('banner_text_color').value = b.text_color || '#1F2937';
            document.getElementById('privacy_policy_url').value = b.privacy_policy_url || '/privacy-policy.php';
            document.getElementById('cookie_policy_url').value = b.cookie_policy_url || '/cookie-policy.php';

            updateLiveBannerPreview();
        }
    } catch (e) {
        console.error('Failed to load banner config', e);
    }
}

function updateLiveBannerPreview() {
    const title = document.getElementById('banner_title').value;
    const text = document.getElementById('banner_text').value;
    const primaryColor = document.getElementById('banner_primary_color').value;
    const bgColor = document.getElementById('banner_bg_color').value;
    const textColor = document.getElementById('banner_text_color').value;

    const previewBox = document.getElementById('liveBannerPreview');
    if (previewBox) {
        previewBox.style.backgroundColor = bgColor;
        previewBox.style.color = textColor;
        document.getElementById('prevTitle').innerText = title;
        document.getElementById('prevText').innerText = text;
        document.getElementById('prevBtnAccept').style.backgroundColor = primaryColor;
    }
}

// 6. User Consent Actions (Accept All, Reject All, Custom Preferences)
async function submitConsentChoice(choice, acceptedCategories = []) {
    const fd = new FormData();
    fd.append('choice', choice);
    acceptedCategories.forEach(cat => fd.append('categories[]', cat));
    fd.append('csrf_token', G_CSRF_TOKEN);

    try {
        const res = await fetch('backend/api/cookie-governance/consent.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            localStorage.setItem('privacyhq_cookie_consent', JSON.stringify({ choice: choice, categories: acceptedCategories, timestamp: new Date().toISOString() }));
            hideFrontConsentBanner();
            closePreferenceCenterModal();
            loadDashboardMetrics();
        }
    } catch (e) {
        console.error('Failed to save consent', e);
    }
}

function hideFrontConsentBanner() {
    const banner = document.getElementById('frontendCookieBanner');
    if (banner) banner.classList.add('hidden');
}

function checkFrontendConsentState() {
    const stored = localStorage.getItem('privacyhq_cookie_consent');
    if (!stored) {
        const banner = document.getElementById('frontendCookieBanner');
        if (banner) banner.classList.remove('hidden');
    }
}

// 7. Form Submit Helper
async function submitApi(formId, endpoint, modalIdToClose) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadCookieInventory();
            loadDashboardMetrics();
            loadCategories();
            form.reset();
            if (modalIdToClose) {
                document.getElementById(modalIdToClose).classList.add('hidden');
            }
        } else {
            alert(data.message || 'Action failed.');
        }
    } catch (e) {
        alert('Network request failed.');
    }
}

// 8. Cookie CRUD & Modals
async function openEditCookieModal(id) {
    try {
        const res = await fetch(`backend/api/cookie-governance/cookies.php?search=&limit=1000`);
        const data = await res.json();
        if (data.success && data.data && data.data.items) {
            const cookie = data.data.items.find(item => String(item.id) === String(id));
            if (cookie) {
                document.getElementById('edit_cookie_id').value = cookie.id;
                document.getElementById('edit_cookie_name').value = cookie.name;
                document.getElementById('edit_cookie_domain').value = cookie.domain;
                document.getElementById('edit_cookie_category_id').value = cookie.category_id || '';
                document.getElementById('edit_cookie_provider').value = cookie.provider || '';
                document.getElementById('edit_cookie_party_type').value = cookie.party_type || 'first_party';
                document.getElementById('edit_cookie_risk_level').value = cookie.risk_level || 'low';
                document.getElementById('edit_cookie_retention').value = cookie.retention || 'Session';
                document.getElementById('edit_cookie_status').value = cookie.status || 'active';
                document.getElementById('edit_cookie_purpose').value = cookie.purpose || '';

                document.getElementById('editCookieModal').classList.remove('hidden');
            }
        }
    } catch (e) {
        alert('Failed to load cookie details for editing');
    }
}

function closeEditCookieModal() { document.getElementById('editCookieModal').classList.add('hidden'); }

async function deleteCookie(id) {
    if (confirm('Are you sure you want to delete this cookie?')) {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('action', 'delete');
        fd.append('csrf_token', G_CSRF_TOKEN);

        try {
            const res = await fetch('backend/api/cookie-governance/cookies.php?action=delete', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                loadCookieInventory();
                loadDashboardMetrics();
            } else {
                alert(data.message || 'Delete failed');
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

// Category Actions
function editCategory(id, name, description, isNecessary) {
    document.getElementById('cat_action').value = 'update';
    document.getElementById('cat_id').value = id;
    document.getElementById('cat_name').value = name;
    document.getElementById('cat_description').value = description;
    document.getElementById('cat_is_necessary').checked = (isNecessary == 1);
    document.getElementById('btnSubmitCategory').innerText = 'Update Category';
}

function resetCategoryForm() {
    document.getElementById('cat_action').value = 'create';
    document.getElementById('cat_id').value = '';
    document.getElementById('addCategoryForm').reset();
    document.getElementById('btnSubmitCategory').innerText = 'Save Category';
}

async function deleteCategory(id) {
    if (confirm('Delete this category? Associated cookies will be reassigned to Unclassified.')) {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('action', 'delete');
        fd.append('csrf_token', G_CSRF_TOKEN);

        try {
            const res = await fetch('backend/api/cookie-governance/categories.php?action=delete', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                loadCategories();
                loadCookieInventory();
                loadDashboardMetrics();
            } else {
                alert(data.message || 'Delete failed');
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

// Reassign Selected Cookies
function openReassignModal() {
    const checkboxes = document.querySelectorAll('.cookie-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one cookie from the inventory table.');
        return;
    }

    document.getElementById('reassignCountText').innerText = `Reassigning ${checkboxes.length} selected cookies:`;
    document.getElementById('reassignModal').classList.remove('hidden');
}

function closeReassignModal() { document.getElementById('reassignModal').classList.add('hidden'); }

async function submitReassignCookies() {
    const checkboxes = document.querySelectorAll('.cookie-checkbox:checked');
    const targetCatId = document.getElementById('reassign_category_id').value;
    if (!targetCatId) {
        alert('Please select a target category.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'reassign');
    fd.append('category_id', targetCatId);
    fd.append('csrf_token', G_CSRF_TOKEN);
    checkboxes.forEach(cb => fd.append('cookie_ids[]', cb.value));

    try {
        const res = await fetch('backend/api/cookie-governance/categories.php?action=reassign', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            closeReassignModal();
            loadCookieInventory();
            loadDashboardMetrics();
            loadCategories();
        } else {
            alert(data.message || 'Reassignment failed');
        }
    } catch (e) {
        alert('Request failed');
    }
}

// 9. Export Reports
function triggerExport(format) {
    const search = document.getElementById('filter-search').value;
    const category = document.getElementById('filter-category').value;
    window.open(`backend/api/cookie-governance/export.php?format=${format}&type=inventory&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`, '_blank');
}

// Modals controls
function openAddCookieModal() { document.getElementById('addCookieModal').classList.remove('hidden'); }
function closeAddCookieModal() { document.getElementById('addCookieModal').classList.add('hidden'); }

function openCategoriesModal() { loadCategories(); document.getElementById('categoriesModal').classList.remove('hidden'); }
function closeCategoriesModal() { document.getElementById('categoriesModal').classList.add('hidden'); }

function openBannerModal() { loadBannerConfig(); document.getElementById('bannerModal').classList.remove('hidden'); }
function closeBannerModal() { document.getElementById('bannerModal').classList.add('hidden'); }

function openPreferenceCenterModal() { document.getElementById('preferenceCenterModal').classList.remove('hidden'); }
function closePreferenceCenterModal() { document.getElementById('preferenceCenterModal').classList.add('hidden'); }

// Window Exposures
window.executeSearch = executeSearch;
window.resetFilters = resetFilters;
window.sortTable = sortTable;
window.changePage = changePage;
window.controlScan = controlScan;
window.triggerExport = triggerExport;
window.openAddCookieModal = openAddCookieModal;
window.closeAddCookieModal = closeAddCookieModal;
window.openEditCookieModal = openEditCookieModal;
window.closeEditCookieModal = closeEditCookieModal;
window.deleteCookie = deleteCookie;
window.openCategoriesModal = openCategoriesModal;
window.closeCategoriesModal = closeCategoriesModal;
window.editCategory = editCategory;
window.deleteCategory = deleteCategory;
window.resetCategoryForm = resetCategoryForm;
window.openReassignModal = openReassignModal;
window.closeReassignModal = closeReassignModal;
window.submitReassignCookies = submitReassignCookies;
window.openBannerModal = openBannerModal;
window.closeBannerModal = closeBannerModal;
window.openPreferenceCenterModal = openPreferenceCenterModal;
window.closePreferenceCenterModal = closePreferenceCenterModal;
window.submitConsentChoice = submitConsentChoice;
window.updateLiveBannerPreview = updateLiveBannerPreview;

// DOM Content Loaded Listener
document.addEventListener('DOMContentLoaded', () => {
    loadDashboardMetrics();
    loadCookieInventory();
    loadCategories();
    checkFrontendConsentState();

    document.getElementById('searchForm').addEventListener('submit', (e) => {
        e.preventDefault();
        executeSearch();
    });

    // Form Handlers
    document.getElementById('addCookieForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('addCookieForm', 'backend/api/cookie-governance/cookies.php?action=create', 'addCookieModal');
    });

    document.getElementById('editCookieForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('editCookieForm', 'backend/api/cookie-governance/cookies.php?action=update', 'editCookieModal');
    });

    document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const action = document.getElementById('cat_action').value;
        submitApi('addCategoryForm', `backend/api/cookie-governance/categories.php?action=${action}`, null);
        resetCategoryForm();
        loadCategories();
    });

    document.getElementById('bannerConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('bannerConfigForm', 'backend/api/cookie-governance/banner.php', 'bannerModal');
    });

    // Custom Preference Center Form Submit
    document.getElementById('customPreferencesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const accepted = ['necessary'];
        if (document.getElementById('pref_analytics').checked) accepted.push('analytics');
        if (document.getElementById('pref_marketing').checked) accepted.push('marketing');
        if (document.getElementById('pref_preferences').checked) accepted.push('preferences');
        submitConsentChoice('custom', accepted);
    });

    // Live preview input listeners
    ['banner_title', 'banner_text', 'banner_primary_color', 'banner_bg_color', 'banner_text_color'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', updateLiveBannerPreview);
    });
});
