# Phase 1 Authentication Forensic Report

## 1. Scope
This report documents a read-only forensic investigation into three authentication test failures identified during the Phase 1 Authenticated Playwright E2E validation:
1. Magic-link replay rejection (FAIL)
2. Portal/admin session isolation (FAIL)
3. OTP request/UI timeout (FAIL)

The objective is to determine the exact root causes of these failures based on source code analysis and system behavior without modifying application logic or data.

## 2. Evidence Reviewed
- **Source Files:**
  - `backend/services/PortalAuthService.php`
  - `backend/api/portal/auth/request-link.php`
  - `backend/api/portal/auth/verify-token.php`
  - `backend/api/portal/PortalBootstrap.php`
  - `backend/api/portal/auth/request-otp.php`
  - `backend/config/db.php`
  - `portal/index.php`
  - `portal/dashboard.php`
  - `index.php` (Admin dashboard)
  - `login.php` (Admin login)
- **Test Artifacts:**
  - Playwright E2E Node.js script (`run_tests.js`)
- **Database:**
  - MariaDB `privacyhq.portal_tokens` queries verifying token consumption (`used_at`).

## 3. Magic-Link Replay

### Observed Behavior
The Playwright test attempted to reuse a magic link in a new browser context. The test expected to find the text "Invalid or expired token" on the screen. Because the text was not found, the test marked the replay as "FAIL".

### Source Trace
1. `PortalAuthService.php::verifyMagicLink($rawToken)` validates the token. The SQL query explicitly includes `AND used_at IS NULL`.
2. Upon successful verification, the token is consumed atomically: `UPDATE portal_tokens SET used_at = NOW() WHERE id = ?`.
3. When the consumed token is reused, `verifyMagicLink` returns `false`.
4. `verify-token.php` correctly detects the failure and executes: `header('Location: /governance/portal/index.php?error=invalid_token'); exit;`
5. `portal/index.php` loads the login page but does **not** contain any PHP code to read `$_GET['error']` or render the error message into the DOM. (The `<div id="message">` is only modified via client-side JavaScript upon form submission).

### Database Evidence
MariaDB queries confirmed that `used_at` is populated immediately upon the first successful magic link use.

### Root Cause
This is a **test-harness false positive** combined with a frontend UX omission. The backend perfectly rejects the consumed token, but the frontend (`portal/index.php`) fails to display the `?error=invalid_token` parameter. The Playwright script failed because it asserted the presence of a specific UI error string that the frontend never renders.

### Security Impact
- **Severity:** Low (UX issue, not a security vulnerability)
- **Exploitability:** None. The backend prevents actual replay.
- **Genuine Application Behavior:** The application correctly rejects the replay; it only fails to inform the user.

### Remediation Direction
Update `portal/index.php` to read the `$_GET['error']` parameter and display an appropriate error message to the user.

## 4. Session Isolation

### Observed Behavior
The Playwright script navigated to `http://localhost/governance/admin/` and asserted that the browser would be redirected to `login.php`. Because it wasn't redirected to `login.php`, the script assumed the portal session bled into the admin environment and marked it "FAIL".

### Source Trace
1. **Portal:** Uses `session_name('privacyhq_portal')` and authenticates the user by setting `$_SESSION['portal_subject_id']`.
2. **Admin:** The admin entry point (`index.php` and `login.php`) relies on the default PHP session name (`PHPSESSID`). Admin authentication sets `$_SESSION['user_id']`.
3. The `admin/` directory does **not exist** in the repository structure. The admin interface is at the root (`/governance/index.php`).

### Browser Evidence
When the script requested `http://localhost/governance/admin/`, the web server encountered a 404 Not Found error (or a directory listing denial). Because it was a 404, no redirect to `login.php` occurred. The script's assertion `page.url().includes('login.php')` failed.

### Root Cause
This is a **test-harness false positive**. The Playwright script requested a non-existent directory (`/admin/`) instead of the actual admin path (`/governance/index.php`). Furthermore, the code review confirms complete session isolation: the portal uses `privacyhq_portal` and `portal_subject_id`, while the admin uses `PHPSESSID` and `user_id`. A portal user cannot access admin routes.

### Security Impact
- **Severity:** None
- **Exploitability:** None
- **Genuine Application Behavior:** The application securely isolates sessions. 

### Remediation Direction
Correct the E2E test harness to navigate to `/governance/index.php` to accurately test admin session isolation.

## 5. OTP Request

### Observed Behavior
The Playwright script clicked "Request OTP" in the portal dashboard and timed out waiting for the OTP input form (`#otpFormGroup:not(.hidden)`) to appear.

### Source Trace
1. `portal/dashboard.php` sends a POST request to `backend/api/portal/auth/request-otp.php` with a JSON payload: `{"csrf_token": "...", "action_context": "erasure_test"}`.
2. `request-otp.php` immediately calls `verify_csrf_token($_POST['csrf_token'] ?? ...)`.
3. Because the request body is JSON (and not `application/x-www-form-urlencoded`), PHP's native `$_POST` array is empty.
4. `verify_csrf_token()` compares against an empty string, causing CSRF validation to fail.
5. The API returns a 403 Forbidden with `{"status": "error", "message": "Invalid CSRF token"}`.
6. `dashboard.php` JavaScript parses the error and updates the message div but does not unhide the `#otpFormGroup`. The Playwright script times out waiting for it.

### Network/API Evidence
The API endpoint rejects the request at the CSRF validation step before ever calling `PortalAuthService::requestOtp()`.

### Root Cause
**Genuine application defect.** The backend CSRF verification logic (`request-otp.php`) incorrectly attempts to read the token from `$_POST`, which is empty for JSON payloads. The frontend sends JSON, leading to an immediate CSRF rejection.

### Security Impact
- **Severity:** Medium (Functional breakage)
- **Exploitability:** N/A (Blocks legitimate functionality rather than exposing a vulnerability)
- **Genuine Application Behavior:** This is a true defect in the API endpoint handling JSON input.

### Remediation Direction
Update `request-otp.php` to decode the JSON payload from `php://input` **before** performing the CSRF verification, or instruct the frontend to send the CSRF token in an HTTP header (e.g., `X-CSRF-TOKEN`).

## 6. Tests Blocked Because of These Failures

The following downstream tests could not be executed because the OTP request failed:
- **OTP Verification:** Blocked because no OTP was generated or sent, preventing tests for correct, incorrect, limit, reuse, and expiry validation.
- **Step-Up Action Binding:** Blocked because step-up requires a successfully verified OTP.
- **IDOR / Subject Ownership:** Blocked because these tests were sequenced after the OTP flow in the script.
- **Logout:** Blocked because the script timed out and crashed before reaching the logout phase.

## 7. Recommended Fix Order

Based on dependencies, the remediation should proceed in the following order:

1. **Fix OTP Request CSRF Handling (`backend/api/portal/auth/request-otp.php`)**
   - *Why:* This is a genuine functional blocker. Fixing this unblocks all downstream E2E tests (OTP Verification, Step-Up, IDOR, Logout).
2. **Fix Portal Frontend Error Display (`portal/index.php`)**
   - *Why:* Ensures users see why their magic link was rejected, and allows the E2E script to correctly assert replay rejection.
3. **Update E2E Test Harness**
   - *Why:* Fix the incorrect admin URL (`/admin/` to `/governance/index.php`) and adjust any frontend assertions to align with the corrected UI.
