<?php
// pages/executive-dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<div class="space-y-lg">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-display font-display text-primary leading-tight">Executive Compliance Console</h1>
            <p class="text-body-md text-on-surface-variant">Real-time risk, workflow, task, and security SLA dashboards across all platform modules.</p>
        </div>
        <button onclick="loadDashboard()" class="inline-flex items-center gap-xs px-4 py-2 bg-primary text-white font-semibold rounded-lg shadow hover:bg-primary/95 transition-all text-body-sm">
            <span class="material-symbols-outlined text-[18px]">sync</span>
            <span>Refresh Data</span>
        </button>
    </div>

    <!-- Filters -->
    <div class="p-md bg-surface border border-outline-variant rounded-xl flex flex-wrap items-center gap-md shadow-sm">
        <div class="flex flex-col gap-xs">
            <label class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Date Range</label>
            <select id="filter-date" class="border border-outline-variant rounded-lg px-3 py-1.5 text-body-sm bg-surface">
                <option value="all">All Time</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
            </select>
        </div>
        <div class="flex flex-col gap-xs">
            <label class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Module</label>
            <select id="filter-module" class="border border-outline-variant rounded-lg px-3 py-1.5 text-body-sm bg-surface">
                <option value="all">All Modules</option>
                <option value="Assessment">Assessments</option>
                <option value="Incident">Incidents</option>
                <option value="DSR">DSR Requests</option>
            </select>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
        <!-- Tasks KPI -->
        <div class="bg-surface border border-outline-variant p-md rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Pending Tasks</span>
                <p id="kpi-tasks" class="text-display font-bold text-primary mt-base">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">assignment</span>
            </div>
        </div>

        <!-- Incident Severity KPI -->
        <div class="bg-surface border border-outline-variant p-md rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Open Incidents</span>
                <p id="kpi-incidents" class="text-display font-bold text-red-600 mt-base">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">error</span>
            </div>
        </div>

        <!-- Risks KPI -->
        <div class="bg-surface border border-outline-variant p-md rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">High Risks</span>
                <p id="kpi-risks" class="text-display font-bold text-amber-600 mt-base">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-amber-600">warning</span>
            </div>
        </div>

        <!-- DSR KPI -->
        <div class="bg-surface border border-outline-variant p-md rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Active DSRs</span>
                <p id="kpi-dsr" class="text-display font-bold text-emerald-600 mt-base">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-600">vpn_key</span>
            </div>
        </div>
    </div>

    <!-- Charts & Lists -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
        <!-- Incident Distribution Chart -->
        <div class="bg-surface border border-outline-variant rounded-xl p-md shadow-sm">
            <h3 class="font-bold text-title-md text-on-surface mb-md">Incident Severity Profile</h3>
            <div class="relative h-60">
                <canvas id="incidentChartCanvas"></canvas>
            </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="bg-surface border border-outline-variant rounded-xl p-md shadow-sm">
            <h3 class="font-bold text-title-md text-on-surface mb-md">Upcoming Deadlines (SLA)</h3>
            <div id="upcomingDeadlinesList" class="space-y-sm">
                <!-- Dynamically loaded -->
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-surface border border-outline-variant rounded-xl p-md shadow-sm">
            <h3 class="font-bold text-title-md text-on-surface mb-md">Pending Workflow Approvals</h3>
            <div id="pendingApprovalsList" class="space-y-sm">
                <!-- Dynamically loaded -->
            </div>
        </div>
    </div>

    <!-- Recent Activities Timeline -->
    <div class="bg-surface border border-outline-variant rounded-xl p-md shadow-sm">
        <h3 class="font-bold text-title-md text-on-surface mb-md">Recent Platform Activities</h3>
        <div id="recentActivitiesList" class="space-y-sm">
            <!-- Dynamically loaded -->
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let incidentChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
});

function loadDashboard() {
    fetch('backend/api/dashboard/executive.php')
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const data = res.data;
                
                // Update KPIs
                document.getElementById('kpi-tasks').innerText = data.kpis.pending_tasks || 0;
                document.getElementById('kpi-incidents').innerText = data.kpis.active_incidents || 0;
                document.getElementById('kpi-risks').innerText = data.kpis.high_risks || 0;
                document.getElementById('kpi-dsr').innerText = data.kpis.pending_dsr || 0;

                // Render Deadlines
                const deadList = document.getElementById('upcomingDeadlinesList');
                if (data.upcoming_deadlines.length === 0) {
                    deadList.innerHTML = '<div class="text-caption text-on-surface-variant">No upcoming deadlines.</div>';
                } else {
                    deadList.innerHTML = data.upcoming_deadlines.map(d => `
                        <div class="flex items-center justify-between p-xs hover:bg-surface-container-low rounded-lg transition-colors border border-transparent hover:border-outline-variant">
                            <div>
                                <div class="font-semibold text-body-sm text-on-surface">${escapeHtml(d.title)}</div>
                                <div class="text-[10px] text-on-surface-variant uppercase tracking-wider">${d.module} &bull; ${d.priority}</div>
                            </div>
                            <div class="text-caption font-bold text-red-600">${d.due_date}</div>
                        </div>
                    `).join('');
                }

                // Render Approvals
                const appList = document.getElementById('pendingApprovalsList');
                if (data.pending_approvals.length === 0) {
                    appList.innerHTML = '<div class="text-caption text-on-surface-variant">No pending approvals.</div>';
                } else {
                    appList.innerHTML = data.pending_approvals.map(a => `
                        <div class="flex items-center justify-between p-xs hover:bg-surface-container-low rounded-lg transition-colors border border-transparent hover:border-outline-variant">
                            <div>
                                <div class="font-semibold text-body-sm text-on-surface">${escapeHtml(a.title)}</div>
                                <div class="text-[10px] text-on-surface-variant uppercase tracking-wider">${a.module}</div>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 rounded-full">Awaiting review</span>
                        </div>
                    `).join('');
                }

                // Render Activities Timeline
                const actList = document.getElementById('recentActivitiesList');
                if (data.recent_activities.length === 0) {
                    actList.innerHTML = '<div class="text-caption text-on-surface-variant">No recent activities.</div>';
                } else {
                    actList.innerHTML = data.recent_activities.map(act => `
                        <div class="flex gap-sm items-start p-xs border-b border-outline-variant last:border-0">
                            <div class="w-2 h-2 mt-1.5 rounded-full bg-primary flex-shrink-0"></div>
                            <div>
                                <div class="text-body-sm text-on-surface">
                                    <span class="font-bold">${escapeHtml(act.user_email)}</span> 
                                    performed <strong>${escapeHtml(act.action)}</strong> on ${act.module} (ID: ${act.record_id})
                                </div>
                                <div class="text-[10px] text-on-surface-variant mt-0.5">${act.created_at}</div>
                            </div>
                        </div>
                    `).join('');
                }

                // Render Chart.js
                renderChart(data.incident_chart);
            }
        })
        .catch(err => console.error(err));
}

function renderChart(chartData) {
    const ctx = document.getElementById('incidentChartCanvas').getContext('2d');
    
    // Destroy existing if refresh
    if (incidentChart) {
        incidentChart.destroy();
    }

    const labels = chartData.map(d => d.severity);
    const counts = chartData.map(d => d.count);

    incidentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444', '#10b981'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            }
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
</script>
