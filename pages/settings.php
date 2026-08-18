<?php
// pages/settings.php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header('Location: login.php');
    exit;
}

$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="space-y-lg max-w-7xl mx-auto">
    <!-- Page Header -->
    <div>
        <div class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary text-[32px]">settings</span>
            <h1 class="text-display font-display text-primary leading-tight">Platform Settings &amp; Security Portal</h1>
        </div>
        <p class="text-body-md text-on-surface-variant mt-xs">Manage personal profile details, authentication credentials, 2FA security, notification preferences, developer API keys, team permissions, and compliance evidence documents.</p>
    </div>

    <!-- 8-Tab Settings Navigation Card -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="border-b border-outline-variant bg-surface-container-low px-md flex flex-wrap gap-xs overflow-x-auto">
            <button onclick="switchSettingsTab('profile')" id="tabBtn-profile" class="px-md py-3.5 font-title-md border-b-2 border-primary text-primary transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">badge</span> Profile Info
            </button>
            <button onclick="switchSettingsTab('edit-profile')" id="tabBtn-edit-profile" class="px-md py-3.5 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">edit_square</span> Edit Profile
            </button>
            <button onclick="switchSettingsTab('password')" id="tabBtn-password" class="px-md py-3.5 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">lock_reset</span> Password
            </button>
            <button onclick="switchSettingsTab('2fa')" id="tabBtn-2fa" class="px-md py-3.5 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">security</span> 2FA Security
            </button>
            <button onclick="switchSettingsTab('notifications')" id="tabBtn-notifications" class="px-md py-3.5 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">notifications</span> Notifications
            </button>
            <button onclick="switchSettingsTab('api-keys')" id="tabBtn-api-keys" class="px-md py-3.5 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">key</span> API Keys
            </button>
            <button onclick="switchSettingsTab('permissions')" id="tabBtn-permissions" class="px-md py-3.5 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span> Team Permissions
            </button>
            <button onclick="switchSettingsTab('documents')" id="tabBtn-documents" class="px-md py-3.5 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">folder_zip</span> Compliance Vault
            </button>
        </div>

        <!-- Tab 1: Profile Information (Row 141) -->
        <div id="tabContent-profile" class="p-lg space-y-md">
            <div class="flex items-center gap-md p-md bg-surface-container-low rounded-xl border border-outline-variant">
                <div class="w-20 h-20 rounded-full bg-primary/10 border-2 border-primary flex items-center justify-center overflow-hidden" id="prof-avatar-box">
                    <span class="material-symbols-outlined text-primary text-[40px]">person</span>
                </div>
                <div>
                    <h2 class="text-display font-bold text-on-surface" id="prof-name">Loading...</h2>
                    <p class="text-body-md text-on-surface-variant" id="prof-email">...</p>
                    <span class="inline-flex px-3 py-0.5 rounded-full text-caption font-semibold border bg-blue-50 text-blue-700 border-blue-200 mt-2" id="prof-role">
                        Loading Role
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-md">
                <div class="p-md bg-surface-container-low rounded-lg border">
                    <span class="text-caption text-on-surface-variant uppercase font-semibold block">User ID</span>
                    <strong class="font-mono text-primary text-title-md block" id="prof-id">#...</strong>
                </div>
                <div class="p-md bg-surface-container-low rounded-lg border">
                    <span class="text-caption text-on-surface-variant uppercase font-semibold block">Phone Number</span>
                    <strong class="font-mono text-on-surface text-body-md block" id="prof-phone">N/A</strong>
                </div>
                <div class="p-md bg-surface-container-low rounded-lg border">
                    <span class="text-caption text-on-surface-variant uppercase font-semibold block">Account Status</span>
                    <strong class="capitalize text-emerald-700 text-body-md block" id="prof-status">Active</strong>
                </div>
                <div class="p-md bg-surface-container-low rounded-lg border">
                    <span class="text-caption text-on-surface-variant uppercase font-semibold block">Account Created</span>
                    <strong class="font-mono text-on-surface text-caption block" id="prof-created">...</strong>
                </div>
                <div class="p-md bg-surface-container-low rounded-lg border">
                    <span class="text-caption text-on-surface-variant uppercase font-semibold block">Last Session Login</span>
                    <strong class="font-mono text-on-surface text-caption block" id="prof-login">...</strong>
                </div>
                <div class="p-md bg-surface-container-low rounded-lg border">
                    <span class="text-caption text-on-surface-variant uppercase font-semibold block">2FA Status</span>
                    <strong class="text-primary text-body-md block" id="prof-2fa-status">Disabled</strong>
                </div>
            </div>
        </div>

        <!-- Tab 2: Edit Profile (Row 142) -->
        <div id="tabContent-edit-profile" class="hidden p-lg max-w-2xl space-y-md">
            <h3 class="font-bold text-on-surface text-title-md">Update Personal Information</h3>
            <form id="editProfileForm" class="space-y-md">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="grid grid-cols-2 gap-md">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">First Name *</label>
                        <input type="text" name="first_name" id="edit_prof_first_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Last Name *</label>
                        <input type="text" name="last_name" id="edit_prof_last_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Phone Number</label>
                    <input type="text" name="phone" id="edit_prof_phone" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="+1 555-0192">
                </div>

                <div class="pt-2 border-t border-outline-variant flex justify-end">
                    <button type="submit" class="px-5 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Save Profile Details</button>
                </div>
            </form>

            <hr class="my-md border-outline-variant">

            <h3 class="font-bold text-on-surface text-title-md">Update Profile Avatar</h3>
            <form id="avatarForm" class="space-y-md">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Select Avatar Image (JPG, PNG, WEBP, Max 5MB)</label>
                    <input type="file" name="profile_image" accept="image/*" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Upload Avatar</button>
                </div>
            </form>
        </div>

        <!-- Tab 3: Change Password (Row 143) -->
        <div id="tabContent-password" class="hidden p-lg max-w-xl space-y-md">
            <h3 class="font-bold text-on-surface text-title-md">Change Account Password</h3>
            <form id="changePasswordForm" class="space-y-md">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Current Password *</label>
                    <input type="password" name="current_password" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Enter current password">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">New Password * (Min 8 Chars)</label>
                    <input type="password" name="new_password" minlength="8" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Enter new password">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Confirm New Password *</label>
                    <input type="password" name="confirm_password" minlength="8" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Re-enter new password">
                </div>

                <div class="pt-2 border-t border-outline-variant flex justify-end">
                    <button type="submit" class="px-5 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Update Password</button>
                </div>
            </form>
        </div>

        <!-- Tab 4: Two-Factor Authentication (Row 144) -->
        <div id="tabContent-2fa" class="hidden p-lg space-y-md">
            <div class="flex items-center justify-between p-md bg-surface-container-low rounded-xl border border-outline-variant">
                <div>
                    <h3 class="font-bold text-on-surface text-title-md">Two-Factor Authentication (2FA) Security</h3>
                    <p class="text-caption text-on-surface-variant mt-0.5">Protect your account using TOTP authenticator apps (Google Authenticator, Authy, 1Password).</p>
                </div>
                <span class="px-3 py-1 text-caption font-bold rounded-full border bg-amber-50 text-amber-700 border-amber-200" id="2fa-badge">
                    Status: Disabled
                </span>
            </div>

            <div id="2fa-setup-box" class="space-y-md border border-outline-variant p-md rounded-xl bg-surface">
                <h4 class="font-bold text-on-surface">Enable 2FA Protection</h4>
                <p class="text-body-md text-on-surface-variant">Step 1: Click setup below to generate your secret seed. Scan the secret using your authenticator app.</p>
                
                <button onclick="start2faSetup()" class="px-4 py-2.5 bg-primary text-white text-body-md font-semibold rounded-xl hover:opacity-90 transition cursor-pointer">
                    Start 2FA Setup
                </button>

                <div id="2fa-qr-container" class="hidden p-md bg-surface-container-low rounded-lg border space-y-md max-w-lg">
                    <div>
                        <span class="text-caption font-semibold uppercase text-on-surface-variant block mb-1">Secret Key (Manual Entry)</span>
                        <code class="font-mono text-primary font-bold text-title-md bg-surface p-2 rounded border block text-center select-all" id="2fa-secret-text">...</code>
                    </div>

                    <form id="enable2faForm" class="space-y-md">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="secret" id="2fa_secret_input" value="">

                        <div>
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Step 2: Enter 6-Digit Authenticator Code *</label>
                            <input type="text" name="otp_code" required maxlength="6" class="w-full font-mono text-center text-title-lg tracking-widest border border-outline-variant rounded-lg p-2.5 focus:border-primary focus:outline-none" placeholder="123456">
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-emerald-600 text-white text-body-md font-semibold rounded-xl hover:bg-emerald-700 transition cursor-pointer">
                            Verify &amp; Activate 2FA
                        </button>
                    </form>
                </div>
            </div>

            <div id="2fa-disable-box" class="hidden space-y-md border border-red-200 p-md rounded-xl bg-red-50">
                <h4 class="font-bold text-red-800">2FA is Currently Active</h4>
                <p class="text-body-md text-red-700">To disable Two-Factor Authentication, confirm your current login password.</p>
                <form id="disable2faForm" class="flex gap-md items-center max-w-lg">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="password" name="current_password" required placeholder="Enter Current Password" class="border border-outline-variant rounded-lg p-2.5 text-body-md bg-surface flex-1">
                    <button type="submit" class="px-4 py-2.5 bg-red-600 text-white text-body-md font-semibold rounded-xl hover:bg-red-700 transition cursor-pointer whitespace-nowrap">
                        Disable 2FA
                    </button>
                </form>
            </div>
        </div>

        <!-- Tab 5: Notification Preferences (Row 145) -->
        <div id="tabContent-notifications" class="hidden p-lg space-y-md">
            <h3 class="font-bold text-on-surface text-title-md">Configure Notification Alerts</h3>
            <form id="notificationForm" class="space-y-md max-w-2xl">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="space-y-3">
                    <label class="flex items-center justify-between p-md bg-surface-container-low rounded-lg border border-outline-variant cursor-pointer hover:bg-surface-container-high transition">
                        <div>
                            <span class="font-semibold text-on-surface block">Email Notification Alerts</span>
                            <span class="text-caption text-on-surface-variant">Receive compliance activity summaries via email</span>
                        </div>
                        <input type="checkbox" name="email_notifications" id="notif_email" value="1" class="w-5 h-5 text-primary rounded border-outline-variant">
                    </label>

                    <label class="flex items-center justify-between p-md bg-surface-container-low rounded-lg border border-outline-variant cursor-pointer hover:bg-surface-container-high transition">
                        <div>
                            <span class="font-semibold text-on-surface block">In-App Banner Notifications</span>
                            <span class="text-caption text-on-surface-variant">Show realtime notifications inside PrivacyHQ dashboard</span>
                        </div>
                        <input type="checkbox" name="in_app_notifications" id="notif_in_app" value="1" class="w-5 h-5 text-primary rounded border-outline-variant">
                    </label>

                    <label class="flex items-center justify-between p-md bg-surface-container-low rounded-lg border border-outline-variant cursor-pointer hover:bg-surface-container-high transition">
                        <div>
                            <span class="font-semibold text-red-700 block">Privacy Incident &amp; Breach Alerts</span>
                            <span class="text-caption text-on-surface-variant">High-priority instant alerts for security incidents</span>
                        </div>
                        <input type="checkbox" name="privacy_incident_alerts" id="notif_incident" value="1" class="w-5 h-5 text-red-600 rounded border-outline-variant">
                    </label>

                    <label class="flex items-center justify-between p-md bg-surface-container-low rounded-lg border border-outline-variant cursor-pointer hover:bg-surface-container-high transition">
                        <div>
                            <span class="font-semibold text-on-surface block">Consent &amp; DSR Request Updates</span>
                            <span class="text-caption text-on-surface-variant">Notifications when data subject requests are submitted</span>
                        </div>
                        <input type="checkbox" name="consent_updates" id="notif_consent" value="1" class="w-5 h-5 text-primary rounded border-outline-variant">
                    </label>

                    <label class="flex items-center justify-between p-md bg-surface-container-low rounded-lg border border-outline-variant cursor-pointer hover:bg-surface-container-high transition">
                        <div>
                            <span class="font-semibold text-on-surface block">Assessment &amp; Audit Reminders</span>
                            <span class="text-caption text-on-surface-variant">Automated reminders for due PIA/DPIA audits</span>
                        </div>
                        <input type="checkbox" name="assessment_reminders" id="notif_assessment" value="1" class="w-5 h-5 text-primary rounded border-outline-variant">
                    </label>
                </div>

                <div class="pt-2 border-t border-outline-variant flex justify-end">
                    <button type="submit" class="px-5 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Save Notification Preferences</button>
                </div>
            </form>
        </div>

        <!-- Tab 6: API Keys (Row 146) -->
        <div id="tabContent-api-keys" class="hidden p-lg space-y-md">
            <div class="flex justify-between items-center bg-surface-container-low p-md rounded-xl border border-outline-variant">
                <div>
                    <h3 class="font-bold text-on-surface text-title-md">Developer API Keys</h3>
                    <p class="text-caption text-on-surface-variant">Generate secure API tokens for integrating PrivacyHQ with external systems.</p>
                </div>
                <button onclick="openCreateApiKeyModal()" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">
                    + Generate New API Key
                </button>
            </div>

            <div class="overflow-x-auto border border-outline-variant rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md">Key Label</th>
                            <th class="p-md">Prefix</th>
                            <th class="p-md">Scopes</th>
                            <th class="p-md">Status</th>
                            <th class="p-md">Created At</th>
                            <th class="p-md text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="apiKeysTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <tr><td colspan="6" class="p-md text-center text-gray-500">Loading API keys...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 7: Team Permissions (Row 147) -->
        <div id="tabContent-permissions" class="hidden p-lg space-y-md">
            <div class="flex justify-between items-center bg-surface-container-low p-md rounded-xl border border-outline-variant">
                <div>
                    <h3 class="font-bold text-on-surface text-title-md">Team Permission Matrix</h3>
                    <p class="text-caption text-on-surface-variant">View and configure role-based access rules for system modules.</p>
                </div>
                <div class="flex gap-sm">
                    <select id="set-matrix-role-select" onchange="loadSettingsRoleMatrix()" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md bg-surface font-semibold text-primary">
                        <option value="1">Super Admin</option>
                        <option value="2">DPO / Privacy Officer</option>
                        <option value="3">Compliance Assessor</option>
                        <option value="4">Audit Specialist</option>
                        <option value="5">Business User</option>
                    </select>
                    <button onclick="saveSettingsRoleMatrix()" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">
                        Save Matrix Changes
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border border-outline-variant rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md w-12">Grant</th>
                            <th class="p-md">Module</th>
                            <th class="p-md">Permission Name</th>
                            <th class="p-md">Description</th>
                        </tr>
                    </thead>
                    <tbody id="settingsMatrixTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <tr><td colspan="4" class="p-md text-center text-gray-500">Loading team permission matrix...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 8: Compliance Documents Vault (Row 148) -->
        <div id="tabContent-documents" class="hidden p-lg space-y-md">
            <div class="flex justify-between items-center bg-surface-container-low p-md rounded-xl border border-outline-variant">
                <div>
                    <h3 class="font-bold text-on-surface text-title-md">Compliance Evidence Documents Vault</h3>
                    <p class="text-caption text-on-surface-variant">Store and manage compliance certifications, DPAs, and audit evidence files.</p>
                </div>
                <button onclick="openUploadDocModal()" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">
                    + Upload Compliance Document
                </button>
            </div>

            <div class="overflow-x-auto border border-outline-variant rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md">Document Title</th>
                            <th class="p-md">Category</th>
                            <th class="p-md">File Name &amp; Size</th>
                            <th class="p-md">Uploaded By</th>
                            <th class="p-md">Date</th>
                            <th class="p-md text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documentsTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <tr><td colspan="6" class="p-md text-center text-gray-500">Loading compliance documents...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create API Key (Row 146) -->
<div id="createApiKeyModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Generate New API Key</h3>
            <button onclick="closeCreateApiKeyModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="createApiKeyForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Key Label / Name *</label>
                <input type="text" name="key_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Production Webhook Key">
            </div>
            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeCreateApiKeyModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Generate Key</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Show Raw API Key Once (Row 146) -->
<div id="showApiKeyModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-emerald-50">
            <h3 class="font-bold text-emerald-800 text-title-md">API Key Generated Successfully</h3>
            <button onclick="closeShowApiKeyModal()" class="text-emerald-800 hover:text-emerald-950 text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md space-y-md">
            <p class="text-body-md text-on-surface">Save this API key immediately. <strong>It will NEVER be shown again!</strong></p>
            <code class="font-mono text-primary font-bold text-body-md bg-surface-container-low p-3 rounded-lg border border-outline-variant block text-center select-all break-all" id="rawApiKeyDisplay">...</code>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeShowApiKeyModal()" class="px-4 py-2 text-body-md text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 font-semibold cursor-pointer">I Have Saved My API Key</button>
        </div>
    </div>
</div>

<!-- Modal: Upload Compliance Document (Row 148) -->
<div id="uploadDocModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Upload Compliance Document</h3>
            <button onclick="closeUploadDocModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="uploadDocForm" class="p-md space-y-md" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Document Title *</label>
                <input type="text" name="title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. GDPR ISO 27001 Certification 2026">
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Category *</label>
                <select name="category" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="General Compliance" selected>General Compliance</option>
                    <option value="DPA / Vendor Contract">DPA / Vendor Contract</option>
                    <option value="Audit Certification">Audit Certification</option>
                    <option value="PIA / DPIA Report">PIA / DPIA Report</option>
                    <option value="Policy Signoff">Policy Signoff</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Document File * (PDF, DOCX, XLSX, PNG, JPG, Max 10MB)</label>
                <input type="file" name="document_file" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeUploadDocModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Upload Document</button>
            </div>
        </form>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/settings.js?v=<?= time() ?>"></script>