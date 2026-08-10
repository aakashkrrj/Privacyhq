<?php
// pages/search.php
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
    <div>
        <h1 class="text-display font-display text-primary leading-tight">Global Unified Search</h1>
        <p class="text-body-md text-on-surface-variant">Federated search across all corporate assessments, policies, DSRs, incidents, tasks, and vendors.</p>
    </div>

    <!-- Search Input -->
    <div class="flex items-center gap-md bg-surface border border-outline-variant p-md rounded-xl shadow-sm">
        <span class="material-symbols-outlined text-primary text-3xl">search</span>
        <input type="text" id="globalSearchInput" oninput="triggerSearch()" placeholder="Type query to search across everything..." class="flex-1 border-0 focus:ring-0 text-body-lg focus:outline-none bg-surface" autofocus>
    </div>

    <!-- Results Box -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex justify-between items-center">
            <h2 class="font-bold text-title-md text-on-surface">Search Results</h2>
            <span id="resultsCount" class="text-caption font-semibold bg-primary/10 text-primary px-2.5 py-0.5 rounded-full">0 found</span>
        </div>
        <div id="searchResultsList" class="divide-y divide-outline-variant">
            <div class="p-lg text-center text-on-surface-variant">Enter a query above to start searching.</div>
        </div>
    </div>
</div>

<script>
let searchTimeout = null;

function triggerSearch() {
    clearTimeout(searchTimeout);
    const q = document.getElementById('globalSearchInput').value.trim();
    if (!q) {
        document.getElementById('searchResultsList').innerHTML = '<div class="p-lg text-center text-on-surface-variant">Enter a query above to start searching.</div>';
        document.getElementById('resultsCount').innerText = '0 found';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`backend/api/search/global.php?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(res => {
                const list = document.getElementById('searchResultsList');
                const badge = document.getElementById('resultsCount');

                if (res.success) {
                    const data = res.data;
                    badge.innerText = `${data.length} found`;

                    if (data.length === 0) {
                        list.innerHTML = '<div class="p-lg text-center text-on-surface-variant">No matching records found.</div>';
                        return;
                    }

                    list.innerHTML = data.map(r => {
                        const moduleColors = {
                            'Assessment': 'bg-blue-50 text-blue-700 border-blue-200',
                            'DSR': 'bg-purple-50 text-purple-700 border-purple-200',
                            'Incident': 'bg-red-50 text-red-700 border-red-200',
                            'Risk': 'bg-amber-50 text-amber-700 border-amber-200',
                            'Policy': 'bg-teal-50 text-teal-700 border-teal-200',
                            'Vendor': 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'Task': 'bg-gray-50 text-gray-700 border-gray-200'
                        };
                        const modColor = moduleColors[r.type] || 'bg-gray-50 text-gray-700 border-gray-200';
                        const link = `index.php?page=${r.page}`;

                        return `
                            <div class="p-md hover:bg-surface-container-low transition-colors flex items-center justify-between">
                                <div class="space-y-[2px]">
                                    <div class="flex items-center gap-sm">
                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded border ${modColor}">
                                            ${r.type}
                                        </span>
                                    </div>
                                    <h4 class="font-semibold text-body-md text-on-surface">${escapeHtml(r.name)}</h4>
                                </div>
                                <a href="${link}" class="inline-flex items-center gap-xs text-body-sm font-semibold text-primary hover:underline">
                                    <span>View Record</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = `<div class="p-lg text-center text-red-600">${escapeHtml(res.message)}</div>`;
                }
            })
            .catch(err => console.error(err));
    }, 300);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
</script>
