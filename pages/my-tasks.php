<?php
// pages/my-tasks.php
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header("Location: login.php");
    exit;
}
?>

<div class="space-y-lg">
    <!-- Header -->
    <div>
        <h1 class="text-display font-display text-primary leading-tight">My Task Workspace</h1>
        <p class="text-body-md text-on-surface-variant">Central console to process, action, and sign-off assigned task workflows.</p>
    </div>

    <!-- Quick Stats Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
        <button onclick="switchTaskFilter('Pending')" id="card-Pending" class="text-left bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between hover:border-primary transition-all">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Pending Tasks</span>
                <p class="text-display font-bold text-primary mt-base" id="count-Pending">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">assignment_late</span>
            </div>
        </button>

        <button onclick="switchTaskFilter('DueToday')" id="card-DueToday" class="text-left bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between hover:border-amber-500 transition-all">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">Due Today</span>
                <p class="text-display font-bold text-amber-600 mt-base" id="count-DueToday">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-amber-600">today</span>
            </div>
        </button>

        <button onclick="switchTaskFilter('Overdue')" id="card-Overdue" class="text-left bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between hover:border-red-500 transition-all">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Overdue</span>
                <p class="text-display font-bold text-red-600 mt-base" id="count-Overdue">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">warning</span>
            </div>
        </button>

        <button onclick="switchTaskFilter('Completed')" id="card-Completed" class="text-left bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between hover:border-emerald-500 transition-all">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Completed</span>
                <p class="text-display font-bold text-emerald-600 mt-base" id="count-Completed">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            </div>
        </button>
    </div>

    <!-- Tasks List Container -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center md:justify-between gap-sm">
            <div class="flex gap-md border-b border-outline-variant pb-base">
                <button onclick="switchWorkspaceTab('Tasks')" id="tab-Tasks" class="font-bold text-body-md text-primary border-b-2 border-primary pb-1">My Tasks</button>
                <button onclick="switchWorkspaceTab('Assessments')" id="tab-Assessments" class="font-bold text-body-md text-on-surface-variant pb-1 hover:text-primary">My Assessments</button>
                <button onclick="switchWorkspaceTab('DSR')" id="tab-DSR" class="font-bold text-body-md text-on-surface-variant pb-1 hover:text-primary">My DSRs</button>
            </div>
            <div class="flex items-center gap-sm">
                <input type="text" id="taskSearch" oninput="renderWorkspaceData()" placeholder="Search..." class="border border-outline-variant rounded-lg px-3 py-1.5 text-body-sm focus:border-primary focus:outline-none bg-surface">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                        <th class="p-md">Task Details</th>
                        <th class="p-md">Module</th>
                        <th class="p-md">Priority</th>
                        <th class="p-md">Due Date</th>
                        <th class="p-md">Status</th>
                        <th class="p-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tasksTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr>
                        <td colspan="6" class="p-md text-center text-on-surface-variant">Loading tasks...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/js/my-tasks.js"></script>
