# Phase 1 Logout UX Remediation Report

## 1. Trace and Root Cause Analysis
The forensic read-only trace revealed that `backend/api/portal/PortalBootstrap.php` enforced authentication for the entire portal space. However, it utilized a blanket enforcement rule: any unauthenticated request was rejected with an `HTTP/1.1 401 Unauthorized` raw JSON payload. 

When a user clicked "Logout", their `privacyhq_portal` session was successfully destroyed via `logout.php`. However, because the logout process occurred via an AJAX call (which then allowed the browser to reload the page or navigate away), the subsequent page load for `dashboard.php` hit `PortalBootstrap.php` without an active session. Because `PortalBootstrap.php` had no routing logic to distinguish between an API fetch and a standard browser navigation, it blindly output the raw JSON 401 error directly to the browser window.

## 2. Minimal Safe Implementation
The minimal safe fix was strictly scoped to `PortalBootstrap.php`'s authentication guard.

**Modifications:**
- Modified `backend/api/portal/PortalBootstrap.php` line 26-34.
- Added a conditional branch utilizing `strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false` to distinguish API endpoint requests from browser page navigations.

**Rationale:**
- **API Clients**: If the URL path contains `/api/`, the behavior remains strictly unchanged. It issues `HTTP/1.1 401 Unauthorized` alongside the exact original JSON payload.
- **Browser Navigations**: If the URL path lacks `/api/` (such as `portal/dashboard.php`), the guard gracefully redirects the browser to `index.php`. Since the `Location` is relative and the execution context lies within the `/portal/` directory, this resolves perfectly to `portal/index.php`.

## 3. Playwright Validation Results
A dedicated headless Playwright test (`playwright_tests/test_logout_ux.js`) was engineered to explicitly validate the requested flow and isolation guarantees.

**Test Outputs:**
- `Admin logged in successfully.`
- `Portal logged in successfully.`
- `PASS: Browser landed on portal/index.php after logout.`
- `PASS: No raw JSON 401 displayed on logout redirect.`
- `PASS: Direct navigation to dashboard redirects to index.php.`
- `PASS: API endpoint still returns JSON 401.`
- `PASS: Admin session remains unaffected.`

## 4. API Regression & Security Checks
- **Unauthenticated APIs**: Explicit validation confirmed that directly hitting `request-otp.php` without a session continues to return the exact 401 JSON error payload, preserving all backend security guarantees.
- **CSRF Isolation**: The modification does not bypass or evaluate CSRF validation; it strictly manages unauthenticated redirection.
- **Session Isolation**: Playwright cookie analysis demonstrated two distinct secure session files exist dynamically: `PHPSESSID` (Admin) and `privacyhq_portal` (Portal). Logging out of the portal explicitly cleared the `privacyhq_portal` cookie and session data while leaving `PHPSESSID` functionally untouched. The admin session was verified live via the Playwright test and successfully maintained state post-logout.
- **Token Exposure**: No sensitive OTPs or magic links were leaked into the URL, page source, console, or network logs during the redirection process.

## 5. Review and Repository State
- **Files Changed**: `backend/api/portal/PortalBootstrap.php`
- **Temporary Test Files Created**: `playwright_tests/test_logout_ux.js` (untracked)
- **Static Validation**: `php -l` passed without syntax errors. `git diff --check` reported no whitespace or formatting errors.
- **Status**: The repository remains clean with no commits or pushes performed. The remediation is strictly verified and scoped perfectly to the user requirements.
