// assets/js/assessments.js

function openAssessmentModal() {
    document.getElementById('title').value = '';
    document.getElementById('assigned_to').selectedIndex = 0;
    document.getElementById('reviewer_id').selectedIndex = 0;
    document.getElementById('priority').value = 'Medium';
    document.getElementById('due_date').value = '';
    document.getElementById('assessmentModal').classList.remove('hidden');
}

function closeAssessmentModal() {
    document.getElementById('assessmentModal').classList.add('hidden');
}

document.getElementById('assessmentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('backend/api/assessment/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeAssessmentModal();
            location.reload();
        } else {
            alert('Error creating assessment: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to save assessment. System error.');
    });
});
