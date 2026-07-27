<?php
require_once __DIR__ . '/../includes/db.php';

$incidents = [];
if (isset($conn) && !$conn->connect_error) {
    $result = $conn->query("SELECT * FROM incidents ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $incidents[] = $row;
        }
    }
}
?>

<div class="p-lg space-y-md">
    <!-- Header Area -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-sm">
        <div>
            <h1 class="text-headline-lg text-on-surface font-semibold">Privacy Incident Management</h1>
            <p class="text-body-md text-on-surface-variant">Track, investigate, and notify data breaches under regulatory requirements.</p>
        </div>
        <div>
            <button onclick="openIncidentModal()" class="inline-flex items-center gap-xs bg-error text-on-error px-md py-sm rounded-lg hover:opacity-90 transition-opacity text-label-md font-medium shadow-sm">
                <span class="material-symbols-outlined text-[18px]">warning</span>
                Log New Incident
            </button>
        </div>
    </div>
<!-- ================= INCIDENT KPI DASHBOARD ================= -->

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-md">

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">Total Incidents</p>
        <h2 class="text-display-small font-semibold text-primary mt-xs">
            <?= count($incidents) ?>
        </h2>
        <p class="text-body-sm text-outline mt-xs">
            Logged incidents
        </p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">Open Incidents</p>
        <h2 class="text-display-small font-semibold text-error mt-xs">
            8
        </h2>
        <p class="text-body-sm text-outline mt-xs">
            Awaiting resolution
        </p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">Resolved</p>
        <h2 class="text-display-small font-semibold text-secondary mt-xs">
            27
        </h2>
        <p class="text-body-sm text-outline mt-xs">
            Successfully closed
        </p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">Critical Incidents</p>
        <h2 class="text-display-small font-semibold text-error mt-xs">
            3
        </h2>
        <p class="text-body-sm text-outline mt-xs">
            Highest priority
        </p>
    </div>

</div>

<!-- ================= SEARCH & FILTER ================= -->

<div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">

    <h2 class="text-title-medium font-semibold mb-md">
        Search & Filter Incidents
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-md">

        <input
            type="text"
            placeholder="Search Incident..."
            class="rounded-lg border-outline-variant bg-surface text-on-surface">

        <select class="rounded-lg border-outline-variant bg-surface">

            <option>All Severity</option>
            <option>Critical</option>
            <option>High</option>
            <option>Medium</option>
            <option>Low</option>

        </select>

        <select class="rounded-lg border-outline-variant bg-surface">

            <option>All Status</option>
            <option>Open</option>
            <option>Resolved</option>

        </select>

        <input
            type="date"
            class="rounded-lg border-outline-variant bg-surface">

        <button
            class="bg-primary text-on-primary rounded-lg hover:opacity-90">

            Search

        </button>

    </div>

</div>

<!-- ================= INCIDENT ANALYTICS ================= -->

<div class="grid grid-cols-1 lg:grid-cols-2 gap-md">

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">

        <h2 class="text-title-medium font-semibold mb-md">
            Incident Distribution
        </h2>

        <div class="space-y-md">

            <div>

                <div class="flex justify-between text-body-sm mb-xs">
                    <span>Critical</span>
                    <span>22%</span>
                </div>

                <div class="w-full h-2 rounded-full bg-surface-container-high">
                    <div class="h-2 rounded-full bg-error" style="width:22%"></div>
                </div>

            </div>

            <div>

                <div class="flex justify-between text-body-sm mb-xs">
                    <span>High</span>
                    <span>34%</span>
                </div>

                <div class="w-full h-2 rounded-full bg-surface-container-high">
                    <div class="h-2 rounded-full bg-primary" style="width:34%"></div>
                </div>

            </div>

            <div>

                <div class="flex justify-between text-body-sm mb-xs">
                    <span>Medium</span>
                    <span>28%</span>
                </div>

                <div class="w-full h-2 rounded-full bg-surface-container-high">
                    <div class="h-2 rounded-full bg-tertiary" style="width:28%"></div>
                </div>

            </div>

            <div>

                <div class="flex justify-between text-body-sm mb-xs">
                    <span>Low</span>
                    <span>16%</span>
                </div>

                <div class="w-full h-2 rounded-full bg-surface-container-high">
                    <div class="h-2 rounded-full bg-secondary" style="width:16%"></div>
                </div>

            </div>

        </div>

    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">

        <h2 class="text-title-medium font-semibold mb-md">
            Response Performance
        </h2>

        <div class="grid grid-cols-2 gap-md">

            <div class="bg-primary-container rounded-lg p-md">
                <p class="text-body-sm">Avg Response</p>
                <h3 class="text-headline-small font-semibold mt-xs">
                    1.8 hrs
                </h3>
            </div>

            <div class="bg-secondary-container rounded-lg p-md">
                <p class="text-body-sm">Avg Resolution</p>
                <h3 class="text-headline-small font-semibold mt-xs">
                    2.4 Days
                </h3>
            </div>

            <div class="bg-tertiary-container rounded-lg p-md">
                <p class="text-body-sm">Escalated</p>
                <h3 class="text-headline-small font-semibold mt-xs">
                    6
                </h3>
            </div>

            <div class="bg-error-container rounded-lg p-md">
                <p class="text-body-sm">SLA Compliance</p>
                <h3 class="text-headline-small font-semibold mt-xs">
                    94%
                </h3>
            </div>

        </div>

    </div>

</div>
    <!-- Data Table Card -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/30 text-outline text-label-md">
                        <th class="py-sm px-md font-medium">Incident ID</th>
                        <th class="py-sm px-md font-medium">Severity</th>
                        <th class="py-sm px-md font-medium">Summary</th>
                        <th class="py-sm px-md font-medium">Data Subjects Impacted</th>
                        <th class="py-sm px-md font-medium">Status</th>
                        <th class="py-sm px-md font-medium">Created Date</th>
                        <th class="py-sm px-md font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-body-md">
                    <?php if (empty($incidents)): ?>
                        <tr>
                            <td colspan="7" class="py-xl text-center text-outline">
                                No incidents reported yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($incidents as $incident): ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="py-md px-md font-medium text-on-surface">
                                    #INC-<?php echo htmlspecialchars($incident['id']); ?>
                                </td>
                                <td class="py-md px-md">
                                    <?php 
                                        $sev = $incident['severity'] ?? 'Medium';
                                        $sevClass = match($sev) {
                                            'Critical', 'High' => 'bg-error-container text-on-error-container',
                                            'Medium' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
                                            'Low' => 'bg-surface-container-high text-on-surface-variant',
                                            default => 'bg-surface-container text-on-surface'
                                        };
                                    ?>
                                    <span class="inline-block px-sm py-[2px] rounded-full text-caption font-semibold <?php echo $sevClass; ?>">
                                        <?php echo htmlspecialchars($sev); ?>
                                    </span>
                                </td>
                                <td class="py-md px-md text-on-surface max-w-xs truncate">
                                    <?php echo htmlspecialchars($incident['summary']); ?>
                                </td>
                                <td class="py-md px-md text-on-surface-variant">
                                    <?php echo number_format($incident['impacted_records'] ?? 0); ?>
                                </td>
                                <td class="py-md px-md">
                                    <?php 
                                        $status = $incident['status'] ?? 'Open';
                                        $statusClass = ($status === 'Resolved') 
                                            ? 'bg-secondary-fixed text-on-secondary-fixed-variant' 
                                            : 'bg-error/10 text-error';
                                    ?>
                                    <span class="inline-block px-sm py-[2px] rounded-full text-caption font-medium <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td class="py-md px-md text-outline">
                                    <?php echo date('M d, Y', strtotime($incident['created_at'])); ?>
                                </td>
                                <td class="py-md px-md text-right">
                                    <button class="text-primary hover:underline font-label-md font-medium">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- ================= INCIDENT RESPONSE WORKFLOW ================= -->

<div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">

    <h2 class="text-title-medium font-semibold mb-lg">
        Incident Response Workflow
    </h2>

    <div class="flex flex-wrap justify-between items-center gap-md text-center">

        <div class="flex-1 min-w-[110px]">
            <div class="w-14 h-14 rounded-full bg-error-container text-on-error-container flex items-center justify-center font-semibold mx-auto">
                1
            </div>
            <p class="mt-sm text-label-large">Reported</p>
        </div>

        <span class="text-outline text-xl">→</span>

        <div class="flex-1 min-w-[110px]">
            <div class="w-14 h-14 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-semibold mx-auto">
                2
            </div>
            <p class="mt-sm text-label-large">Investigating</p>
        </div>

        <span class="text-outline text-xl">→</span>

        <div class="flex-1 min-w-[110px]">
            <div class="w-14 h-14 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center font-semibold mx-auto">
                3
            </div>
            <p class="mt-sm text-label-large">Containment</p>
        </div>

        <span class="text-outline text-xl">→</span>

        <div class="flex-1 min-w-[110px]">
            <div class="w-14 h-14 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-semibold mx-auto">
                4
            </div>
            <p class="mt-sm text-label-large">Recovery</p>
        </div>

        <span class="text-outline text-xl">→</span>

        <div class="flex-1 min-w-[110px]">
            <div class="w-14 h-14 rounded-full bg-primary text-on-primary flex items-center justify-center font-semibold mx-auto">
                5
            </div>
            <p class="mt-sm text-label-large">Closed</p>
        </div>

    </div>

</div>

<!-- ================= COMPLIANCE SUMMARY ================= -->

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-md">

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">72-Hour GDPR SLA</p>
        <h2 class="text-headline-medium text-primary mt-xs">96%</h2>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">Authority Notifications</p>
        <h2 class="text-headline-medium text-secondary mt-xs">48</h2>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">Open Investigations</p>
        <h2 class="text-headline-medium text-error mt-xs">8</h2>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">
        <p class="text-body-sm text-outline">Resolved Incidents</p>
        <h2 class="text-headline-medium text-secondary mt-xs">71</h2>
    </div>

</div>

<!-- ================= RECENT INCIDENT ACTIVITY ================= -->

<div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">

    <h2 class="text-title-medium font-semibold mb-md">
        Recent Incident Activity
    </h2>

    <div class="space-y-md">

        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <div>
                <p class="font-medium">Unauthorized access detected</p>
                <small class="text-outline">Today • 09:15 AM</small>
            </div>
            <span class="px-sm py-[2px] rounded-full bg-error-container text-on-error-container text-caption">
                Critical
            </span>
        </div>

        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <div>
                <p class="font-medium">Malware incident resolved</p>
                <small class="text-outline">Today • 08:10 AM</small>
            </div>
            <span class="px-sm py-[2px] rounded-full bg-secondary-container text-on-secondary-container text-caption">
                Resolved
            </span>
        </div>

        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <div>
                <p class="font-medium">Third-party vendor breach reported</p>
                <small class="text-outline">Yesterday</small>
            </div>
            <span class="px-sm py-[2px] rounded-full bg-primary-container text-on-primary-container text-caption">
                Investigating
            </span>
        </div>

        <div class="flex justify-between items-center">
            <div>
                <p class="font-medium">Database recovery completed</p>
                <small class="text-outline">Yesterday</small>
            </div>
            <span class="px-sm py-[2px] rounded-full bg-secondary-container text-on-secondary-container text-caption">
                Closed
            </span>
        </div>

    </div>

</div>

<!-- ================= QUICK ACTIONS ================= -->

<div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-md shadow-sm">

    <h2 class="text-title-medium font-semibold mb-md">
        Quick Actions
    </h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-md">

        <button class="bg-error text-on-error rounded-lg py-md font-medium hover:opacity-90">
            + Log Incident
        </button>

        <button class="bg-primary text-on-primary rounded-lg py-md font-medium hover:opacity-90">
            Export Report
        </button>

        <button class="bg-secondary text-on-secondary rounded-lg py-md font-medium hover:opacity-90">
            Notify Authority
        </button>

        <button class="bg-tertiary text-on-tertiary rounded-lg py-md font-medium hover:opacity-90">
            Generate RCA
        </button>

    </div>

</div>

<!-- Log Incident Tailwind Modal -->
<div id="incidentModal" class="fixed inset-0 z-50 hidden bg-inverse-surface/40 backdrop-blur-sm flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 w-full max-w-lg overflow-hidden shadow-xl">
        <div class="p-md border-b border-outline-variant/20 flex items-center justify-between">
            <h3 class="text-title-md font-semibold text-error flex items-center gap-xs">
                <span class="material-symbols-outlined">warning</span> Log New Incident
            </h3>
            <button onclick="closeIncidentModal()" class="text-outline hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="incidentForm" class="p-md space-y-md">
            <div>
                <label class="block text-label-md font-medium text-on-surface mb-xs">Incident Summary</label>
                <textarea name="summary" rows="3" class="w-full rounded-lg border-outline-variant bg-surface text-on-surface focus:ring-primary focus:border-primary text-body-md" placeholder="Describe the incident (e.g. Unauthorized S3 Bucket Access)..." required></textarea>
            </div>
            <div>
                <label class="block text-label-md font-medium text-on-surface mb-xs">Severity Level</label>
                <select name="severity" class="w-full rounded-lg border-outline-variant bg-surface text-on-surface focus:ring-primary focus:border-primary text-body-md" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div>
                <label class="block text-label-md font-medium text-on-surface mb-xs">Estimated Impacted Records</label>
                <input type="number" name="impacted_records" min="0" class="w-full rounded-lg border-outline-variant bg-surface text-on-surface focus:ring-primary focus:border-primary text-body-md" placeholder="e.g. 5000" required>
            </div>
            <div class="flex items-center justify-end gap-sm pt-sm border-t border-outline-variant/20">
                <button type="button" onclick="closeIncidentModal()" class="px-md py-sm rounded-lg border border-outline-variant/50 text-on-surface hover:bg-surface-container-low text-label-md">Cancel</button>
                <button type="submit" class="px-md py-sm rounded-lg bg-error text-on-error hover:opacity-90 text-label-md font-medium">Submit Incident</button>
            </div>
        </form>
    </div>
</div>

<!-- Script to handle Modal & AJAX Form Submission -->
<script>
document.getElementById('incidentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    // Using relative path to match your route structure
    fetch('api/save-incident.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const isJson = response.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await response.json() : null;

        if (!response.ok) {
            const error = (data && data.message) || response.statusText;
            return Promise.reject(error);
        }

        return data;
    })
    .then(data => {
        if (data.status === 'success') {
            closeIncidentModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Failed to save incident: ' + error);
    });
});
</script>