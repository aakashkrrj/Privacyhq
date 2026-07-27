<div class="content-wrapper">

    <!-- ================= HEADER ================= -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Personal Data Discovery & DSPM</h3>
            <p class="text-muted mb-0">
                Identify and map PII/SPII across cloud, on-premise, and SaaS environments.
            </p>
        </div>

        <button class="btn btn-primary">
            <i class="bi bi-plug me-1"></i>
            Add Data Source
        </button>
    </div>

    <!-- ================= KPI CARDS ================= -->
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Connected Sources</small>
                <h3 class="fw-bold mt-2">12</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">PII Records</small>
                <h3 class="fw-bold text-primary mt-2">2.8M</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Sensitive Files</small>
                <h3 class="fw-bold text-danger mt-2">487</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Compliance Score</small>
                <h3 class="fw-bold text-success mt-2">91%</h3>
            </div>
        </div>

    </div>

    <!-- ================= SEARCH & FILTER ================= -->

    <div class="card card-custom mb-4">
        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-5">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Search Data Sources">
                </div>

                <div class="col-md-3">
                    <select class="form-select">
                        <option>All Sources</option>
                        <option>Database</option>
                        <option>Cloud</option>
                        <option>SaaS</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select">
                        <option>Risk</option>
                        <option>High</option>
                        <option>Medium</option>
                        <option>Low</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100">
                        Search
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- ================= CONNECTED DATA SOURCES ================= -->

    <div class="row g-3 mb-4">

        <!-- Card 1 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-primary">Structured DB</span>
                    <span class="badge bg-danger">High Exposure</span>
                </div>

                <h5 class="fw-bold">PostgreSQL Production</h5>

                <p class="text-muted small">
                    <code>db.prod.internal:5432</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Aadhaar</span>
                    <span class="badge bg-light text-dark">PAN</span>
                    <span class="badge bg-light text-dark">Email</span>
                    <span class="badge bg-light text-dark">Mobile</span>
                </div>

            </div>
        </div>

        <!-- Card 2 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-info">Cloud Storage</span>
                    <span class="badge bg-warning text-dark">Medium Exposure</span>
                </div>

                <h5 class="fw-bold">AWS S3 User Storage</h5>

                <p class="text-muted small">
                    <code>s3://prod-user-documents</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Passport</span>
                    <span class="badge bg-light text-dark">Bank Statements</span>
                    <span class="badge bg-light text-dark">Invoices</span>
                </div>

            </div>
        </div>

        <!-- Card 3 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-success">NoSQL</span>
                    <span class="badge bg-danger">High Exposure</span>
                </div>

                <h5 class="fw-bold">MongoDB Atlas</h5>

                <p class="text-muted small">
                    <code>cluster0.mongodb.net</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Customer IDs</span>
                    <span class="badge bg-light text-dark">Email</span>
                    <span class="badge bg-light text-dark">Phone</span>
                </div>

            </div>
        </div>

        <!-- Card 4 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-secondary">Enterprise DB</span>
                    <span class="badge bg-warning text-dark">Medium Exposure</span>
                </div>

                <h5 class="fw-bold">Microsoft SQL Server</h5>

                <p class="text-muted small">
                    <code>sql.company.local</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Employee IDs</span>
                    <span class="badge bg-light text-dark">Salary</span>
                    <span class="badge bg-light text-dark">PAN</span>
                </div>

            </div>
        </div>

        <!-- Card 5 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-primary">SaaS</span>
                    <span class="badge bg-success">Low Exposure</span>
                </div>

                <h5 class="fw-bold">Google Drive</h5>

                <p class="text-muted small">
                    <code>drive.google.com</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Contracts</span>
                    <span class="badge bg-light text-dark">Invoices</span>
                    <span class="badge bg-light text-dark">HR Files</span>
                </div>

            </div>
        </div>

        <!-- Card 6 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-dark">CRM</span>
                    <span class="badge bg-warning text-dark">Medium Exposure</span>
                </div>

                <h5 class="fw-bold">Salesforce CRM</h5>

                <p class="text-muted small">
                    <code>salesforce.enterprise.com</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Customers</span>
                    <span class="badge bg-light text-dark">Leads</span>
                    <span class="badge bg-light text-dark">Phone</span>
                </div>

            </div>
        </div>

    </div>

    <!-- ===== PART 2 STARTS FROM HERE ===== -->
     <div class="content-wrapper">

    <!-- ================= HEADER ================= -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Personal Data Discovery & DSPM</h3>
            <p class="text-muted mb-0">
                Identify and map PII/SPII across cloud, on-premise, and SaaS environments.
            </p>
        </div>

        <button class="btn btn-primary">
            <i class="bi bi-plug me-1"></i>
            Add Data Source
        </button>
    </div>

    <!-- ================= KPI CARDS ================= -->
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Connected Sources</small>
                <h3 class="fw-bold mt-2">12</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">PII Records</small>
                <h3 class="fw-bold text-primary mt-2">2.8M</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Sensitive Files</small>
                <h3 class="fw-bold text-danger mt-2">487</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-custom p-3">
                <small class="text-muted">Compliance Score</small>
                <h3 class="fw-bold text-success mt-2">91%</h3>
            </div>
        </div>

    </div>

    <!-- ================= SEARCH & FILTER ================= -->

    <div class="card card-custom mb-4">
        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-5">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Search Data Sources">
                </div>

                <div class="col-md-3">
                    <select class="form-select">
                        <option>All Sources</option>
                        <option>Database</option>
                        <option>Cloud</option>
                        <option>SaaS</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select">
                        <option>Risk</option>
                        <option>High</option>
                        <option>Medium</option>
                        <option>Low</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100">
                        Search
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- ================= CONNECTED DATA SOURCES ================= -->

    <div class="row g-3 mb-4">

        <!-- Card 1 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-primary">Structured DB</span>
                    <span class="badge bg-danger">High Exposure</span>
                </div>

                <h5 class="fw-bold">PostgreSQL Production</h5>

                <p class="text-muted small">
                    <code>db.prod.internal:5432</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Aadhaar</span>
                    <span class="badge bg-light text-dark">PAN</span>
                    <span class="badge bg-light text-dark">Email</span>
                    <span class="badge bg-light text-dark">Mobile</span>
                </div>

            </div>
        </div>

        <!-- Card 2 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-info">Cloud Storage</span>
                    <span class="badge bg-warning text-dark">Medium Exposure</span>
                </div>

                <h5 class="fw-bold">AWS S3 User Storage</h5>

                <p class="text-muted small">
                    <code>s3://prod-user-documents</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Passport</span>
                    <span class="badge bg-light text-dark">Bank Statements</span>
                    <span class="badge bg-light text-dark">Invoices</span>
                </div>

            </div>
        </div>

        <!-- Card 3 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-success">NoSQL</span>
                    <span class="badge bg-danger">High Exposure</span>
                </div>

                <h5 class="fw-bold">MongoDB Atlas</h5>

                <p class="text-muted small">
                    <code>cluster0.mongodb.net</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Customer IDs</span>
                    <span class="badge bg-light text-dark">Email</span>
                    <span class="badge bg-light text-dark">Phone</span>
                </div>

            </div>
        </div>

        <!-- Card 4 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-secondary">Enterprise DB</span>
                    <span class="badge bg-warning text-dark">Medium Exposure</span>
                </div>

                <h5 class="fw-bold">Microsoft SQL Server</h5>

                <p class="text-muted small">
                    <code>sql.company.local</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Employee IDs</span>
                    <span class="badge bg-light text-dark">Salary</span>
                    <span class="badge bg-light text-dark">PAN</span>
                </div>

            </div>
        </div>

        <!-- Card 5 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-primary">SaaS</span>
                    <span class="badge bg-success">Low Exposure</span>
                </div>

                <h5 class="fw-bold">Google Drive</h5>

                <p class="text-muted small">
                    <code>drive.google.com</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Contracts</span>
                    <span class="badge bg-light text-dark">Invoices</span>
                    <span class="badge bg-light text-dark">HR Files</span>
                </div>

            </div>
        </div>

        <!-- Card 6 -->
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">

                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-dark">CRM</span>
                    <span class="badge bg-warning text-dark">Medium Exposure</span>
                </div>

                <h5 class="fw-bold">Salesforce CRM</h5>

                <p class="text-muted small">
                    <code>salesforce.enterprise.com</code>
                </p>

                <strong>Discovered PII</strong>

                <div class="mt-2">
                    <span class="badge bg-light text-dark">Customers</span>
                    <span class="badge bg-light text-dark">Leads</span>
                    <span class="badge bg-light text-dark">Phone</span>
                </div>

            </div>
        </div>

    </div>

    <!-- ===== PART 2 STARTS FROM HERE ===== -->
     <!-- ================= DATA CLASSIFICATION ================= -->

<div class="card card-custom mb-4">

    <div class="card-header bg-transparent">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-diagram-3-fill text-primary me-2"></i>
            Data Classification Overview
        </h5>
    </div>

    <div class="card-body">

        <div class="row g-3">

            <!-- Personal -->
            <div class="col-md-3">
                <div class="border rounded-3 p-4 text-center h-100">

                    <i class="bi bi-person-fill text-primary fs-1 mb-3"></i>

                    <h3 class="fw-bold">1.25M</h3>

                    <p class="text-muted mb-0">
                        Personal Records
                    </p>

                </div>
            </div>

            <!-- Sensitive -->
            <div class="col-md-3">
                <div class="border rounded-3 p-4 text-center h-100">

                    <i class="bi bi-shield-lock-fill text-danger fs-1 mb-3"></i>

                    <h3 class="fw-bold">420K</h3>

                    <p class="text-muted mb-0">
                        Sensitive Personal Data
                    </p>

                </div>
            </div>

            <!-- Financial -->
            <div class="col-md-3">
                <div class="border rounded-3 p-4 text-center h-100">

                    <i class="bi bi-bank2 text-success fs-1 mb-3"></i>

                    <h3 class="fw-bold">218K</h3>

                    <p class="text-muted mb-0">
                        Financial Records
                    </p>

                </div>
            </div>

            <!-- Health -->
            <div class="col-md-3">
                <div class="border rounded-3 p-4 text-center h-100">

                    <i class="bi bi-heart-pulse-fill text-warning fs-1 mb-3"></i>

                    <h3 class="fw-bold">74K</h3>

                    <p class="text-muted mb-0">
                        Health Records
                    </p>

                </div>
            </div>

        </div>

    </div>

</div>

<!-- ================= QUICK ACTIONS ================= -->

<div class="card card-custom mb-4">

    <div class="card-header bg-transparent">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-lightning-charge-fill text-warning me-2"></i>
            Quick Actions
        </h5>
    </div>

    <div class="card-body">

        <div class="row g-3">

            <div class="col-lg-3 col-md-6">
                <button class="btn btn-primary w-100 py-3">
                    <i class="bi bi-search me-2"></i>
                    Start New Scan
                </button>
            </div>

            <div class="col-lg-3 col-md-6">
                <button class="btn btn-success w-100 py-3">
                    <i class="bi bi-file-earmark-arrow-down me-2"></i>
                    Export Report
                </button>
            </div>

            <div class="col-lg-3 col-md-6">
                <button class="btn btn-info text-white w-100 py-3">
                    <i class="bi bi-cloud-download me-2"></i>
                    Download Inventory
                </button>
            </div>

            <div class="col-lg-3 col-md-6">
                <button class="btn btn-warning text-dark w-100 py-3">
                    <i class="bi bi-bar-chart-fill me-2"></i>
                    View Heatmap
                </button>
            </div>

        </div>

        <hr class="my-4">

        <div class="row text-center">

            <div class="col-md-3">
                <h3 class="fw-bold text-primary">12</h3>
                <small class="text-muted">Connected Sources</small>
            </div>

            <div class="col-md-3">
                <h3 class="fw-bold text-danger">487</h3>
                <small class="text-muted">Sensitive Files</small>
            </div>

            <div class="col-md-3">
                <h3 class="fw-bold text-success">91%</h3>
                <small class="text-muted">Compliance Score</small>
            </div>

            <div class="col-md-3">
                <h3 class="fw-bold text-warning">3</h3>
                <small class="text-muted">Pending Reviews</small>
            </div>

        </div>

    </div>

</div>

</div>