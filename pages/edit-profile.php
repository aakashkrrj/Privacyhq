<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="en" class="light">

<head>

    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Profile | PrivacyHQ</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
          rel="stylesheet">

    <style>

        body{
            font-family:'Inter',sans-serif;
        }

        .material-symbols-outlined{
            font-variation-settings:
            'FILL'0,
            'wght'400,
            'GRAD'0,
            'opsz'24;
        }

    </style>

</head>

<body class="bg-gray-100">

<div class="min-h-screen">

    <!-- HEADER -->

    <header class="bg-white shadow">

        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">

            <div class="flex items-center gap-4">

                <a href="index.php?page=settings"
                   class="text-blue-600 hover:text-blue-800">

                    <span class="material-symbols-outlined">
                        arrow_back
                    </span>

                </a>

                <div>

                    <h1 class="text-2xl font-bold">

                        Edit Profile

                    </h1>

                    <p class="text-gray-500 text-sm">

                        Update your account information

                    </p>

                </div>

            </div>

        </div>

    </header>

    <!-- MAIN -->

    <main class="max-w-4xl mx-auto mt-8 px-6">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT CARD -->

            <div
                class="bg-white rounded-xl shadow p-6 text-center">

                <img

                    id="profilePreview"

                    src="../assets/images/default-avatar.png"

                    class="w-36 h-36 rounded-full mx-auto object-cover border-4 border-blue-500">

                <h2
                    id="displayName"
                    class="text-xl font-semibold mt-5">

                    Loading...

                </h2>

                <p
                    id="displayRole"
                    class="text-gray-500">

                    User

                </p>

                <div class="mt-6">

                    <label
                        for="profileImage"
                        class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">

                        <span class="material-symbols-outlined mr-2">

                            upload

                        </span>

                        Upload Image

                    </label>

                </div>

            </div>

            <!-- RIGHT CARD -->

            <div
                class="lg:col-span-2 bg-white rounded-xl shadow p-8">

                <form id="profileForm">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrfToken) ?>">
    <input
        type="file"
        id="profileImage"
        name="profile_image"
        class="hidden"
        accept="image/*">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- FIRST NAME -->
        <div>
            <label class="block mb-2 font-medium">
                First Name
            </label>

            <input
                type="text"
                id="first_name"
                name="first_name"
                class="w-full rounded-lg border border-gray-300 px-4 py-3">
        </div>

        <!-- LAST NAME -->
        <div>
            <label class="block mb-2 font-medium">
                Last Name
            </label>

            <input
                type="text"
                id="last_name"
                name="last_name"
                class="w-full rounded-lg border border-gray-300 px-4 py-3">
        </div>

        <!-- EMAIL -->
        <div>
            <label class="block mb-2 font-medium">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                class="w-full rounded-lg border border-gray-300 px-4 py-3">
        </div>

        <!-- PHONE -->
        <div>
            <label class="block mb-2 font-medium">
                Phone
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                maxlength="10"
                inputmode="numeric"
                autocomplete="off"
                class="w-full rounded-lg border border-gray-300 px-4 py-3">
        </div>

    </div>

                    <!-- STATUS -->

                    <div class="mt-5">

                        <label
                            class="block mb-2 font-medium">

                            Account Status

                        </label>

                        <input
    type="text"
    id="status"
    name="status"
    readonly
    class="w-full rounded-lg border bg-gray-100 px-4 py-3">

                    </div>

                    <!-- MESSAGE -->

                    <div
                        id="messageBox"
                        class="hidden mt-6 rounded-lg px-4 py-3">

                    </div>

                    <!-- BUTTON -->

                    <div class="mt-8 flex justify-end">

                        <button

                            id="saveBtn"

                            type="submit"

                            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold">

                            Save Changes

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>
<script>
const phoneInput = document.getElementById("phone");

phoneInput.addEventListener("input", function () {

    this.value = this.value.replace(/\D/g, "");

    if (this.value.length > 10)
    {
        this.value = this.value.substring(0, 10);
    }

});
document.addEventListener("DOMContentLoaded", function () {

    loadProfile();

    document.getElementById("profileImage").addEventListener("change", function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                document.getElementById("profilePreview").src = evt.target.result;
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

});

/**
 * Load Logged-in User Profile
 */
function loadProfile()
{

    fetch("../backend/api/settings/profile.php", {

        method: "GET",
        credentials: "same-origin"

    })

    .then(response => response.json())

    .then(result => {

        console.log(result);

        if (!result.success)
        {
            showMessage(
                result.message,
                false
            );

            return;
        }

        const user = result.data;
        console.log("PROFILE DATA:", user);

        document.getElementById("first_name").value =
            user.first_name ?? "";

        document.getElementById("last_name").value =
            user.last_name ?? "";

        document.getElementById("email").value =
            user.email ?? "";

        document.getElementById("phone").value =
            user.phone ?? "";

        document.getElementById("status").value =
            user.status ?? "";

        document.getElementById("displayName").innerHTML =
            (user.first_name ?? "") +
            " " +
            (user.last_name ?? "");

        if(user.role_name)
        {
            document.getElementById("displayRole").innerHTML =
                user.role_name;
        }

        if(user.profile_image)
        {
            document.getElementById("profilePreview").src =
                user.profile_image;
        }

    })

    .catch(error => {

        console.error(error);

        showMessage(
            "Unable to load profile.",
            false
        );

    });

}


/**
 * Success/Error Alert
 */

function showMessage(message, success)
{

    const box = document.getElementById("messageBox");

    box.classList.remove("hidden");

    if(success)
    {
        box.className =
            "mt-6 rounded-lg px-4 py-3 bg-green-100 text-green-700";

    }
    else
    {
        box.className =
            "mt-6 rounded-lg px-4 py-3 bg-red-100 text-red-700";
    }

    box.innerHTML = message;

    setTimeout(function(){

        box.classList.add("hidden");

    },4000);

}
console.log("SCRIPT LOADED");
document.getElementById("profileForm").addEventListener("submit", function (e) {

    console.log("STEP 1");

    e.preventDefault();

    const firstName = document.getElementById("first_name").value.trim();
const lastName = document.getElementById("last_name").value.trim();
const email = document.getElementById("email").value.trim();
const phone = document.getElementById("phone").value.trim();

// First Name
if(firstName === "")
{
    showMessage("First Name is required.", false);
    return;
}

// Last Name
if(lastName === "")
{
    showMessage("Last Name is required.", false);
    return;
}

// Email
if(email === "")
{
    showMessage("Email is required.", false);
    return;
}

// Email Format
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

if(!emailPattern.test(email))
{
    showMessage("Please enter a valid email address.", false);
    return;
}

// Phone Validation (optional field)
if(phone !== "" && phone.length !== 10)
{
    showMessage("Phone number must contain exactly 10 digits.", false);
    return;
}
    console.log("STEP 1");

const form = new FormData(this);

console.log("STEP 2");

for (const pair of form.entries()) {
    console.log(pair[0], pair[1]);
}

const saveBtn = document.getElementById("saveBtn");

saveBtn.disabled = true;
saveBtn.innerHTML = "Saving...";

console.log("STEP 3");   // <-- just before fetch

fetch("../backend/api/settings/update-profile.php", {
    method: "POST",
    body: form,
    credentials: "same-origin"
})

    .then(response => {

    console.log("STEP 4", response);

    return response.json();

})

    .then(result => {

        if(result.success)
        {
            showMessage(result.message, true);

            loadProfile();
        }
        else
        {
            showMessage(result.message, false);
        }

    })

    .catch(error => {

        console.error(error);

        showMessage("Something went wrong.", false);

    })

    .finally(() => {

        saveBtn.disabled = false;
        saveBtn.innerHTML = "Save Changes";

    });

});

</script>