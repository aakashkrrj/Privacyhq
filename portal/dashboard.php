<?php
require_once __DIR__ . '/../backend/api/portal/PortalBootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>PrivacyHQ - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>";
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">PrivacyHQ Portal</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600"><?= htmlspecialchars($_SESSION['portal_email'] ?? '') ?></span>
                    <button id="logoutBtn" class="text-sm text-red-600 hover:text-red-800">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="border-4 border-dashed border-gray-200 rounded-lg h-96 p-8">
                <h2 class="text-2xl font-semibold mb-4">Welcome to your Privacy Portal</h2>
                <p class="text-gray-600 mb-8">This is a secure area authenticated via magic link.</p>
                
                <!-- OTP Test Section -->
                <div class="bg-white shadow sm:rounded-lg mb-6 max-w-md p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Step-Up Verification Test</h3>
                    <div class="mt-2 text-sm text-gray-500">
                        <p>Request an OTP to test step-up verification.</p>
                    </div>
                    
                    <div id="otpMsg" class="hidden mt-4 p-2 text-sm rounded"></div>
                    
                    <div class="mt-5 space-y-4">
                        <button id="reqOtpBtn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Request OTP
                        </button>
                        
                        <div id="otpFormGroup" class="hidden flex space-x-2">
                            <input type="text" id="otpCode" placeholder="123456" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <button id="verifyOtpBtn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Verify
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            let fd = new FormData();
            fd.append('csrf_token', csrfToken);
            await fetch('../backend/api/portal/auth/logout.php', {
                method: 'POST',
                body: fd
            });
            window.location.href = 'index.php';
        });

        document.getElementById('reqOtpBtn').addEventListener('click', async () => {
            const msgEl = document.getElementById('otpMsg');
            try {
                let fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('action_context', 'erasure_test');
                const res = await fetch('../backend/api/portal/auth/request-otp.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                
                msgEl.classList.remove('hidden', 'bg-red-100', 'text-red-700');
                msgEl.classList.add('bg-blue-100', 'text-blue-700');
                msgEl.innerText = data.message;
                
                if (data.status === 'success') {
                    document.getElementById('otpFormGroup').classList.remove('hidden');
                }
            } catch (err) {
                console.error(err);
            }
        });

        document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
            const otp = document.getElementById('otpCode').value;
            const msgEl = document.getElementById('otpMsg');
            
            try {
                let fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('otp', otp);
                fd.append('action_context', 'erasure_test');
                const res = await fetch('../backend/api/portal/auth/verify-otp.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                
                msgEl.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-blue-100', 'text-blue-700');
                if (data.status === 'success') {
                    msgEl.classList.add('bg-green-100', 'text-green-700');
                } else {
                    msgEl.classList.add('bg-red-100', 'text-red-700');
                }
                msgEl.innerText = data.message;
            } catch (err) {
                console.error(err);
            }
        });
    </script>
</body>
</html>
