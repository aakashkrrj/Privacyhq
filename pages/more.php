<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>More - PrivacyHQ</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

    <style>
        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24;
        }
    </style>
</head>

<body class="bg-[#F3F2F1] min-h-screen pb-24">

    <!-- Header -->
    <header class="fixed top-0 left-0 w-full h-16 bg-white border-b z-40
                   flex items-center justify-between px-6">

        <div class="flex items-center gap-2 text-blue-700">
            <span class="material-symbols-outlined">shield</span>
            <h1 class="text-xl font-bold">PrivacyHQ</h1>
        </div>

        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined">notifications</span>
            <span class="material-symbols-outlined">account_circle</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-24 px-6 max-w-4xl mx-auto">

        <h2 class="text-2xl font-bold mb-2">More</h2>

        <p class="text-gray-600 mb-6">
            Access additional governance modules and settings.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Vendor Risk -->
            <?php if (has_permission('manage_vendors')): ?>
                <a href="index.php?page=vendor-risk" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">business_center</span>
                    <div>
                        <h3 class="font-semibold text-lg">Vendor Risk</h3>
                        <p class="text-sm text-gray-500">Manage third-party risks and vendors.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- My Assessments -->
            <?php if (has_permission('view_dashboard')): ?>
                <a href="index.php?page=my-assessments" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">assignment</span>
                    <div>
                        <h3 class="font-semibold text-lg">My Assessments</h3>
                        <p class="text-sm text-gray-500">Perform and submit your assigned DPIAs.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Reports -->
            <?php if (has_permission('view_reports')): ?>
                <a href="index.php?page=reports" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">analytics</span>
                    <div>
                        <h3 class="font-semibold text-lg">Reports</h3>
                        <p class="text-sm text-gray-500">View governance and compliance reports.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Settings -->
            <?php if (has_permission('view_dashboard')): ?>
                <a href="index.php?page=settings" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">settings</span>
                    <div>
                        <h3 class="font-semibold text-lg">Settings</h3>
                        <p class="text-sm text-gray-500">Manage system and account configuration.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- ROPA -->
            <?php if (has_permission('manage_ropa')): ?>
                <a href="index.php?page=ropa" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">fact_check</span>
                    <div>
                        <h3 class="font-semibold text-lg">ROPA</h3>
                        <p class="text-sm text-gray-500">Records of Processing Activities.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Risk Register -->
            <?php if (has_permission('view_dashboard')): ?>
                <a href="index.php?page=risk-register" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">warning</span>
                    <div>
                        <h3 class="font-semibold text-lg">Risk Register</h3>
                        <p class="text-sm text-gray-500">Log, assess, and manage privacy risks.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Policies & Version Control -->
            <?php if (has_permission('manage_policies')): ?>
                <a href="index.php?page=policies" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">description</span>
                    <div>
                        <h3 class="font-semibold text-lg">Policies</h3>
                        <p class="text-sm text-gray-500">Create, edit, and track compliance policy documents.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- User Management -->
            <?php if (has_permission('manage_users')): ?>
                <a href="index.php?page=user-management" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">group</span>
                    <div>
                        <h3 class="font-semibold text-lg">User Management</h3>
                        <p class="text-sm text-gray-500">Manage system users, roles, and permissions.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Role Management -->
            <?php if (has_permission('manage_users')): ?>
                <a href="index.php?page=role-management" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">admin_panel_settings</span>
                    <div>
                        <h3 class="font-semibold text-lg">Role Policies</h3>
                        <p class="text-sm text-gray-500">Define custom roles, baselines, and permissions.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- My Tasks Workspace -->
            <?php if (has_permission('view_dashboard')): ?>
                <a href="index.php?page=my-tasks" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">task_alt</span>
                    <div>
                        <h3 class="font-semibold text-lg">My Tasks</h3>
                        <p class="text-sm text-gray-500">View and manage your assigned workflow tasks.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Executive Dashboard -->
            <?php if (has_permission('view_dashboard')): ?>
                <a href="index.php?page=executive-dashboard" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">dashboard</span>
                    <div>
                        <h3 class="font-semibold text-lg">Compliance Console</h3>
                        <p class="text-sm text-gray-500">Aggregated KPIs, charts, and platform activities.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Global Search -->
            <?php if (has_permission('view_dashboard')): ?>
                <a href="index.php?page=search" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">search</span>
                    <div>
                        <h3 class="font-semibold text-lg">Global Search</h3>
                        <p class="text-sm text-gray-500">Search across assessments, tasks, and data records.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Audit Logs -->
            <?php if (has_permission('view_audit_logs')): ?>
                <a href="index.php?page=audit-logs" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">history</span>
                    <div>
                        <h3 class="font-semibold text-lg">Audit Logs</h3>
                        <p class="text-sm text-gray-500">View system user actions and compliance audit logs.</p>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Incident Management -->
            <?php if (has_permission('manage_incidents')): ?>
                <a href="index.php?page=incident-management" class="bg-white border rounded-xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <span class="material-symbols-outlined text-blue-700 text-3xl">notification_important</span>
                    <div>
                        <h3 class="font-semibold text-lg">Incidents</h3>
                        <p class="text-sm text-gray-500">Track and remediate privacy incidents.</p>
                    </div>
                </a>
            <?php endif; ?>

        </div>
    </main>


    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 w-full z-50
                bg-white shadow-[0px_-2px_4px_rgba(0,0,0,0.04)]
                flex justify-around items-center h-16 px-2">

        <!-- Dashboard -->
        <a href="index.php?page=dashboard"
           class="flex flex-col items-center justify-center text-gray-600 px-4 py-1">

            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-xs">Dashboard</span>
        </a>

        <!-- Consent -->
        <a href="index.php?page=consent-management"
           class="flex flex-col items-center justify-center text-gray-600 px-4 py-1">

            <span class="material-symbols-outlined">verified_user</span>
            <span class="text-xs">Consent</span>
        </a>

        <!-- Requests -->
        <a href="index.php?page=data-requests"
           class="flex flex-col items-center justify-center text-gray-600 px-4 py-1">

            <span class="material-symbols-outlined">gavel</span>
            <span class="text-xs">Requests</span>
        </a>

        <!-- Assess -->
        <a href="index.php?page=assessments"
           class="flex flex-col items-center justify-center text-gray-600 px-4 py-1">

            <span class="material-symbols-outlined">assignment_turned_in</span>
            <span class="text-xs">Assess</span>
        </a>

        <!-- More Active -->
        <a href="index.php?page=more"
           class="flex flex-col items-center justify-center text-blue-700
                  bg-blue-100 rounded-xl px-4 py-1">

            <span class="material-symbols-outlined">menu</span>
            <span class="text-xs">More</span>
        </a>
        <a href="index.php?page=vendor-management" class="...">
    Vendor Management
</a>

<a href="index.php?page=user-management" class="...">
    User Management
</a>

    </nav>

</body>
</html>