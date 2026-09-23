# Phase 1 OTP Attempt & Logout Forensic Report

## 1. Scope
This report provides a read-only forensic analysis of the two defects discovered during the Phase 1 Authenticated Playwright E2E Retest: the failure to enforce OTP attempt limits (critical security) and the raw JSON output when accessing protected UI pages after logout (UX). No code modifications have been made during this analysis.

## 2. OTP Attempt Limit

### Observed E2E Behavior
Submitting an incorrect 6-digit OTP correctly results in a failure response, but it completely fails to increment the attempt counter on the active OTP token. An attacker can repeatedly submit incorrect OTPs without ever locking out the token, enabling an infinite brute-force attack.

### Source Trace
The verification logic in `backend/services/PortalAuthService.php` (`verifyOtp` method) processes the incoming OTP by hashing it and then executing a `SELECT ... FOR UPDATE` query against the `portal_tokens` table.

### Database Schema
The `portal_tokens` table tracks: `id`, `data_subject_id`, `token_hash`, `token_type`, `attempts`, `expires_at`, `used_at`, and `ip_address`.

### Exact Root Cause
The database query in `verifyOtp` explicitly filters by `token_hash = ?`. 
When an incorrect OTP is supplied, it generates a mismatched HMAC hash. The query `SELECT ... WHERE token_hash = [WRONG_HASH]` returns zero rows. Because no row is found, the application immediately returns `false` and rolls back the transaction. The active token (which has a different hash) is never retrieved, meaning its `attempts` column is completely inaccessible to the logic intended to increment it.

### Security Impact
This is a critical vulnerability. An attacker with access to the portal dashboard can write a script to rapidly guess the 6-digit OTP (1,000,000 combinations). Because incorrect guesses never increment the `attempts` counter of the active token, the token remains valid for its full 10-minute lifespan. An attacker can easily guess the correct OTP within this window.

### Required Security Invariants
A corrected implementation must guarantee the following without unnecessary architectural changes:
- **Independent Lookup**: The active OTP token must be identified by `data_subject_id` and `token_type` (independent of the submitted OTP value) so the row can be retrieved and its attempts incremented regardless of whether the guess is right or wrong.
- **Increment First**: Attempts must be checked against the maximum threshold *before* accepting the OTP, and incorrect attempts must increment atomically.
- **Concurrency Protection**: The maximum attempts cannot be bypassed through concurrency (requires retaining the `FOR UPDATE` row lock).
- **State Enforcement**: Successful verification marks the token used. Expired or used tokens cannot authenticate.
- **Action Context Binding**: An additional vulnerability was discovered during tracing: `action_context` is not stored in the database upon OTP generation, meaning `verifyOtp` blindly trusts the client-provided `action_context` and elevates the session for it. The `action_context` must remain bound securely to the token in the database to prevent privilege escalation (e.g. requesting an OTP for `view` but submitting it to authorize `delete`).

### Remediation Direction
Update the `verifyOtp` query to `SELECT ... WHERE data_subject_id = ? AND token_type = 'otp' ORDER BY id DESC LIMIT 1 FOR UPDATE`. Once the active token is locked and loaded in memory, increment its attempts, then verify `hash_equals($tokenRow['token_hash'], $submittedHash)`. Additionally, introduce an `action_context` column to `portal_tokens` to strictly bind the OTP to the originally requested context.

## 3. Logout UX

### Observed Behavior
After a successful logout, if a user navigates directly back to `portal/dashboard.php`, the browser displays a raw JSON string: `{"status":"error","message":"Unauthorized access. Portal session required."}` instead of redirecting the user to the portal login page.

### Source Trace
The `dashboard.php` file includes `backend/api/portal/PortalBootstrap.php` at the very top of the document to enforce session protection. `logout.php` successfully destroys the session, leaving `$_SESSION['portal_subject_id']` empty on subsequent requests.

### Root Cause
`PortalBootstrap.php` acts as a dual-purpose interceptor for both API endpoints and browser-facing UI pages. However, its authentication guard logic is hardcoded to emit an `HTTP 401` header and echo a JSON payload on failure, unconditionally terminating execution (`exit;`). It does not differentiate between an API client expecting JSON and a web browser expecting a `302 Redirect`.

### Existing Application Convention
By analyzing the core administrative application, the architectural convention strictly separates API and UI authentication guards:
- UI Pages (`index.php`) utilize a standard session check that emits `header('Location: login.php');`.
- APIs (`backend/core/ApiBootstrap.php`) utilize `ApiResponse::error(...)` to emit JSON.

### Remediation Direction
`PortalBootstrap.php` should be updated to align with the existing convention. It should inspect the request (e.g., checking if `$_SERVER['REQUEST_URI']` contains `/api/`) to determine the context. If it's an API request, it should return the 401 JSON. If it's a browser page request, it should emit `header('Location: index.php');` to redirect the user to the portal login.

## 4. Interaction With Existing Phase 1 Security Controls
- **CSRF**: Remains robust and securely implemented via `FormData` and `$_POST`.
- **Session Isolation**: Remains perfectly functional. Portal and admin sessions do not bleed into each other.
- **Token Expiry**: Handled correctly. OTPs are issued with a strict 10-minute expiry.
- **Token Replay**: Handled correctly. Magic links and correct OTPs are marked used and rejected on subsequent attempts.
- **Action Context**: **Vulnerable**. As noted in the required invariants, `action_context` is currently a client-controlled parameter during verification rather than a securely bound server-side property.
- **Rate Limiting**: Remains functional. The 5-OTP-per-hour issuance limit prevents flooding the user's email.

## 5. Recommended Fix Order
1. **Critical Security**: Fix the `verifyOtp` SQL logic to properly enforce the OTP Attempt Limit and implement `action_context` database binding.
2. **Usability**: Update `PortalBootstrap.php` to correctly route unauthorized browser navigations to the login page via HTTP Redirect.
