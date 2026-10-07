async function loadDashboardMetrics() {
    try {
        const res = await fetch('/governance/backend/api/grc/dashboard.php');
        const data = await res.json();
        if (data.status === 'success') {
            const metrics = data.data;
            document.querySelectorAll('.text-display, .text-headline-lg').forEach(el => {
                if (el.closest('.grid')) {
                    if (el.innerText.includes('94%')) el.innerText = '94%';
                    else if (el.innerText.includes('14')) el.innerText = metrics.total_risks;
                    else if (el.innerText.includes('2') && !el.innerText.includes('92')) el.innerText = metrics.high_risks;
                }
            });
        }
    } catch (e) {
        console.error('Failed to load dashboard metrics');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardMetrics();
});
