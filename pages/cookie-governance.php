<!--
 Cookie Governance is currently a UI placeholder because the
 application contains no persistence layer for cookie management.
 All data is fetched from the backend/api/cookie-governance/ placeholder endpoint.
-->
<div class="content-wrapper">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Cookie Governance & Scan Center</h3>
            <p class="text-muted mb-0">Discover, categorize, and manage cookie compliance for your web domains. <span class="badge bg-warning text-dark">UI Placeholder</span></p>
        </div>
        <button class="btn btn-primary" onclick="alert('Coming Soon: Scan functionality is currently a placeholder.');">
            <i class="bi bi-search me-1"></i> Start New Scan
        </button>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Total Active Cookies</small>
                <h3 class="fw-bold text-dark mt-1 mb-0" id="metric-total">...</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Uncategorized Cookies</small>
                <h3 class="fw-bold text-danger mt-1 mb-0" id="metric-uncategorized">...</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Cookie Opt-In Rate</small>
                <h3 class="fw-bold text-success mt-1 mb-0" id="metric-opt-in">...</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Configured Banners</small>
                <h3 class="fw-bold text-info mt-1 mb-0" id="metric-banners">...</h3>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card card-custom h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Cookie Categories</h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="alert('Coming Soon: Feature under development.');">
                            Export Report
                        </button>
                    </div>
                    <div class="progress mb-3" style="height:14px;">
                        <div class="progress-bar bg-success" style="width:42%" id="cat-necessary">Necessary (42%)</div>
                    </div>
                    <div class="progress mb-3" style="height:14px;">
                        <div class="progress-bar bg-info" style="width:28%" id="cat-analytics">Analytics (28%)</div>
                    </div>
                    <div class="progress mb-3" style="height:14px;">
                        <div class="progress-bar bg-warning" style="width:18%" id="cat-preferences">Preferences (18%)</div>
                    </div>
                    <div class="progress" style="height:14px;">
                        <div class="progress-bar bg-danger" style="width:12%" id="cat-advertising">Advertising (12%)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Recent Scan</h5>
                    <p><strong>Domain</strong><br><span id="scan-domain">...</span></p>
                    <p><strong>Status</strong><br><span class="badge bg-success" id="scan-status">...</span></p>
                    <p><strong>Cookies Found</strong><br><span id="scan-cookies">...</span></p>
                    <p><strong>Last Scan</strong><br><span id="scan-time">...</span></p>
                    <button class="btn btn-primary w-100" onclick="alert('Coming Soon: Scan functionality is currently a placeholder.');">
                        Run Scan Again
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cookie Inventory Table -->
    <div class="card card-custom">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="fw-bold">Discovered Cookies & Trackers</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Cookie Name</th>
                            <th>Domain</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cookieTableBody">
                        <tr><td colspan="6" class="text-center py-4 text-muted">Loading cookies...</td></tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <small class="text-muted" id="inventory-count">
                        Showing 0 of 0 Cookies
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadCookieGovernance() {
    try {
        const response = await fetch('backend/api/cookie-governance/index.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Hydrate metrics
            document.getElementById('metric-total').textContent = data.metrics.total_cookies;
            document.getElementById('metric-uncategorized').textContent = data.metrics.uncategorized;
            document.getElementById('metric-opt-in').textContent = data.metrics.opt_in_rate;
            document.getElementById('metric-banners').textContent = data.metrics.configured_banners;
            
            // Hydrate scan summary
            document.getElementById('scan-domain').textContent = data.recent_scan.domain;
            document.getElementById('scan-status').textContent = data.recent_scan.status;
            document.getElementById('scan-cookies').textContent = data.recent_scan.cookies_found;
            document.getElementById('scan-time').textContent = data.recent_scan.last_scan;
            
            // Hydrate categories progress bars
            const cats = data.categories;
            document.getElementById('cat-necessary').style.width = cats.Necessary + '%';
            document.getElementById('cat-necessary').textContent = `Necessary (${cats.Necessary}%)`;
            
            document.getElementById('cat-analytics').style.width = cats.Analytics + '%';
            document.getElementById('cat-analytics').textContent = `Analytics (${cats.Analytics}%)`;
            
            document.getElementById('cat-preferences').style.width = cats.Preferences + '%';
            document.getElementById('cat-preferences').textContent = `Preferences (${cats.Preferences}%)`;
            
            document.getElementById('cat-advertising').style.width = cats.Advertising + '%';
            document.getElementById('cat-advertising').textContent = `Advertising (${cats.Advertising}%)`;
            
            // Hydrate inventory table
            const tbody = document.getElementById('cookieTableBody');
            tbody.innerHTML = '';
            
            data.inventory.forEach(cookie => {
                let badgeClass = 'bg-info-subtle text-info';
                if (cookie.category === 'Advertising') badgeClass = 'bg-danger-subtle text-danger';
                else if (cookie.category === 'Necessary') badgeClass = 'bg-success-subtle text-success';
                else if (cookie.category === 'Preferences') badgeClass = 'bg-warning-subtle text-warning';
                
                const row = `
                    <tr>
                        <td><code>${escapeHtml(cookie.name)}</code></td>
                        <td>${escapeHtml(cookie.domain)}</td>
                        <td><span class="badge ${badgeClass}">${escapeHtml(cookie.category)}</span></td>
                        <td>${escapeHtml(cookie.type)}</td>
                        <td>${escapeHtml(cookie.duration)}</td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" onclick="alert('Coming Soon: Feature under development.');">Edit</button>
                                <button class="btn btn-sm btn-outline-success" onclick="alert('Coming Soon: Feature under development.');">Approve</button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            
            document.getElementById('inventory-count').textContent = `Showing 1-2 of ${data.metrics.total_cookies} Cookies`;
        }
    } catch (error) {
        console.error("Failed to load cookie governance dataset", error);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

document.addEventListener('DOMContentLoaded', loadCookieGovernance);
</script>