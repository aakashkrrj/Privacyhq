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

    fetch("../backend/api/settings/notification-preferences.php")

        .then(res => res.json())

        .then(result => {

            if (!result.success) return;

            const data = result.data;

            document.getElementById(
                "email_notifications"
            ).checked = data.email_notifications == 1;

            document.getElementById(
                "in_app_notifications"
            ).checked = data.in_app_notifications == 1;

            document.getElementById(
                "privacy_incident_alerts"
            ).checked = data.privacy_incident_alerts == 1;

            document.getElementById(
                "consent_updates"
            ).checked = data.consent_updates == 1;

            document.getElementById(
                "assessment_reminders"
            ).checked = data.assessment_reminders == 1;

            document.getElementById(
                "risk_alerts"
            ).checked = data.risk_alerts == 1;

            document.getElementById(
                "system_announcements"
            ).checked = data.system_announcements == 1;

        });

    form.addEventListener("submit", function (e) {

        e.preventDefault();

        const button = form.querySelector("button");

        button.disabled = true;

        button.innerHTML = "Saving...";

        fetch(
            "../backend/api/settings/update-notification-preferences.php",
            {
                method: "POST",
                body: new FormData(form),
                credentials: "same-origin"
            }
        )

            .then(res => res.json())

            .then(result => {

                if (result.success) {

                    showMessage(result.message, true);

                } else {

                    showMessage(result.message);

                }

            })

            .catch(() => {

                showMessage("Something went wrong.");

            })

            .finally(() => {

                button.disabled = false;

                button.innerHTML = "Save Preferences";

            });

    });

});