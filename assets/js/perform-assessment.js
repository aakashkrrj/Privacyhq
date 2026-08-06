// assets/js/perform-assessment.js

// Debounce timer for autosave
let autosaveTimeout = null;

// Attach event listeners to autosave fields
document.querySelectorAll('.autosave-input').forEach(input => {
    input.addEventListener('input', triggerAutosave);
    input.addEventListener('change', triggerAutosave);
});

function triggerAutosave() {
    const saveStatus = document.getElementById('saveStatus');
    saveStatus.textContent = 'Saving changes...';
    saveStatus.classList.add('text-primary');
    saveStatus.classList.remove('text-emerald-600');

    clearTimeout(autosaveTimeout);
    autosaveTimeout = setTimeout(performAutosave, 800);
}

function performAutosave() {
    const form = document.getElementById('assessmentForm');
    const formData = new FormData(form);

    fetch('backend/api/assessment/save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const saveStatus = document.getElementById('saveStatus');
        if (data.status === 'success') {
            saveStatus.textContent = 'All changes saved locally';
            saveStatus.classList.remove('text-primary');
            saveStatus.classList.add('text-emerald-600');
        } else {
            saveStatus.textContent = 'Error autosaving';
            saveStatus.classList.remove('text-primary');
            saveStatus.classList.add('text-red-500');
        }
    })
    .catch(err => {
        console.error(err);
        const saveStatus = document.getElementById('saveStatus');
        saveStatus.textContent = 'Connection error';
        saveStatus.classList.remove('text-primary');
        saveStatus.classList.add('text-red-500');
    });
}

function saveDraft() {
    const form = document.getElementById('assessmentForm');
    const formData = new FormData(form);

    fetch('backend/api/assessment/save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Draft saved successfully!');
        } else {
            alert('Failed to save draft: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error while saving draft.');
    });
}

function submitForReview() {
    const form = document.getElementById('assessmentForm');
    const formData = new FormData(form);

    // Save final state
    fetch('backend/api/assessment/save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Call submit
            const submitData = new FormData();
            submitData.append('assessment_id', form.querySelector('[name="assessment_id"]').value);

            return fetch('backend/api/assessment/submit.php', {
                method: 'POST',
                body: submitData
            });
        } else {
            throw new Error(data.message);
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Assessment submitted for review successfully!');
            window.location.href = 'index.php?page=my-assessments';
        } else {
            alert('Submission failed: ' + data.message);
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
        if (data.status === 'success') {
            alert('Evidence file uploaded successfully!');
            location.reload();
        } else {
            alert('Failed to upload file: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error uploading file.');
    });
});
