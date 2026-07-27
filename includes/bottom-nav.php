<?php
// governance/includes/bottom-nav.php
$currentPage = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';
?>
<!-- Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-0 right-0 w-full z-50 bg-surface shadow-[0px_-2px_4px_rgba(0,0,0,0.04)] flex justify-around items-center h-16 px-2 border-t border-outline-variant/30">
    <!-- Dashboard -->
    <a href="index.php?page=dashboard" class="flex flex-col items-center justify-center px-5 py-1.5 rounded-xl no-underline transition-all duration-200 <?php echo ($currentPage === 'dashboard') ? 'text-primary bg-secondary-fixed font-semibold' : 'text-on-surface-variant hover:text-primary'; ?>">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="font-label-md text-label-md no-underline">Dashboard</span>
    </a>

    <!-- Consent -->
    <a href="index.php?page=consent" class="flex flex-col items-center justify-center px-5 py-1.5 rounded-xl no-underline transition-all duration-200 <?php echo ($currentPage === 'consent') ? 'text-primary bg-secondary-fixed font-semibold' : 'text-on-surface-variant hover:text-primary'; ?>">
        <span class="material-symbols-outlined">verified_user</span>
        <span class="font-label-md text-label-md no-underline">Consent</span>
    </a>

    <!-- Requests -->
    <a href="index.php?page=data-requests" class="flex flex-col items-center justify-center px-5 py-1.5 rounded-xl no-underline transition-all duration-200 <?php echo in_array($currentPage, ['data-requests', 'dsar', 'dsr-management']) ? 'text-primary bg-secondary-fixed font-semibold' : 'text-on-surface-variant hover:text-primary'; ?>">
        <span class="material-symbols-outlined">gavel</span>
        <span class="font-label-md text-label-md no-underline">Requests</span>
    </a>

    <!-- Assess -->
    <a href="index.php?page=assessments" class="flex flex-col items-center justify-center px-5 py-1.5 rounded-xl no-underline transition-all duration-200 <?php echo ($currentPage === 'assessments') ? 'text-primary bg-secondary-fixed font-semibold' : 'text-on-surface-variant hover:text-primary'; ?>">
        <span class="material-symbols-outlined">assignment_turned_in</span>
        <span class="font-label-md text-label-md no-underline">Assess</span>
    </a>

    <!-- More -->
    <a href="index.php?page=more" class="flex flex-col items-center justify-center px-5 py-1.5 rounded-xl no-underline transition-all duration-200 <?php echo ($currentPage === 'more' || in_array($currentPage, ['cookie-governance','data-discovery','data-mapping','incident-management','vendor-risk','vendor-management','risk-register','ropa','policies','reports','settings','user-management','audit-logs'])) ? 'text-primary bg-secondary-fixed font-semibold' : 'text-on-surface-variant hover:text-primary'; ?>">
        <span class="material-symbols-outlined">menu</span>
        <span class="font-label-md text-label-md no-underline">More</span>
    </a>
</nav>