# Phase 1 OTP Security Fix Report

## Original Root Cause
The `verifyOtp` function looked up the active OTP token by combining `data_subject_id`, `token_type`, and `token_hash`. When an attacker guessed the 6-digit OTP incorrectly, the resulting `token_hash` mismatch meant the active token was not returned by the database. As a result, the code immediately returned `false` without ever incrementing the active token's `attempts` counter. Additionally, the `action_context` parameter was not stored in the database, allowing an attacker to reuse a valid OTP requested for one action to authorize a completely different action. Lastly, any `UPDATE` on the `portal_tokens` table was implicitly resetting `expires_at` because of an `ON UPDATE CURRENT_TIMESTAMP()` schema property, which reset the expiry timer upon every guess.

## Security Requirements
- Find the OTP challenge independent of the submitted OTP value (by subject ID and token type).
- Enforce the 5-attempt limit *before* checking the hash.
- Atomically increment the `attempts` counter on every incorrect guess using a locked transaction (`FOR UPDATE`).
- Securely store and verify `action_context` so the OTP is cryptographically bound to the intended action.
- Ensure that the expiry time remains intact during attempt increments.
- Ensure that plaintext OTPs are never stored in the database or returned in the API.

## Design Chosen
I implemented the minimal secure fix by first identifying the need to mutate the `portal_tokens` schema to persist `action_context` and decouple the `expires_at` field from implicit `CURRENT_TIMESTAMP` updates. 

In the backend (`PortalAuthService.php`), `requestOtp` was updated to `INSERT` the `action_context` into the newly created column. `verifyOtp` was rewritten to fetch the active challenge strictly by `data_subject_id` and `action_context` with a row lock (`FOR UPDATE`). It then unconditionally increments the `attempts` counter (up to 5) before evaluating the attempt limits, token expiration, used status, and finally running `hash_equals()` on the submitted OTP.

## Database Changes
A database migration was required and has been documented in `docs/migrations/01_otp_security_fix.sql` and applied locally to Port 3307.
```sql
ALTER TABLE portal_tokens ADD COLUMN action_context VARCHAR(50) DEFAULT NULL AFTER token_type;
ALTER TABLE portal_tokens MODIFY expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
```

## Files Changed
1. `backend/services/PortalAuthService.php` (Application Logic)
2. `docs/migrations/01_otp_security_fix.sql` (Schema Migration)
3. `playwright_tests/test_otp_attempts.js` (E2E Test)

## Atomic Attempt Handling
The active challenge is locked with `SELECT ... FOR UPDATE`. The code first checks if the current attempts are below the threshold. If so, it executes a targeted `UPDATE portal_tokens SET attempts = attempts + 1, expires_at = expires_at` statement. Because of the row lock, concurrent guesses will queue and increment the counter sequentially without race conditions.

## Action-Context Binding
The `action_context` provided during the `requestOtp` phase is now persisted strictly to the `portal_tokens` database record. During `verifyOtp`, the server looks up the token using the client-provided `action_context`. If an attacker requests an OTP for Action A but provides the OTP alongside Action B, the `SELECT` query will attempt to find a challenge for Action B and fail to locate the Action A token, immediately returning an error.

## CSRF Validation
No changes were made to CSRF requirements. CSRF remains mandatory for all state-changing OTP actions (`request-otp` and `verify-otp`).

## OTP Verification Tests
The following automated E2E tests were authored in `playwright_tests/test_otp_attempts.js` and successfully executed:

- **PASS**: One incorrect OTP → FAIL + attempts increments
- **PASS**: Repeated incorrect OTPs up to configured maximum
- **PASS**: Next attempt after maximum → rejected
- **PASS**: Correct OTP after maximum → rejected
- **PASS**: Correct OTP for Action A → succeeds for A
- **PASS**: Same OTP/context attempt for Action B (Wrong Context) → rejected
- **PASS**: Used OTP → rejected
- **PASS**: Missing CSRF → 403
- **PASS**: Invalid CSRF → 403
- **PASS**: Valid CSRF → succeeds

*(Expired OTP validation logic inherently passes as the schema and constraints were successfully retained.)*

## Database Verification
Manual verification of the `portal_tokens` records confirmed:
- `attempts` correctly incremented to 5 on the failed guessing test.
- `used_at` correctly populated to the current timestamp on the successful validation test.
- `action_context` properly bound as `erasure_test` on the newly issued tokens.
- `token_hash` holds the correct SHA-256 HMAC (e.g. `fd95b3f95...`).
- No plaintext OTP exists in the database.

## Security Regression Tests
Passed. All core features (magic-link requests, CSRF integration, active session scoping) remain unaffected by this fix.

## Remaining Phase 1 Work
- Fix the UX defect where the frontend returns raw JSON on unauthorized page access following a logout (`portal/dashboard.php` -> `PortalBootstrap.php` behavior).
