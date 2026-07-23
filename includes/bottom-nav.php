<?php
// active tab check karne ke liye helper
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<nav class="navbar fixed-bottom navbar-light bg-white border-top py-2">
    <div class="container-fluid d-flex justify-content-around text-center">
        
        <!-- Dashboard -->
        <a href="index.php?page=dashboard" class="nav-link p-0 <?= ($currentPage == 'dashboard') ? 'text-primary fw-bold' : 'text-muted' ?>">
            <i class="bi bi-grid-fill d-block fs-5"></i>
            <small style="font-size: 11px;">Dashboard</small>
        </a>

        <!-- Consent -->
        <a href="index.php?page=consent" class="nav-link p-0 <?= ($currentPage == 'consent') ? 'text-primary fw-bold' : 'text-muted' ?>">
            <i class="bi bi-shield-check d-block fs-5"></i>
            <small style="font-size: 11px;">Consent</small>
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

        <!-- More (Cookie, Discovery, Incidents, Vendor Risk, etc.) -->
        <a href="index.php?page=more" class="nav-link p-0 <?= ($currentPage == 'more') ? 'text-primary fw-bold' : 'text-muted' ?>">
            <i class="bi bi-three-dots d-block fs-5"></i>
            <small style="font-size: 11px;">More</small>
        </a>

    </div>
</nav>