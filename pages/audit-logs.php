<?php
// Active tab detection helper
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// List of pages under the "More" dropdown to keep active state highlighted
$morePages = [
    'cookie-governance', 'data-discovery', 'incident-management', 
    'vendor-risk', 'reports', 'settings', 'data-mapping', 
    'risk-register', 'audit-logs', 'user-management'
];
$isMoreActive = in_array($currentPage, $morePages);
?>

<!-- Bootstrap Dropdown Support CSS Fix for Fixed-Bottom Nav -->
<style>
    .dropup .dropdown-menu {
        bottom: 100% !important;
        top: auto !important;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
</style>

<nav class="navbar fixed-bottom navbar-light bg-white border-top py-2">
    <div class="container-fluid d-flex justify-content-around text-center">
        
        <!-- Dashboard -->
        <a href="index.php?page=dashboard" class="nav-link p-0 <?= ($currentPage == 'dashboard') ? 'text-primary fw-bold' : 'text-muted' ?>">
            <i class="bi bi-grid-fill d-block fs-5"></i>
            <small style="font-size: 11px;">Dashboard</small>
        </a>

        <!-- ROPA -->
        <a href="index.php?page=ropa" class="nav-link p-0 <?= ($currentPage == 'ropa') ? 'text-primary fw-bold' : 'text-muted' ?>">
            <i class="bi bi-journal-text d-block fs-5"></i>
            <small style="font-size: 11px;">ROPA</small>
        </a>

        <!-- Requests -->
        <a href="index.php?page=data-requests" class="nav-link p-0 <?= ($currentPage == 'data-requests') ? 'text-primary fw-bold' : 'text-muted' ?>">
            <i class="bi bi-person-gear d-block fs-5"></i>
            <small style="font-size: 11px;">Requests</small>
        </a>

        <!-- Assess -->
        <a href="index.php?page=assessments" class="nav-link p-0 <?= ($currentPage == 'assessments') ? 'text-primary fw-bold' : 'text-muted' ?>">
            <i class="bi bi-clipboard-data d-block fs-5"></i>
            <small style="font-size: 11px;">Assess</small>
        </a>

        <!-- More Dropup Menu -->
        <div class="dropup">
            <a href="#" class="nav-link p-0 <?= $isMoreActive ? 'text-primary fw-bold' : 'text-muted' ?>" id="moreDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots d-block fs-5"></i>
                <small style="font-size: 11px;">More</small>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="moreDropdown" style="min-width: 220px;">
                <li><h6 class="dropdown-header">Governance & Mapping</h6></li>
                <li><a class="dropdown-item py-1" href="index.php?page=data-mapping"><i class="bi bi-diagram-3 me-2"></i> Data Mapping</a></li>
                <li><a class="dropdown-item py-1" href="index.php?page=risk-register"><i class="bi bi-exclamation-triangle me-2"></i> Risk Register</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Security & Admin</h6></li>
                <li><a class="dropdown-item py-1" href="index.php?page=audit-logs"><i class="bi bi-shield-lock me-2"></i> Audit Logs</a></li>
                <li><a class="dropdown-item py-1" href="index.php?page=user-management"><i class="bi bi-people me-2"></i> Users & Roles</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Other Modules</h6></li>
                <li><a class="dropdown-item py-1" href="index.php?page=consent"><i class="bi bi-shield-check me-2"></i> Consent</a></li>
                <li><a class="dropdown-item py-1" href="index.php?page=cookie-governance"><i class="bi bi-cookie me-2"></i> Cookie Governance</a></li>
                <li><a class="dropdown-item py-1" href="index.php?page=vendor-risk"><i class="bi bi-building me-2"></i> Vendor Risk</a></li>
                <li><a class="dropdown-item py-1" href="index.php?page=settings"><i class="bi bi-gear me-2"></i> Settings</a></li>
            </ul>
        </div>

    </div>
</nav>