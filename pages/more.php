<?php
// pages/more.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderCard($title, $desc, $icon, $url, $color = 'primary') {
    echo '<a href="' . htmlspecialchars($url) . '" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
            <span class="material-symbols-outlined text-' . $color . ' text-3xl">' . $icon . '</span>
            <div>
                <h3 class="font-semibold text-lg text-on-surface">' . htmlspecialchars($title) . '</h3>
                <p class="text-sm text-on-surface-variant">' . htmlspecialchars($desc) . '</p>
            </div>
          </a>';
}
?>
<div class="space-y-12 animate-in fade-in slide-in-from-top-4 duration-300">
    <div>
        <h2 class="text-display font-display text-primary leading-tight">All Modules</h2>
        <p class="text-body-md text-on-surface-variant mb-6">
            Access the complete PrivacyHQ platform suite.
        </p>
    </div>

    <!-- EXECUTIVE -->
    <?php if (has_permission('view_dashboard')): ?>
    <section>
        <h2 class="text-title-md font-semibold text-on-surface mb-4 border-b border-outline-variant pb-2">Executive</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php renderCard('Executive Dashboard', 'Aggregated KPIs, charts, and platform activities.', 'dashboard', 'index.php?page=executive-dashboard'); ?>
            <?php renderCard('Global Search', 'Search across assessments, tasks, and data records.', 'search', 'index.php?page=search'); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- PRIVACY -->
    <section>
        <h2 class="text-title-md font-semibold text-on-surface mb-4 border-b border-outline-variant pb-2">Privacy & Consent</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (has_permission('manage_consents')) renderCard('Consent Management', 'Manage user consents and preferences.', 'verified_user', 'index.php?page=consent'); ?>
            <?php if (has_permission('manage_dsr')) renderCard('Data Requests (DSR)', 'Manage and fulfill subject rights requests.', 'gavel', 'index.php?page=data-requests'); ?>
            <?php if (has_permission('view_cookie_governance')) renderCard('Cookie Governance', 'Manage cookie consent, scanning, and banners.', 'cookie', 'index.php?page=cookie-governance'); ?>
            <?php if (has_permission('manage_assessments')) renderCard('Privacy Assessments', 'Perform and submit assigned DPIAs.', 'assignment', 'index.php?page=assessments'); ?>
        </div>
    </section>

    <!-- THIRD-PARTY RISK -->
    <?php if (has_permission('manage_vendors')): ?>
    <section>
        <h2 class="text-title-md font-semibold text-on-surface mb-4 border-b border-outline-variant pb-2">Third-Party Risk</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php renderCard('Vendor Management', 'Manage third-party vendors and processors.', 'business_center', 'index.php?page=vendor-management'); ?>
            <?php renderCard('Vendor Risk Assessments', 'Assess and score vendor risks.', 'shield', 'index.php?page=vendor-risk'); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- GRC -->
    <?php if (has_permission('view_dashboard')): ?>
    <section>
        <h2 class="text-title-md font-semibold text-on-surface mb-4 border-b border-outline-variant pb-2">Governance, Risk & Compliance</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php renderCard('Compliance Dashboard', 'Monitor compliance across global frameworks.', 'fact_check', 'index.php?page=compliance-dashboard'); ?>
            <?php renderCard('Risk Register', 'Log, assess, and manage privacy risks.', 'warning', 'index.php?page=risk-register'); ?>
            <?php renderCard('Frameworks & Controls', 'Manage frameworks, requirements, and controls.', 'rule', 'index.php?page=frameworks'); ?>
            <?php if (has_permission('manage_policies')) renderCard('Policies', 'Create, edit, and track compliance documents.', 'description', 'index.php?page=policies'); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- DATA GOVERNANCE -->
    <?php if (has_permission('view_dashboard')): ?>
    <section>
        <h2 class="text-title-md font-semibold text-on-surface mb-4 border-b border-outline-variant pb-2">Data Governance</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php renderCard('Data Discovery (DSPM)', 'Discover, classify, and monitor sensitive data.', 'travel_explore', 'index.php?page=data-discovery'); ?>
            <?php if (has_permission('manage_ropa')) renderCard('ROPA', 'Records of Processing Activities.', 'table_view', 'index.php?page=ropa'); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- OPERATIONS -->
    <section>
        <h2 class="text-title-md font-semibold text-on-surface mb-4 border-b border-outline-variant pb-2">Operations</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (has_permission('view_dashboard')) renderCard('My Tasks', 'View and manage your assigned workflow tasks.', 'task_alt', 'index.php?page=my-tasks'); ?>
            <?php if (has_permission('manage_incidents')) renderCard('Incident Management', 'Track and remediate privacy incidents.', 'notification_important', 'index.php?page=incident-management'); ?>
            <?php if (has_permission('view_reports')) renderCard('Reports', 'View governance and compliance reports.', 'analytics', 'index.php?page=reports'); ?>
        </div>
    </section>

    <!-- ADMINISTRATION -->
    <?php if (has_permission('view_dashboard')): ?>
    <section>
        <h2 class="text-title-md font-semibold text-on-surface mb-4 border-b border-outline-variant pb-2">Administration</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (has_permission('manage_users')) renderCard('User Management', 'Manage system users and access.', 'group', 'index.php?page=user-management'); ?>
            <?php if (has_permission('manage_users')) renderCard('Role Policies', 'Define custom roles and permissions.', 'admin_panel_settings', 'index.php?page=role-management'); ?>
            <?php if (has_permission('view_audit_logs')) renderCard('Audit Logs', 'View system user actions and compliance logs.', 'history', 'index.php?page=audit-logs'); ?>
            <?php renderCard('Settings', 'Manage system and account configuration.', 'settings', 'index.php?page=settings'); ?>
        </div>
    </section>
    <?php endif; ?>
</div>