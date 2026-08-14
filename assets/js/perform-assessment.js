// assets/js/perform-assessment.js
// Perform DPIA Questionnaire Interactions

let autosaveTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.autosave-input').forEach(input => {
        input.addEventListener('input', triggerAutosave);
        input.addEventListener('change', triggerAutosave);
    });
});

function triggerAutosave() {
    const saveStatus = document.getElementById('saveStatus');
    if (saveStatus) {
        saveStatus.textContent = 'Saving changes...';
        saveStatus.classList.add('text-primary');
        saveStatus.classList.remove('text-emerald-600');
    }

    clearTimeout(autosaveTimeout);
    autosaveTimeout = setTimeout(performAutosave, 800);
}

function performAutosave() {
    const form = document.getElementById('assessmentForm');
    if (!form) return;
    const formData = new FormData(form);

    fetch('backend/api/assessment/save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const saveStatus = document.getElementById('saveStatus');
        if (saveStatus) {
            if (data.status === 'success' || data.success) {
                saveStatus.textContent = 'All changes saved locally';
                saveStatus.classList.remove('text-primary', 'text-red-500');
                saveStatus.classList.add('text-emerald-600');
            } else {
                saveStatus.textContent = 'Error autosaving';
                saveStatus.classList.remove('text-primary', 'text-emerald-600');
                saveStatus.classList.add('text-red-500');
            }
        }
    })
    .catch(err => {
        console.error(err);
        const saveStatus = document.getElementById('saveStatus');
        if (saveStatus) {
            saveStatus.textContent = 'Connection error';
            saveStatus.classList.remove('text-primary', 'text-emerald-600');
            saveStatus.classList.add('text-red-500');
        }
    });
}

function saveDraft() {
    const form = document.getElementById('assessmentForm');
    if (!form) return;
    const formData = new FormData(form);

    fetch('backend/api/assessment/save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            alert('Draft saved successfully!');
        } else {
            alert('Failed to save draft: ' + (data.message || 'Error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error while saving draft.');
    });
}

function submitForReview() {
    const form = document.getElementById('assessmentForm');
    if (!form) return;

    // Validate Mandatory Questions
    let missingQuestions = 0;
    const questionBlocks = form.querySelectorAll('[data-question-id]');
    questionBlocks.forEach(block => {
        const requiredAsterisk = block.querySelector('.text-red-500');
        if (requiredAsterisk) {
            const inputs = block.querySelectorAll('input, select, textarea');
            let answered = false;
            inputs.forEach(inp => {
                if ((inp.type === 'radio' || inp.type === 'checkbox') && inp.checked) answered = true;
                else if (inp.type !== 'radio' && inp.type !== 'checkbox' && inp.value.trim() !== '') answered = true;
            });
            if (!answered) {
                missingQuestions++;
                block.classList.add('p-2', 'bg-red-50', 'rounded-lg', 'border', 'border-red-200');
            } else {
                block.classList.remove('p-2', 'bg-red-50', 'rounded-lg', 'border', 'border-red-200');
            }
        }
    });

    if (missingQuestions > 0) {
        alert(`Please complete all mandatory questions (${missingQuestions} required questions remaining) before submitting for review.`);
        return;
    }

    const formData = new FormData(form);

    // Save final state then submit
    fetch('backend/api/assessment/save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            const submitData = new FormData();
            submitData.append('assessment_id', form.querySelector('[name="assessment_id"]').value);

            return fetch('backend/api/assessment/submit.php', {
                method: 'POST',
                body: submitData
            });
        } else {
            throw new Error(data.message || 'Failed to save responses before submission');
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            alert('Assessment submitted for review successfully!');
            window.location.href = 'index.php?page=my-assessments';
        } else {
            alert('Submission failed: ' + (data.message || 'Action failed'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error during submission: ' + err.message);
    });
}

// Upload Evidence Form submission
document.getElementById('evidenceForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('backend/api/assessment/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            alert('Evidence file uploaded successfully!');
            location.reload();
        } else {
            alert('Failed to upload file: ' + (data.message || 'Upload error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error uploading file.');
    });
});

window.saveDraft = saveDraft;
window.submitForReview = submitForReview;
