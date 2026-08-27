document.getElementById("changePasswordForm").addEventListener("submit", function (e) {

    e.preventDefault();

    const form = new FormData(this);

    const currentPassword = form.get("current_password").trim();
    const newPassword = form.get("new_password").trim();
    const confirmPassword = form.get("confirm_password").trim();

    const messageBox = document.getElementById("message");

    function showMessage(message, success = false) {

        messageBox.innerHTML = message;

        messageBox.className =
            success
                ? "mb-4 p-3 rounded bg-green-100 text-green-700"
                : "mb-4 p-3 rounded bg-red-100 text-red-700";
    }

    if (currentPassword === "") {
        showMessage("Current Password is required.");
        return;
    }

    if (newPassword === "") {
        showMessage("New Password is required.");
        return;
    }

    if (confirmPassword === "") {
        showMessage("Confirm Password is required.");
        return;
    }

    if (newPassword.length < 8) {
        showMessage("Password must be at least 8 characters.");
        return;
    }

    if (newPassword !== confirmPassword) {
        showMessage("Passwords do not match.");
        return;
    }

    const button = this.querySelector("button");

    button.disabled = true;
    button.innerHTML = "Changing...";

    fetch("../backend/api/settings/change-password.php", {

        method: "POST",
        body: form,
        credentials: "same-origin"

    })

        .then(response => response.json())

        .then(result => {

            if (result.success) {

                showMessage(result.message, true);

                document.getElementById("changePasswordForm").reset();

                setTimeout(() => {
                    window.location.href = "index.php?page=settings";
                }, 2000);

            } else {

                showMessage(result.message);

            }

        })

        .catch(error => {

            console.error(error);

            showMessage("Something went wrong.");

        })

        .finally(() => {

            button.disabled = false;

            button.innerHTML = "Change Password";

        });

});