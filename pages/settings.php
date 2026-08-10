<?php
// pages/settings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo '<div class="p-6 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl">Access Denied: Please log in.</div>';
    exit;
}

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$designation = $user['designation'] ?? 'Data Protection Officer';
$department = $user['department'] ?? 'Compliance & Legal';
$profileImage = $user['profile_image'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';
$roleId = $_SESSION['role_id'] ?? 0;
?>

<!-- Profile Header -->
<section class="mb-lg animate-in fade-in slide-in-from-top-4 duration-500">
    <div class="flex items-center gap-md p-md bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
        <div class="relative">
            <img id="settingsAvatar" class="w-16 h-16 rounded-full object-cover border-2 border-primary" src="<?= htmlspecialchars($profileImage) ?>" alt="Avatar">
            <div class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-surface rounded-full"></div>
        </div>
        <div class="flex-grow">
            <h2 id="settingsFullName" class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface"><?= htmlspecialchars($fullName ?: 'Admin User') ?></h2>
            <p id="settingsDesignation" class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($designation) ?></p>
            <div class="mt-1">
                <span class="bg-tertiary-fixed text-on-tertiary-fixed text-[10px] uppercase font-bold px-2 py-0.5 rounded-full tracking-wider">Premium Enterprise</span>
            </div>
        </div>
        <button onclick="openEditProfileModal()" class="text-primary hover:bg-primary-fixed p-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined" data-icon="edit">edit</span>
        </button>
    </div>
</section>

<!-- Settings Categories -->
<div class="space-y-md">
    <!-- Security Category -->
    <div class="space-y-base">
        <h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Security</h3>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
            <div class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="lock">lock</span>
                    <span class="font-body-lg text-body-lg">Two-Factor Authentication</span>
                </div>
                <div class="flex items-center gap-sm">
                    <span class="font-body-md text-body-md text-green-600">Enabled</span>
                    <span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
                </div>
            </div>
            <div onclick="openChangePasswordModal()" class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all hover:bg-surface-container-low">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="password">password</span>
                    <span class="font-body-lg text-body-lg">Change Password</span>
                </div>
                <span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
            </div>
        </div>
    </div>

    <!-- Preferences Category -->
    <div class="space-y-base">
        <h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Preferences</h3>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
            <a href="index.php?page=notification-preferences" class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all hover:bg-surface-container-low block">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="notifications_active">notifications_active</span>
                    <span class="font-body-lg text-body-lg">Notification Channels</span>
                </div>
                <span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
            </a>
            <div class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="dark_mode">dark_mode</span>
                    <span class="font-body-lg text-body-lg">Dark Mode</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input id="settingsDarkModeToggle" class="sr-only peer" type="checkbox" onchange="toggleDarkTheme(this.checked)"/>
                    <div class="w-11 h-6 bg-surface-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Team & API -->
    <div class="space-y-base">
        <h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Organization</h3>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
            <div onclick="alert('Team Permissions module is managed under User Management.')" class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all hover:bg-surface-container-low">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="groups">groups</span>
                    <span class="font-body-lg text-body-lg">Team Permissions</span>
                </div>
                <span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
            </div>
            <div onclick="alert('API Keys configuration is restricted to Enterprise Administrators.')" class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all hover:bg-surface-container-low">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="api">api</span>
                    <span class="font-body-lg text-body-lg">API Keys</span>
                </div>
                <span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
            </div>
        </div>
    </div>

    <!-- Compliance Category -->
    <div class="space-y-base">
        <h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Compliance</h3>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
            <a href="index.php?page=policies" class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all hover:bg-surface-container-low block">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="policy">policy</span>
                    <span class="font-body-lg text-body-lg">Legal &amp; Compliance Docs</span>
                </div>
                <span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
            </a>
            <a href="index.php?page=audit-logs" class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all hover:bg-surface-container-low block">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-outline" data-icon="history">history</span>
                    <span class="font-body-lg text-body-lg">Audit Logs</span>
                </div>
                <span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
            </a>
        </div>
    </div>

    <!-- Sign Out -->
    <div class="pt-lg">
        <button onclick="window.location.href='logout.php'" class="w-full flex items-center justify-center gap-sm bg-surface-container-lowest text-error border border-error/20 py-md rounded-xl font-body-lg hover:bg-error-container/20 transition-all active:scale-95 font-semibold">
            <span class="material-symbols-outlined" data-icon="logout">logout</span>
            Sign Out
        </button>
        <p class="text-center font-caption text-caption text-outline mt-md">Version 2.4.0 • Build 882</p>
    </div>
</div>

<!-- Modal: Edit Profile -->
<div id="editProfileModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 w-full max-w-md shadow-xl relative animate-in fade-in zoom-in-95 duration-200">
        <button onclick="closeEditProfileModal()" class="absolute top-4 right-4 text-outline hover:text-on-surface font-bold text-xl">&times;</button>
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">person</span>
            Edit Profile
        </h2>
        <form id="editProfileForm" onsubmit="submitProfileUpdate(event)" class="space-y-4" enctype="multipart/form-data">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">First Name</label>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Designation</label>
                <input type="text" name="designation" value="<?= htmlspecialchars($designation) ?>" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Department</label>
                <input type="text" name="department" value="<?= htmlspecialchars($department) ?>" class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Profile Image</label>
                <input type="file" name="profile_image" accept="image/*" class="w-full text-body-sm text-on-surface-variant">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant">
                <button type="button" onclick="closeEditProfileModal()" class="px-4 py-2 text-body-sm font-medium border border-outline-variant rounded-lg hover:bg-surface-container-low bg-surface text-on-surface">Cancel</button>
                <button type="submit" class="px-4 py-2 text-body-sm font-medium text-white bg-primary rounded-lg hover:bg-opacity-90">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Change Password -->
<div id="changePasswordModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 w-full max-w-md shadow-xl relative animate-in fade-in zoom-in-95 duration-200">
        <button onclick="closeChangePasswordModal()" class="absolute top-4 right-4 text-outline hover:text-on-surface font-bold text-xl">&times;</button>
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">lock</span>
            Change Password
        </h2>
        <form id="changePasswordForm" onsubmit="submitPasswordChange(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Current Password</label>
                <input type="password" name="current_password" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">New Password</label>
                <input type="password" name="new_password" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-on-surface-variant mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password" required class="w-full px-3 py-2 border border-outline-variant rounded-lg text-body-sm bg-surface text-on-surface">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant">
                <button type="button" onclick="closeChangePasswordModal()" class="px-4 py-2 text-body-sm font-medium border border-outline-variant rounded-lg hover:bg-surface-container-low bg-surface text-on-surface">Cancel</button>
                <button type="submit" class="px-4 py-2 text-body-sm font-medium text-white bg-primary rounded-lg hover:bg-opacity-90">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const isDark = localStorage.getItem('theme') === 'dark' || document.documentElement.classList.contains('dark');
    const toggleInput = document.getElementById('settingsDarkModeToggle');
    if (toggleInput) {
        toggleInput.checked = isDark;
    }
});

function toggleDarkTheme(checked) {
    if (checked) {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
        localStorage.setItem('theme', 'light');
    }
}

function openEditProfileModal() {
    document.getElementById('editProfileModal').classList.remove('hidden');
}

function closeEditProfileModal() {
    document.getElementById('editProfileModal').classList.add('hidden');
}

function openChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.remove('hidden');
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.add('hidden');
}

function submitProfileUpdate(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('editProfileForm'));

    fetch('backend/api/profile/update.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert('Profile updated successfully!');
            closeEditProfileModal();
            const firstName = formData.get('first_name');
            const lastName = formData.get('last_name');
            const designation = formData.get('designation');
            document.getElementById('settingsFullName').textContent = firstName + ' ' + lastName;
            document.getElementById('settingsDesignation').textContent = designation;
            if (res.data && res.data.profile_image) {
                document.getElementById('settingsAvatar').src = res.data.profile_image;
                const headerAv = document.querySelector('header img') || document.querySelector('header span[data-icon="person"]');
                if (headerAv) {
                    if (headerAv.tagName === 'IMG') {
                        headerAv.src = res.data.profile_image;
                    } else {
                        const img = document.createElement('img');
                        img.className = "w-10 h-10 rounded-full object-cover border border-primary/20";
                        img.src = res.data.profile_image;
                        headerAv.parentNode.replaceChild(img, headerAv);
                    }
                }
            }
        } else {
            alert(res.message);
        }
    })
    .catch(err => console.error(err));
}

function submitPasswordChange(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('changePasswordForm'));

    fetch('backend/api/profile/change-password.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert('Password changed successfully! Logging out for security...');
            window.location.href = 'logout.php';
        } else {
            alert(res.message);
        }
    })
    .catch(err => console.error(err));
}
</script>