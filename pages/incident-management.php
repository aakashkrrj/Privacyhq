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