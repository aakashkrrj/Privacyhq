<?php
// Make sure this doesn't conflict if a user hits it directly
if (session_status() === PHP_SESSION_NONE) {
    session_name('privacyhq_portal');
    session_start();
}
// If already authenticated, redirect to dashboard
if (!empty($_SESSION['portal_subject_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>PrivacyHQ - Privacy Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-2">Privacy Portal</h1>
        <p class="text-gray-600 mb-6">Enter your email address to receive a secure access link.</p>
        
        <div id="message" class="hidden mb-4 p-3 rounded text-sm"></div>

        <form id="loginForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="email">Email Address</label>
                <input type="email" id="email" name="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Send Magic Link
            </button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const messageEl = document.getElementById('message');
            
            try {
                const res = await fetch('../backend/api/portal/auth/request-link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const data = await res.json();
                
                messageEl.classList.remove('hidden', 'bg-red-100', 'text-red-700');
                messageEl.classList.add('bg-blue-100', 'text-blue-700');
                messageEl.innerText = data.message;
            } catch (err) {
                messageEl.classList.remove('hidden', 'bg-blue-100', 'text-blue-700');
                messageEl.classList.add('bg-red-100', 'text-red-700');
                messageEl.innerText = "An error occurred. Please try again.";
            }
        });
    </script>
</body>
</html>
