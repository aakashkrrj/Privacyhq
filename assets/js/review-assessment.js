// assets/js/review-assessment.js

function approveDpia(assessmentId) {
    const notes = document.getElementById('review_notes').value;

    const formData = new FormData();
    formData.append('assessment_id', assessmentId);
    formData.append('notes', notes);

    fetch('backend/api/assessment/approve.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Assessment approved and findings synchronized with the Risk Register successfully!');
            window.location.href = 'index.php?page=assessments';
        } else {
            alert('Failed to approve: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error during approval.');
    });
}

function rejectDpia(assessmentId) {
    const notes = document.getElementById('review_notes').value;

    if (!notes.trim()) {
        alert('Please enter comments/notes specifying why the changes are requested.');
        return;
    }

    const formData = new FormData();
    formData.append('assessment_id', assessmentId);
    formData.append('notes', notes);

    fetch('backend/api/assessment/reject.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Changes requested. Assessment status set to Rejected.');
            window.location.href = 'index.php?page=assessments';
        } else {
            alert('Failed to reject: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error during rejection.');
    });
}
