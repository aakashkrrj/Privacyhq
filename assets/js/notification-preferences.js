document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById(
        "notificationPreferencesForm"
    );

    const message = document.getElementById(
        "message"
    );

    function showMessage(text, success = false) {

        message.innerHTML = text;

        message.className =
            success
                ? "mt-5 p-3 rounded bg-green-100 text-green-700"
                : "mt-5 p-3 rounded bg-red-100 text-red-700";

    }

    fetch("backend/api/settings/notification-preferences.php")
        .then(res => {
            if (!res.ok) {
                throw new Error("HTTP error " + res.status);
            }
            return res.json();
        })
        .then(result => {
            if (!result.success || !result.data) return;
            const data = result.data;
            const fields = [
                "email_notifications",
                "in_app_notifications",
                "privacy_incident_alerts",
                "consent_updates",
                "assessment_reminders",
                "risk_alerts",
                "system_announcements"
            ];
            fields.forEach(field => {
                const el = document.getElementById(field);
                if (el) {
                    el.checked = (data[field] == 1 || data[field] === true);
                }
            });
        })
        .catch(err => {
            console.error("Error loading notification preferences:", err);
        });

    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const button = form.querySelector("button[type='submit']");
            if (button) {
                button.disabled = true;
                button.innerHTML = "Saving...";
            }

            fetch(
                "backend/api/settings/update-notification-preferences.php",
                {
                    method: "POST",
                    body: new FormData(form),
                    credentials: "same-origin"
                }
            )
                .then(res => {
                    if (!res.ok) {
                        return res.json().then(errData => {
                            throw new Error(errData.message || ("HTTP Error " + res.status));
                        });
                    }
                    return res.json();
                })
                .then(result => {
                    if (result.success) {
                        showMessage(result.message || "Preferences updated successfully.", true);
                    } else {
                        showMessage(result.message || "Failed to update preferences.");
                    }
                })
                .catch(err => {
                    console.error("Error saving notification preferences:", err);
                    showMessage(err.message || "Something went wrong.");
                })
                .finally(() => {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = "Save Preferences";
                    }
                });
        });
    }

});