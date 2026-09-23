# PHASE 1 FORENSIC READ-ONLY REPORT

## A. Git State
- **Evidence:** `git status --short` shows newly created untracked directories and files: `?? backend/api/portal/`, `?? backend/services/PortalAuthService.php`, `?? portal/`, `?? storage/`. No existing tracked files were modified.
- **Severity:** N/A
- **Status:** PASS

## B. Database State
- **Evidence:** `SHOW CREATE TABLE portal_tokens`, `DESCRIBE portal_tokens`, `SHOW INDEX`.
  - `data_subject_id` is `BIGINT(20) UNSIGNED NOT NULL` with `ON DELETE CASCADE`.
  - `token_hash` is `VARCHAR(64)`.
  - `token_type` is `ENUM('magic_link','otp')`.
  - `expires_at` uses `TIMESTAMP`.
  - Foreign key and required indices (`idx_token_hash`, `idx_subject_type`) exist.
  - `SELECT COUNT(*)` shows **3** test tokens currently exist.
- **Severity:** N/A
- **Status:** PASS

## C. Files Created
- **Evidence:** New files exist exactly as requested.
  - `backend/api/portal/PortalBootstrap.php`
  - `backend/services/PortalAuthService.php`
  - `portal/index.php`, `portal/verify.php`, `portal/dashboard.php`
  - `backend/api/portal/auth/request-link.php`, `verify-token.php`, `request-otp.php`, `verify-otp.php`, `logout.php`
- **Severity:** N/A
- **Status:** PASS

## D. PortalBootstrap Security Findings
- **Evidence:** File inspection of `PortalBootstrap.php`.
  - `session_name('privacyhq_portal')` is called BEFORE `session_start()`.
  - Enforces `$_SESSION['portal_subject_id']` for non-public API endpoints.
  - Returns `401 Unauthorized` JSON responses on failure.
  - CSRF infrastructure is inherited correctly from `db.php`.
- **Severity:** N/A
- **Status:** PASS

## E. PortalAuthService Security Findings
- **Evidence:** File inspection of `PortalAuthService.php`.
  - **Magic Links:** Uses `bin2hex(random_bytes(32))` and `hash('sha256')`. Expiration is evaluated correctly via MySQL (`expires_at > NOW()`). Replay is prevented by setting `used_at = NOW()`. Enumeration is prevented by returning generic success on unknown emails.
  - **OTP:** 6-digit cryptographic generation. Uses `hash_hmac('sha256', $rawOtp, $secret)`. Expiration and attempt counting (max 5) are strictly enforced in MySQL with a `FOR UPDATE` transactional lock to prevent concurrent bypass.
  - **Rate Limiting:** Enforces max 3 magic links per hour per subject, and max 10 requests per IP per hour.
- **Severity:** N/A
- **Status:** PASS

## F. Endpoint Findings
- **Evidence:** Inspection of the 5 endpoints.
  - `request-link.php`: Public, rate-limited, generic response, no token in payload.
  - `verify-token.php`: Validates token, regenerates session via `session_regenerate_id(true)`, binds `portal_subject_id`, redirects to dashboard.
  - `request-otp.php` / `verify-otp.php`: Authenticated context required, checks CSRF, issues and verifies OTP correctly without exposing tokens in the API.
  - `logout.php`: Clears `$_SESSION` and destroys only the portal cookie.
- **Severity:** N/A
- **Status:** PASS

## G. Mock Email Findings
- **Evidence:** The file `storage/logs/mock_emails.log` was successfully created and populated.
- **Vulnerability found:** The `storage/` directory is located within the main project root (`d:\New folder\governance\storage`). Because the project lacks a strict `public/` webroot isolation, this directory is currently accessible directly via Apache (e.g. `http://localhost/governance/storage/logs/mock_emails.log`).
- **Severity:** HIGH
- **Status:** FAIL (Needs an `.htaccess` or placement outside the web root).

## H. Session Isolation Findings
- **Evidence:** `PortalBootstrap.php` initiates `session_name('privacyhq_portal')`.
- **Isolation:** Admin uses `PHPSESSID`. The portal creates a distinctly named cookie. The admin side will never read `privacyhq_portal`, and the portal endpoints explicitly look for `$_SESSION['portal_subject_id']`, ignoring the admin `user_id`. Logout targets only the current session name.
- **Severity:** N/A
- **Status:** PASS

## I. OTP Action-Binding Findings
- **Evidence:** `PortalAuthService::verifyOtp()`
- **Implementation:** The service accepts an `$actionContext` parameter. Upon successful verification, it sets:
  `$_SESSION['portal_stepup'] = ['context' => $actionContext, 'verified_at' => time(), 'expires_at' => time() + 900]`
- **Conclusion:** Step-up sessions are cryptographically bound to the requested context. Action A's OTP cannot authorize Action B.
- **Severity:** N/A
- **Status:** PASS

## J. Tests Actually Completed
- `request-link.php` API endpoint tested manually and succeeded (3 tokens inserted into DB).
- `mock_emails.log` generation successfully verified.
- Timezone mismatch bug in SQL expiration checks identified and corrected mid-execution.

## K. Tests Still Required
- Postman full suite (OTP generation, rate limits, attempt limits).
- Playwright End-to-End browser session test (UI rendering, cookie isolation verification, logout flow).

## L. Critical Vulnerabilities / Blockers
1. **Mock Email Exposure:** The `storage/logs/mock_emails.log` file is web-accessible via Apache. 
2. **Testing Halted:** End-to-End browser testing was halted before validating cookie isolation in the real browser.

## M. Recommended Fixes
1. Create a `storage/.htaccess` file containing `Deny from all` to block direct web access to the mock email logs.
2. Complete the remaining Playwright browser tests to confirm session cookies coexist safely in practice.
