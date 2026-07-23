<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Personal Data Discovery & DSPM</h3>
            <p class="text-muted mb-0">Identify and map PII/SPII across cloud, on-premise, and SaaS environments.</p>
        </div>
        <button class="btn btn-primary">
            <i class="bi bi-plug me-1"></i> Add Data Source
        </button>
    </div>

    <!-- Connected Data Sources Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-primary">Structured DB</span>
                    <span class="badge bg-danger">High Exposure</span>
                </div>
                <h5 class="fw-bold mb-1">PostgreSQL Production</h5>
                <p class="text-muted small mb-2"><code>db.prod.internal:5432</code></p>
                <div class="small">
                    <strong>Discovered PII:</strong>
                    <div class="mt-1">
                        <span class="badge bg-light text-dark">Aadhaar</span>
                        <span class="badge bg-light text-dark">PAN</span>
                        <span class="badge bg-light text-dark">Email</span>
                        <span class="badge bg-light text-dark">Mobile</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-info text-white">Cloud Storage</span>
                    <span class="badge bg-warning text-dark">Medium Exposure</span>
                </div>
                <h5 class="fw-bold mb-1">AWS S3 User Storage</h5>
                <p class="text-muted small mb-2"><code>s3://prod-user-documents</code></p>
                <div class="small">
                    <strong>Discovered PII:</strong>
                    <div class="mt-1">
                        <span class="badge bg-light text-dark">Passport</span>
                        <span class="badge bg-light text-dark">Bank Statements</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>