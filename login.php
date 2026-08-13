<?php
$error = '';
require_once __DIR__ . '/backend/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT u.*, r.role_name 
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.email = ? AND u.deleted_at IS NULL AND u.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Fetch user permissions
                $stmtPerms = $pdo->prepare("
                    SELECT p.permission_name 
                    FROM role_permissions rp
                    JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = ?
                ");
                $stmtPerms->execute([$user['role_id']]);
                $perms = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

                // Start session and store credentials
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email'];
                $_SESSION['permissions'] = $perms;
                $_SESSION['profile_image'] = $user['profile_image'] ?? null;

                // Update last login
                $stmtLogin = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                $stmtLogin->execute([$user['id']]);

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'Authentication service error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }
    </script>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>PrivacyHQ - Enterprise Login</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-surface-variant": "var(--on-surface-variant)",
                        "on-secondary-container": "var(--on-secondary-container)",
                        "secondary-fixed": "var(--secondary-fixed)",
                        "outline": "var(--outline)",
                        "surface-variant": "var(--surface-variant)",
                        "on-secondary": "var(--on-secondary)",
                        "surface-dim": "var(--surface-dim)",
                        "on-tertiary-fixed-variant": "var(--on-tertiary-fixed-variant)",
                        "error": "var(--error)",
                        "tertiary-fixed": "var(--tertiary-fixed)",
                        "secondary": "var(--secondary)",
                        "on-secondary-fixed-variant": "var(--on-secondary-fixed-variant)",
                        "surface": "var(--surface)",
                        "on-primary-container": "var(--on-primary-container)",
                        "on-secondary-fixed": "var(--on-secondary-fixed)",
                        "tertiary-container": "var(--tertiary-container)",
                        "on-surface": "var(--on-surface)",
                        "surface-container": "var(--surface-container)",
                        "surface-container-lowest": "var(--surface-container-lowest)",
                        "surface-container-high": "var(--surface-container-high)",
                        "secondary-fixed-dim": "var(--secondary-fixed-dim)",
                        "surface-bright": "var(--surface-bright)",
                        "on-background": "var(--on-background)",
                        "error-container": "var(--error-container)",
                        "on-tertiary": "var(--on-tertiary)",
                        "background": "var(--background)",
                        "on-primary": "var(--on-primary)",
                        "inverse-surface": "var(--inverse-surface)",
                        "tertiary": "var(--tertiary)",
                        "on-tertiary-fixed": "var(--on-tertiary-fixed)",
                        "primary-container": "var(--primary-container)",
                        "tertiary-fixed-dim": "var(--tertiary-fixed-dim)",
                        "primary-fixed": "var(--primary-fixed)",
                        "secondary-container": "var(--secondary-container)",
                        "surface-tint": "var(--surface-tint)",
                        "on-tertiary-container": "var(--on-tertiary-container)",
                        "on-primary-fixed": "var(--on-primary-fixed)",
                        "primary": "var(--primary)",
                        "primary-fixed-dim": "var(--primary-fixed-dim)",
                        "inverse-primary": "var(--inverse-primary)",
                        "on-error-container": "var(--on-error-container)",
                        "on-primary-fixed-variant": "var(--on-primary-fixed-variant)",
                        "surface-container-low": "var(--surface-container-low)",
                        "surface-container-highest": "var(--surface-container-highest)",
                        "inverse-on-surface": "var(--inverse-on-surface)",
                        "on-error": "var(--on-error)",
                        "outline-variant": "var(--outline-variant)"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "sm": "8px",
                        "base": "4px",
                        "stack-gap": "12px",
                        "md": "16px",
                        "xs": "4px",
                        "container-padding": "16px",
                        "lg": "24px",
                        "xl": "32px"
                    },
                    "fontFamily": {
                        "headline-lg-mobile": ["Inter"],
                        "headline-lg": ["Inter"],
                        "body-lg": ["Inter"],
                        "display": ["Inter"],
                        "title-md": ["Inter"],
                        "body-md": ["Inter"],
                        "caption": ["Inter"],
                        "label-md": ["Inter"]
                    },
                    "fontSize": {
                        "headline-lg-mobile": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "display": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "title-md": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "caption": ["11px", {"lineHeight": "14px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.01em", "fontWeight": "500"}]
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --on-surface-variant: #404752;
            --on-secondary-container: #003f6d;
            --secondary-fixed: #d1e4ff;
            --outline: #717783;
            --surface-variant: #e3e2e1;
            --on-secondary: #ffffff;
            --surface-dim: #dadad9;
            --on-tertiary-fixed-variant: #004881;
            --error: #ba1a1a;
            --tertiary-fixed: #d3e4ff;
            --secondary: #0061a3;
            --on-secondary-fixed-variant: #00497d;
            --surface: #faf9f8;
            --on-primary-container: #ffffff;
            --on-secondary-fixed: #001d36;
            --tertiary-container: #2679c9;
            --on-surface: #1a1c1c;
            --surface-container: #efeeed;
            --surface-container-lowest: #ffffff;
            --surface-container-high: #e9e8e7;
            --secondary-fixed-dim: #9ecaff;
            --surface-bright: #faf9f8;
            --on-background: #1a1c1c;
            --error-container: #ffdad6;
            --on-tertiary: #ffffff;
            --background: #f4f3f2;
            --on-primary: #ffffff;
            --inverse-surface: #2f3130;
            --tertiary: #0060a9;
            --on-tertiary-fixed: #001c38;
            --primary-container: #0078d4;
            --tertiary-fixed-dim: #a2c9ff;
            --primary-fixed: #d3e3ff;
            --secondary-container: #5badff;
            --surface-tint: #0060ab;
            --on-tertiary-container: #ffffff;
            --on-primary-fixed: #001c39;
            --primary: #005faa;
            --primary-fixed-dim: #a3c9ff;
            --inverse-primary: #a3c9ff;
            --on-error-container: #93000a;
            --on-primary-fixed-variant: #004883;
            --surface-container-low: #f4f3f2;
            --surface-container-highest: #e3e2e1;
            --inverse-on-surface: #f1f0ef;
            --on-error: #ffffff;
            --outline-variant: #c0c7d4;
        }

        .dark {
            --on-surface-variant: #cbd5e1;
            --on-secondary-container: #d1e4ff;
            --secondary-fixed: #00497d;
            --outline: #94a3b8;
            --surface-variant: #334155;
            --on-secondary: #0f172a;
            --surface-dim: #1e293b;
            --on-tertiary-fixed-variant: #d3e4ff;
            --error: #ffb4ab;
            --tertiary-fixed: #004881;
            --secondary: #9ecaff;
            --on-secondary-fixed-variant: #d1e4ff;
            --surface: #1e293b;
            --on-primary-container: #0f172a;
            --on-secondary-fixed: #d1e4ff;
            --tertiary-container: #a2c9ff;
            --on-surface: #f8fafc;
            --surface-container: #1e293b;
            --surface-container-lowest: #0f172a;
            --surface-container-high: #334155;
            --secondary-fixed-dim: #00497d;
            --surface-bright: #1e293b;
            --on-background: #f8fafc;
            --error-container: #93000a;
            --on-tertiary: #0f172a;
            --background: #0f172a;
            --on-primary: #0f172a;
            --inverse-surface: #f1f0ef;
            --tertiary: #a2c9ff;
            --on-tertiary-fixed: #d3e4ff;
            --primary-container: #38bdf8;
            --tertiary-fixed-dim: #004881;
            --primary-fixed: #004883;
            --secondary-container: #00497d;
            --surface-tint: #a3c9ff;
            --on-tertiary-container: #0f172a;
            --on-primary-fixed: #d3e3ff;
            --primary: #38bdf8;
            --primary-fixed-dim: #004883;
            --inverse-primary: #005faa;
            --on-error-container: #ffb4ab;
            --on-primary-fixed-variant: #d3e3ff;
            --surface-container-low: #0f172a;
            --surface-container-highest: #475569;
            --inverse-on-surface: #1a1c1c;
            --on-error: #0f172a;
            --outline-variant: #475569;
        }

        /* Class Overrides for Non-Tailwind Colors or Hardcoded Elements */
        .dark body {
            background-color: var(--background) !important;
            color: var(--on-surface) !important;
        }
        .dark .bg-white {
            background-color: var(--surface) !important;
        }
        .dark .bg-gray-50, .dark .bg-gray-100 {
            background-color: var(--surface-container-low) !important;
        }
        .dark .border-gray-100, .dark .border-gray-200 {
            border-color: var(--outline-variant) !important;
        }
        .dark .text-gray-900, .dark .text-gray-800 {
            color: var(--on-surface) !important;
        }
        .dark .text-gray-700, .dark .text-gray-600 {
            color: var(--on-surface-variant) !important;
        }
        .dark .text-gray-500, .dark .text-gray-400 {
            color: var(--outline) !important;
        }
        .dark input, .dark select, .dark textarea {
            background-color: var(--surface-container-lowest) !important;
            color: var(--on-surface) !important;
            border-color: var(--outline-variant) !important;
        }
        .dark label {
            color: var(--on-surface-variant) !important;
        }
        .dark .glass-background {
            background: var(--surface) !important;
            border-color: var(--outline-variant) !important;
        }

        body, header, nav, main, div, p, span, input, select, textarea, button, a {
            transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .fluent-input:focus-within {
            box-shadow: 0 0 0 2px #0078d4;
        }
        .glass-background {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
    </style>
<style>
    body {
      min-height: 100dvh;
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen p-md">
<!-- Background Decorative Element -->
<div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">

</div>
<!-- Login Container -->
<main class="relative z-10 w-full max-w-md animate-fade-in">
<div class="bg-surface-container-lowest shadow-lg rounded-xl overflow-hidden border border-surface-variant flex flex-col p-lg md:p-xl gap-lg">
<!-- Branding -->
<div class="flex flex-col items-center gap-base">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-primary text-[40px]" style="font-variation-settings: 'FILL' 1;">security</span>
<h1 class="font-display text-display text-primary">PrivacyHQ</h1>
</div>
<p class="font-body-md text-on-surface-variant text-center px-md">
                    Secure Enterprise Data Governance &amp; Compliance Portal
                </p>
</div>
<!-- Welcome Message -->
<div class="space-y-base text-center">
<h2 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface">Welcome back</h2>
<p class="font-body-md text-on-surface-variant">Please enter your credentials to continue</p>
</div>

<?php if (!empty($error)): ?>
    <div class="p-3 bg-red-50 text-red-700 border border-red-200 text-xs rounded-lg text-center font-medium animate-pulse">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Form -->
<form class="flex flex-col gap-md" method="POST" action="login.php">
<!-- Email Field -->
<div class="flex flex-col gap-xs">
<label class="font-label-md text-label-md text-on-surface-variant ml-1" for="email">Email Address</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">mail</span>
<input class="w-full pl-10 pr-md py-3 bg-surface border border-outline-variant rounded-lg font-body-md text-on-surface focus:outline-none focus:border-primary transition-all shadow-sm"
id="email"
name="email"
placeholder="name@company.com"
type="email"
required/>
</div>
<!-- Password Field -->
<div class="flex flex-col gap-xs">
<div class="flex justify-between items-center ml-1">
<label class="font-label-md text-label-md text-on-surface-variant" for="password">Password</label>
<a class="font-label-md text-label-md text-primary hover:underline transition-all" href="#">Forgot Password?</a>
</div>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
<input class="w-full pl-10 pr-md py-3 bg-surface border border-outline-variant rounded-lg font-body-md text-on-surface focus:outline-none focus:border-primary transition-all shadow-sm"
id="password"
name="password"
placeholder="••••••••"
type="password"
required/>
</div>
<!-- Remember Me -->
<div class="flex items-center gap-sm">
<input class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary" id="remember" type="checkbox"/>
<label class="font-body-md text-body-md text-on-surface-variant" for="remember">Keep me signed in</label>
</div>
<!-- Sign In Button -->
<button class="mt-base bg-primary-container hover:bg-primary text-on-primary-container font-title-md text-title-md py-3 rounded-lg shadow-md hover:shadow-lg transform active:scale-95 transition-all flex items-center justify-center gap-sm" type="submit">
<span>Sign In</span>
<span class="material-symbols-outlined">login</span>
</button>
</form>
<!-- SSO Divider -->
<div class="relative flex items-center py-base">
<div class="flex-grow border-t border-outline-variant"></div>
<span class="flex-shrink mx-4 font-label-md text-label-md text-outline uppercase tracking-wider">or</span>
<div class="flex-grow border-t border-outline-variant"></div>
</div>
<!-- SSO Options -->
<button class="w-full flex items-center justify-center gap-md py-3 px-md border border-outline-variant rounded-lg bg-surface-container-lowest hover:bg-surface-container-low transition-all shadow-sm active:scale-95">
<div class="w-5 h-5 flex items-center justify-center">
<svg height="21" viewbox="0 0 21 21" width="21" xmlns="http://www.w3.org/2000/svg">
<rect fill="#f35325" height="10" width="10" x="0" y="0"></rect>
<rect fill="#81bc06" height="10" width="10" x="11" y="0"></rect>
<rect fill="#05a6f0" height="10" width="10" x="0" y="11"></rect>
<rect fill="#ffba08" height="10" width="10" x="11" y="11"></rect>
</svg>
</div>
<span class="font-body-md text-body-md text-on-surface font-semibold">Sign in with Azure AD</span>
</button>
</div>
<!-- Footer Links -->
<footer class="mt-lg flex justify-between px-md">
<p class="font-caption text-caption text-outline">© 2024 PrivacyHQ Inc.</p>
<div class="flex gap-md">
<a class="font-caption text-caption text-outline hover:text-primary transition-colors" href="#">Privacy Policy</a>
<a class="font-caption text-caption text-outline hover:text-primary transition-colors" href="#">Terms of Service</a>
</div>
</footer>
</main>
<!-- Side Image Decoration (Visible on Large Screens) -->
<div class="hidden lg:block fixed right-0 top-0 h-full w-1/3 z-0">
<div class="w-full h-full bg-cover bg-center opacity-80" data-alt="A professional high-tech corporate office lobby with clean glass architecture, minimalist furniture, and subtle blue architectural lighting. The environment feels secure, modern, and high-end, featuring abstract data visualization panels on the walls. The overall mood is sophisticated and authoritative, reflecting a premium enterprise software aesthetic." style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuA1bA_OVJFoHkxG3onnW-xUu4gi885Lh7Mlc1yVNJudFtgslbksq89mhJUfFr1SZcOL0LoFbI30tlSt_1iwqVAeI9j7EMq6RE_UysHr6QsIGPTQaHYOP4_3qn17dso1tT34LCuh8gXxstbG-CFU_jyV3ceghHMoPiqOFVuIXxWpGXXFzig9GIB67wmA4gXGcZmv0DHmUj_WbZ5Z3ZNDeQlEGESn6SCsr7f4TsnRLvHA-Y7ylAWVELjVhOihMPzU-4vQKhWx3mqO8dZ_')">
</div>
<div class="absolute inset-0 bg-gradient-to-l from-transparent to-[#F3F2F1]"></div>
</div>
<script>
        // Simple micro-interaction for button press
        document.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', function(e) {
                let x = e.clientX - e.target.offsetLeft;
                let y = e.clientY - e.target.offsetTop;
                let ripples = document.createElement('span');
                ripples.style.left = x + 'px';
                ripples.style.top = y + 'px';
                this.appendChild(ripples);
                setTimeout(() => ripples.remove(), 600);
            });
        });

        // Theme toggle logic (if needed in future)
        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (isDark) {
            // document.documentElement.classList.add('dark');
            // Keeping light mode as requested by prompt
        }
    </script>
</body></html>