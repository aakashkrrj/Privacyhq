<div class="content-wrapper">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Cookie Governance & Scan Center</h3>
            <p class="text-muted mb-0">Discover, categorize, and manage cookie compliance for your web domains.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scanModal">
            <i class="bi bi-search me-1"></i> Start New Scan
        </button>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Total Active Cookies</small>
                <h3 class="fw-bold text-dark mt-1 mb-0">148</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Uncategorized Cookies</small>
                <h3 class="fw-bold text-danger mt-1 mb-0">8</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Cookie Opt-In Rate</small>
                <h3 class="fw-bold text-success mt-1 mb-0">82.4%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Configured Banners</small>
                <h3 class="fw-bold text-info mt-1 mb-0">3 Domains</h3>
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
                    <tbody>
                        <tr>
                            <td><code>_ga</code></td>
                            <td>example.com</td>
                            <td><span class="badge bg-info-subtle text-info">Analytics</span></td>
                            <td>First-Party</td>
                            <td>2 Years</td>
                            <td><button class="btn btn-sm btn-light">Edit</button></td>
                        </tr>
                        <tr>
                            <td><code>_fbp</code></td>
                            <td>example.com</td>
                            <td><span class="badge bg-danger-subtle text-danger">Advertising</span></td>
                            <td>Third-Party</td>
                            <td>90 Days</td>
                            <td><button class="btn btn-sm btn-light">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>