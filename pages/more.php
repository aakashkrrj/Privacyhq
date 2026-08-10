<?php
// pages/more.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="space-y-lg animate-in fade-in slide-in-from-top-4 duration-300">
    <div>
        <h2 class="text-display font-display text-primary leading-tight">More</h2>
        <p class="text-body-md text-on-surface-variant mb-6">
            Access additional governance modules and settings.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Vendor Risk -->
        <?php if (has_permission('manage_vendors')): ?>
            <a href="index.php?page=vendor-risk" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">business_center</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Vendor Risk</h3>
                    <p class="text-sm text-on-surface-variant">Manage third-party risks and vendors.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- My Assessments -->
        <?php if (has_permission('view_dashboard')): ?>
            <a href="index.php?page=my-assessments" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">assignment</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">My Assessments</h3>
                    <p class="text-sm text-on-surface-variant">Perform and submit your assigned DPIAs.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Reports -->
        <?php if (has_permission('view_reports')): ?>
            <a href="index.php?page=reports" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">analytics</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Reports</h3>
                    <p class="text-sm text-on-surface-variant">View governance and compliance reports.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Settings -->
        <?php if (has_permission('view_dashboard')): ?>
            <a href="index.php?page=settings" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">settings</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Settings</h3>
                    <p class="text-sm text-on-surface-variant">Manage system and account configuration.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- ROPA -->
        <?php if (has_permission('manage_ropa')): ?>
            <a href="index.php?page=ropa" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">fact_check</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">ROPA</h3>
                    <p class="text-sm text-on-surface-variant">Records of Processing Activities.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Risk Register -->
        <?php if (has_permission('view_dashboard')): ?>
            <a href="index.php?page=risk-register" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">warning</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Risk Register</h3>
                    <p class="text-sm text-on-surface-variant">Log, assess, and manage privacy risks.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Policies & Version Control -->
        <?php if (has_permission('manage_policies')): ?>
            <a href="index.php?page=policies" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">description</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Policies</h3>
                    <p class="text-sm text-on-surface-variant">Create, edit, and track compliance policy documents.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- User Management -->
        <?php if (has_permission('manage_users')): ?>
            <a href="index.php?page=user-management" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">group</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">User Management</h3>
                    <p class="text-sm text-on-surface-variant">Manage system users, roles, and permissions.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Role Management -->
        <?php if (has_permission('manage_users')): ?>
            <a href="index.php?page=role-management" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">admin_panel_settings</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Role Policies</h3>
                    <p class="text-sm text-on-surface-variant">Define custom roles, baselines, and permissions.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- My Tasks Workspace -->
        <?php if (has_permission('view_dashboard')): ?>
            <a href="index.php?page=my-tasks" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">task_alt</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">My Tasks</h3>
                    <p class="text-sm text-on-surface-variant">View and manage your assigned workflow tasks.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Executive Dashboard -->
        <?php if (has_permission('view_dashboard')): ?>
            <a href="index.php?page=executive-dashboard" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">dashboard</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Compliance Console</h3>
                    <p class="text-sm text-on-surface-variant">Aggregated KPIs, charts, and platform activities.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Global Search -->
        <?php if (has_permission('view_dashboard')): ?>
            <a href="index.php?page=search" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">search</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Global Search</h3>
                    <p class="text-sm text-on-surface-variant">Search across assessments, tasks, and data records.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Audit Logs -->
        <?php if (has_permission('view_audit_logs')): ?>
            <a href="index.php?page=audit-logs" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">history</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Audit Logs</h3>
                    <p class="text-sm text-on-surface-variant">View system user actions and compliance audit logs.</p>
                </div>
            </a>
        <?php endif; ?>

        <!-- Incident Management -->
        <?php if (has_permission('manage_incidents')): ?>
            <a href="index.php?page=incident-management" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                <span class="material-symbols-outlined text-primary text-3xl">notification_important</span>
                <div>
                    <h3 class="font-semibold text-lg text-on-surface">Incidents</h3>
                    <p class="text-sm text-on-surface-variant">Track and remediate privacy incidents.</p>
                </div>
            </a>
        <?php endif; ?>

    </div>
</div>