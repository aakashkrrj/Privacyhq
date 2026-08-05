// assets/js/risk-register.js

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function loadDashboard() {
    try {
        const res = await fetch('backend/api/risk-register/dashboard.php');
        const data = await res.json();
        if (data.success && data.data) {
            document.getElementById('kpi-total').innerText = data.data.total_risks;
            document.getElementById('kpi-high').innerText = data.data.high_risks;
            document.getElementById('kpi-mitigated').innerText = data.data.mitigated_risks;
            document.getElementById('kpi-needs-action').innerText = data.data.needs_action;
        }
    } catch (e) {
        console.error('Failed to load dashboard metrics', e);
    }
}

async function loadRecords() {
    try {
        const res = await fetch('backend/api/risk-register/list.php');
        const data = await res.json();
        const tbody = document.getElementById('riskTableBody');
        
        if (data.success) {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #6b7280;">No risks logged in the matrix yet.</td></tr>';
            } else {
                let priorityHigh = 0, priorityMed = 0, priorityLow = 0;
                let categoryCounts = {};
                
                items.forEach(i => {
                    const lh = (i.likelihood || 'medium').toLowerCase();
                    const badgeLh = (lh === 'high') ? 'badge-high' : ((lh === 'medium') ? 'badge-medium' : 'badge-low');
                    
                    const imp = (i.impact || 'medium').toLowerCase();
                    const badgeImp = (imp === 'high') ? 'badge-high' : ((imp === 'medium') ? 'badge-medium' : 'badge-low');
                    
                    const st = (i.status || 'open').toLowerCase();
                    const badgeSt = (st === 'mitigated') ? 'badge-status-mitigated' : ((st === 'in review' || st === 'in_progress') ? 'badge-status-in-review' : 'badge-status-open');

                    const rl = (i.risk_level || 'medium').toLowerCase();
                    if (rl === 'high') priorityHigh++;
                    else if (rl === 'medium') priorityMed++;
                    else priorityLow++;

                    const cat = i.category || 'Uncategorized';
                    if (!categoryCounts[cat]) categoryCounts[cat] = 0;
                    categoryCounts[cat]++;

                    const row = `
                        <tr>
                            <td><strong>${escapeHtml(i.title)}</strong></td>
                            <td>${escapeHtml(i.category)}</td>
                            <td><span class="badge ${badgeLh}">${escapeHtml(i.likelihood)}</span></td>
                            <td><span class="badge ${badgeImp}">${escapeHtml(i.impact)}</span></td>
                            <td>${escapeHtml(i.owner || 'Admin User')}</td>
                            <td><span class="badge ${badgeSt}">${escapeHtml(i.status)}</span></td>
                            <td>${escapeHtml(i.mitigation || 'No strategy defined')}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                // Update distribution card
                document.getElementById('distributionCard').style.display = 'block';
                document.getElementById('dist-high').innerText = priorityHigh;
                document.getElementById('dist-med').innerText = priorityMed;
                document.getElementById('dist-low').innerText = priorityLow;
                document.getElementById('dist-total').innerText = total;

                // Sort categories
                const sortedCategories = Object.keys(categoryCounts).map(k => ({ cat: k, count: categoryCounts[k] }))
                    .sort((a,b) => b.count - a.count).slice(0, 5);
                
                let distHtml = '';
                const colors = ['#dc2626', '#2563eb', '#16a34a', '#d97706', '#7c3aed'];
                const totalForPct = total > 0 ? total : 1;

                sortedCategories.forEach((item, idx) => {
                    const pct = Math.round((item.count / totalForPct) * 100);
                    const color = colors[idx % colors.length];
                    distHtml += `
                        <p style="margin-bottom:8px;">${escapeHtml(item.cat)} (${pct}%)</p>
                        <div style="height:8px;background:#e5e7eb;border-radius:5px;">
                            <div style="width:${pct}%;height:8px;background:${color};border-radius:5px;"></div>
                        </div><br>
                    `;
                });
                document.getElementById('categoryDistribution').innerHTML = distHtml || '<p style="color:#6b7280; font-size:0.9rem;">No data available.</p>';
            }
        }
    } catch (e) {
        console.error('Failed to load records', e);
    }
}

// 2. Log Risk Modal
function openLogRiskModal() {
    document.getElementById('logRiskModal').style.display = 'flex';
}

function closeLogRiskModal() {
    document.getElementById('logRiskModal').style.display = 'none';
    document.getElementById('modalRiskForm').reset();
}

// 3. Review Controls Modal
async function loadControls() {
    try {
        const res = await fetch('backend/api/risk-register/controls.php');
        const data = await res.json();
        const tbody = document.getElementById('controlsTableBody');
        
        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data || [];
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">No controls or mitigations logged.</td></tr>';
            } else {
                items.forEach(c => {
                    const row = `
                        <tr>
                            <td><strong>${escapeHtml(c.risk_title)}</strong></td>
                            <td>${escapeHtml(c.implementation_details)}</td>
                            <td>
                                <select onchange="updateControlStatus(${c.id}, this.value)" style="padding:4px 8px; border-radius:4px; border:1px solid #ccc; font-size:0.85rem;">
                                    <option value="planned" ${c.status === 'planned' ? 'selected' : ''}>Planned</option>
                                    <option value="in_progress" ${c.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="implemented" ${c.status === 'implemented' ? 'selected' : ''}>Implemented</option>
                                </select>
                            </td>
                            <td>
                                <span class="badge ${c.status === 'implemented' ? 'badge-status-mitigated' : (c.status === 'in_progress' ? 'badge-status-in-review' : 'badge-status-open')}">
                                    ${escapeHtml(c.status)}
                                </span>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load controls', e);
    }
}

async function updateControlStatus(mitigationId, newStatus) {
    const fd = new FormData();
    fd.append('csrf_token', G_CSRF_TOKEN);
    fd.append('mitigation_id', mitigationId);
    fd.append('status', newStatus);

    try {
        const res = await fetch('backend/api/risk-register/controls.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            loadControls();
            loadRecords();
            loadDashboard();
        } else {
            alert(data.message || 'Failed to update control status.');
        }
    } catch (e) {
        alert('Network error updating control.');
    }
}

function openReviewControlsModal() {
    loadControls();
    document.getElementById('reviewControlsModal').style.display = 'flex';
}

function closeReviewControlsModal() {
    document.getElementById('reviewControlsModal').style.display = 'none';
}

// Setup Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRecords();

    // Log Risk Modal buttons
    document.getElementById('btn-log-risk-qa').addEventListener('click', openLogRiskModal);
    document.getElementById('closeLogRiskModal').addEventListener('click', closeLogRiskModal);
    document.getElementById('btnCancelLogRiskModal').addEventListener('click', closeLogRiskModal);

    // Modal Risk Form Submit
    document.getElementById('modalRiskForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('csrf_token', G_CSRF_TOKEN);

        try {
            const res = await fetch('backend/api/risk-register/create.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Risk logged successfully!');
                closeLogRiskModal();
                loadRecords();
                loadDashboard();
            } else {
                alert(data.message || 'Failed to log risk.');
            }
        } catch (e) {
            alert('Request failed');
        }
    });

    // Page level form submit
    document.getElementById('riskForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        try {
            const res = await fetch('backend/api/risk-register/create.php', { method: 'POST', body: formData });
            const data = await res.json();
            const alertBox = document.getElementById('alertBox');
            alertBox.style.display = 'block';
            if (data.success) {
                alertBox.innerText = data.message;
                alertBox.style.background = '#d1fae5';
                alertBox.style.color = '#065f46';
                this.reset();
                loadRecords();
                loadDashboard();
            } else {
                alertBox.innerText = data.message;
                alertBox.style.background = '#fee2e2';
                alertBox.style.color = '#991b1b';
            }
        } catch (e) {
            alert('Request failed');
        }
    });

    // Export Register
    document.getElementById('btn-export-register-qa').addEventListener('click', () => {
        window.open('backend/api/risk-register/export.php', '_blank');
    });

    // Generate Report
    document.getElementById('btn-generate-report-qa').addEventListener('click', () => {
        window.open('backend/api/reports/risk-register.php', '_blank');
    });

    // Review Controls Modal buttons
    document.getElementById('btn-review-controls-qa').addEventListener('click', openReviewControlsModal);
    document.getElementById('closeReviewControlsModal').addEventListener('click', closeReviewControlsModal);
    document.getElementById('btnCloseReviewControlsModal').addEventListener('click', closeReviewControlsModal);
});
