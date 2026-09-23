# Phase 1 Pre-Implementation Read-Only Audit

## 1. Current Authentication/Session Findings
- **Evidence:** `login.php` (Lines 34-44), `backend/config/db.php` (Lines 34-40), `logout.php` (Lines 17-25).
- **Session Settings:** Explicit cookie parameters (path, domain, secure, httponly, samesite) are **not** explicitly configured in `db.php` or `login.php`. It relies on the environment's `php.ini` defaults.
- **Session Regeneration:** `login.php` does **not** call `session_regenerate_id(true)` upon successful `password_verify`. It directly populates `$_SESSION`.
- **Logout:** `logout.php` successfully clears `$_SESSION`, destroys the cookie explicitly via `setcookie()`, and calls `session_destroy()`.
- **Browser Coexistence:** Using `session_name('privacyhq_portal')` in the portal will create an entirely separate HTTP cookie (e.g., `privacyhq_portal=XYZ` vs `PHPSESSID=ABC`). The two sessions will coexist in the browser without overlapping in PHP's memory, completely isolating the admin from the portal.
- **Decision:** APPROVED WITH CONDITION (Portal session creation must strictly enforce `session_regenerate_id(true)` since the admin side currently omits it).

## 2. Portal Token Database Pre-Check
- **Evidence:** `SHOW TABLES LIKE '%token%'` and `%otp%` returned 0 rows. `data_subjects.id` is `bigint(20) unsigned`.
- **Findings:** The database is completely clean of any conflicting token tables. 
- **Compatibility:** The proposed schema (`data_subject_id BIGINT UNSIGNED NOT NULL`) perfectly matches `data_subjects.id` ensuring foreign key constraints will succeed. Length constraints (hash=64), INT for attempts, and TIMESTAMP behaviors are fully compatible.
- **Decision:** APPROVED.

## 3. Data_Requests Migration Pre-Check
- **Evidence:** `SELECT VERSION()` (10.4.32-MariaDB), `SHOW CREATE TABLE data_requests`.
- **Findings:**
  - `request_type` is currently exactly `enum('access','erasure','rectification','portability','objection') NOT NULL`.
  - There are currently 7 rows (`access`=2, `erasure`=2, `portability`=3).
  - Triggers/Views: None exist for this table.
- **Code Dependencies (Must be updated during DSR phase):**
  - `pages/legacy/dsar.php` (UI dropdown)
  - `pages/legacy/data-requests.php` (UI dropdown & mapped switch statements)
  - `pages/dsr-management.php` (UI dropdown)
  - `api/save-dsr.php` (mapped variables)
- **Decision:** NEEDS DECISION (Safe to migrate in a future phase, but deferred from Phase 1).

## 4. Portal API Foundation
- **Evidence:** Repository search for existing services.
- **Findings:**
  - **`ConsentService` & `DsrService`**: Heavily rely on `DataSubject::findByEmail($email)`. Can be cleanly wrapped. The internal services expect an admin `$userId`, but can accept `NULL` for portal usage.
  - **`WorkflowService`**: Fully decoupled and uses event names (e.g. `dsr.created`). Safe to reuse.
  - **`NotificationService`**: Triggered automatically via `WorkflowService`. Safe to reuse.
  - **`db.php` (`log_audit_event`)**: Accepts `NULL` for `$user_id`. Safe to reuse for portal logging.
  - **`db.php` (CSRF)**: Universal `$_SESSION['csrf_token']` is initialized on every session. Safe to reuse across namespaces.
- **Decision:** APPROVED (Thin wrappers `PortalAuthService` and `PortalConsentService` will be required to abstract the `userId = NULL` requirement).

## 5. Email Delivery Audit
- **Evidence:** `grep_search` across the repository for `mail(`, `PHPMailer`, `SMTP`, `sendEmail`.
- **Findings:** The repository currently **lacks any outbound email infrastructure**. No `PHPMailer` dependency exists. No `mail()` function calls exist for notifications. The internal notification system is entirely database-driven (`notifications` table + UI badges).
- **Decision:** BLOCKED (Phase 1 requires a strategy for token delivery. A mock/logger delivery mechanism must be explicitly approved for development until an SMTP service is integrated).

## 6. Security Dependencies
- **Existing & Reusable:**
  - **CSRF:** Built-in `verify_csrf_token($token)`.
  - **Random generation:** `bin2hex(random_bytes(32))` is used natively.
  - **Audit logging:** `log_audit_event($pdo, ...)`.
- **Missing (Must be implemented for Phase 1):**
  - **Rate Limiting:** No existing rate-limiting logic (IP or user-based) exists in the codebase.
  - **Input Validation Wrappers:** No universal form-request validator exists; validation is done ad-hoc in controllers.
  - **HMAC Storage:** No HMAC helpers exist for the OTP storage requirement.
- **Decision:** APPROVED WITH CONDITION (PortalAuthService must implement its own standalone rate limit tracking against `portal_tokens`).

## 7. Existing File/Folder Conflicts
- **Evidence:** `list_dir` on `backend/api/portal/` and `portal/`.
- **Findings:** Neither directory exists. No file conflicts.
- **Decision:** APPROVED.

## 8. Exact Phase 1 File List
- `backend/api/portal/PortalBootstrap.php`
- `backend/services/PortalAuthService.php`
- `portal/index.php`
- `portal/verify.php`
- `portal/dashboard.php` (Empty shell for redirection target)
- `backend/api/portal/auth/request-link.php`
- `backend/api/portal/auth/verify-token.php`
- `backend/api/portal/auth/request-otp.php`
- `backend/api/portal/auth/verify-otp.php`
- `backend/api/portal/auth/logout.php`

## 9. Exact Phase 1 DB Changes
- `CREATE TABLE portal_tokens` (Exactly as proposed in the architecture document).
- *No ALTER TABLE data_requests in Phase 1.*

## 10. Phase 1 Test Matrix
**Authentication:**
- Request with valid email -> Link generated.
- Request with unknown email -> Generic success message (no link generated).
- Access `verify-token.php` with expired token -> Rejected.
- Access `verify-token.php` with invalid token -> Rejected.
- Access `verify-token.php` with already used token -> Rejected.
- Verify tokens cannot be brute-forced (length validation).

**OTP:**
- Request OTP -> OTP generated and attempts=0.
- Submit valid OTP -> Consumed, session elevated.
- Submit invalid OTP -> Rejected, attempts=1.
- Submit 5 invalid OTPs -> Attempts=5.
- Submit 6th attempt -> Rejected due to lockout.
- Attempt concurrent requests to bypass limit -> Prevented via transactional lock.
- Resubmit valid OTP after consumption -> Rejected (already used).

**Session:**
- Portal session created with `session_name('privacyhq_portal')`.
- Verify `session_regenerate_id(true)` successfully executes.
- Ensure admin dashboard (`PHPSESSID`) remains unaffected.
- Ensure Portal endpoints reject requests with only an admin cookie.
- Ensure Admin endpoints reject requests with only a portal cookie.
- Logout destroys only the portal session cookie.

**Rate Limiting:**
- Exceed 3 magic links per hour per email -> Rejected.
- Exceed 10 requests per IP per hour -> Rejected.
- Verify OTP issuance limit (e.g. max 5 OTPs per action session) is enforced.

**CSRF:**
- Valid token allows POST/PUT.
- Missing or invalid token returns 403 Forbidden.

## 11. Risks / Blockers
1. **Email Delivery Blocker:** No email service exists in the repository. We cannot deliver the Magic Link or OTP. 
2. **Admin Session Fixation Risk:** `login.php` does not regenerate session IDs. This is an existing flaw outside the portal scope, but worth noting.

## 12. Human Decisions Still Required
1. **Email Strategy:** Do we proceed with writing tokens to a local log file (e.g., `mock_emails.log`) for Phase 1 development, or do we halt to integrate an SMTP library like PHPMailer?
2. **Proceed to Implementation:** Please explicitly approve the commencement of Phase 1 based on these findings.
