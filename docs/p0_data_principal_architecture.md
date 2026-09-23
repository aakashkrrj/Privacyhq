# P0 — Data Principal Self-Service Architecture

## 1. Current Architecture Findings

### Authentication & Session Model
The admin portal uses PHP session-based authentication ([login.php](file:///d:/New%20folder/governance/login.php)). Users are looked up from the `users` table, password-verified via `password_verify()`, and session variables (`user_id`, `role_id`, `permissions`) are populated. All API endpoints are gated by [ApiBootstrap.php](file:///d:/New%20folder/governance/backend/core/ApiBootstrap.php), which rejects any request without a valid `$_SESSION['user_id']` (line 14) and enforces CSRF on all state-changing methods (line 21).

### RBAC Model
Permissions are loaded from `role_permissions` → `permissions` at login. The `has_permission()` function in [db.php](file:///d:/New%20folder/governance/backend/config/db.php#L58-L68) grants Super Admin (role_id=1) blanket access. All 6 existing roles are internal staff roles. There is **no** Data Principal role.

### Consent Architecture
- **`data_subjects`** — The identity anchor. Has `identifier_hash` (currently stores email), `email`, `name`, `phone`, `department`, `type` (customer/employee/vendor_contact/citizen), `status` (active/erased/frozen).
- **`consent_purposes`** — 11 active purposes, some marked `is_essential`. Has `retention_days`.
- **`consents`** — Links `data_subject_id` → `consent_purpose_id` with `status` (opt_in/opt_out/withdrawn/expired), `source`, `collection_method`, `ip_address`, `user_agent`, `expires_at`.
- **`consent_history`** — Immutable change log: `consent_id`, `previous_status`, `new_status`, `changed_by`, `reason`, `changed_at`.
- **ConsentService** — Full transactional create/revoke/updatePreference with audit logging.

### DSR Architecture
- **`data_requests`** — Links to `data_subject_id`. Has `request_id_code` (e.g. `DSR-A1B2C3D4`), `request_type` (access/erasure/rectification/portability/objection), `status` (open/assigned/verifying/processing/waiting/completed/rejected/cancelled/expired), `verification_status` (pending/verified/failed), `priority`, `assigned_to`, `due_date`, `progress_percentage`, `resolved_at`.
- **`request_history`** — Immutable timeline of status transitions.
- **`dsr_notes`** — Has `is_public` flag for internal vs. subject-visible notes.
- **`dsr_attachments`** — File attachments with path/size/type.
- **DsrService** — Full lifecycle: create, verify, assign, changeStatus, addNote, uploadAttachment, with WorkflowService dispatch and notification creation.

### Key Observations
1. `data_subjects.identifier_hash` is currently just the raw email, **not** a cryptographic hash. The column name suggests the original intent was pseudonymization.
2. `consent_history.changed_by` references `users.id` (admin users), not data subjects.
3. `dsr_notes.is_public` already distinguishes internal/external-facing notes — designed for a future portal.
4. `data_requests.verification_status` already exists, indicating identity verification was planned from the start.
5. The `WorkflowService` dispatches events (`dsr.created`, `dsr.verified`, `dsr.completed`) which could trigger Data Principal notifications.

---

## 2. Existing Components That Can Be Reused

| Component | Reusable? | How |
|---|---|---|
| `data_subjects` table | ✅ Yes | Already the identity anchor for both consent and DSR |
| `consents` table | ✅ Yes | Already links subject → purpose with status tracking |
| `consent_history` table | ✅ Yes | Immutable audit trail; `changed_by` is already nullable |
| `consent_purposes` table | ✅ Yes | Defines available purposes; `is_essential` can prevent withdrawal |
| `data_requests` table | ✅ Yes | Already has `verification_status`, `request_id_code` for tracking |
| `request_history` table | ✅ Yes | Status change timeline |
| `dsr_notes.is_public` | ✅ Yes | Already designed for portal visibility |
| `ConsentService` | ✅ Partially | `revokeConsent()` and `updatePreference()` can be called from portal service layer |
| `DsrService.createRequest()` | ✅ Partially | Core logic reusable; needs wrapper that doesn't require admin `userId` |
| `audit_logs` table | ✅ Yes | `user_id` is nullable; can log portal actions with `NULL` |
| CSRF infrastructure | ✅ Yes | Session-based CSRF tokens work for browser-based portal |
| `WorkflowService` | ✅ Yes | Can dispatch portal events for admin notification |
| Notification system | ✅ Yes | Can notify admins of portal-submitted DSRs |

---

## 3. Existing Limitations

1. **No Data Principal authentication mechanism.** The `users` table is strictly for internal staff. There is no login path for external subjects.
2. **`consent_history.changed_by` references `users.id`.** A portal-initiated change has no valid `users.id` to record. The column is already nullable, so `NULL` can represent self-service, provided we augment the audit log.
3. **No token/OTP infrastructure.** No table exists for verification tokens, OTPs, or magic links.
4. **API layer assumes admin session.** `ApiBootstrap.php` hard-rejects any request without `$_SESSION['user_id']` before any controller logic runs. The portal cannot reuse admin API endpoints.
5. **No rate limiting infrastructure.** No existing mechanism throttles requests by IP or identity.
6. **`data_requests.request_type` enum** lacks `consent_withdrawal` and `grievance`. Currently: `access`, `erasure`, `rectification`, `portability`, `objection`.

---

## 4. Data Principal Authentication Options

### Option A: Dedicated Data Principal Accounts
Create accounts in `users` table (or a parallel `portal_users` table) with email/password.

| Criterion | Assessment |
|---|---|
| Security | Strong (password-based) |
| Usability | Poor for one-time users; requires registration |
| Account management | High overhead; password resets, forgotten accounts |
| Privacy | Stores additional PII (password hashes) |
| Scalability | 50M subjects × credentials = significant table |
| DPDP alignment | DPDP does not require account creation |

### Option B: One-Time Secure Verification Tokens (Magic Links)
Email a time-limited, hashed token link. Token grants a scoped session.

| Criterion | Assessment |
|---|---|
| Security | Good (email as proof of identity; token hashed in DB) |
| Usability | Excellent; no registration, no passwords |
| Account management | Zero; no accounts to manage |
| Privacy | Minimal PII storage (just the existing email) |
| Token leakage risk | Mitigated by expiration, one-time use, and HTTPS |
| Scalability | Excellent; tokens are ephemeral |
| DPDP alignment | Strong; mirrors regulatory workflows |

### Option C: Separate Portal Authentication (Username/Password)
A dedicated `portal_users` table with full registration flow.

| Criterion | Assessment |
|---|---|
| Security | Strong |
| Usability | Moderate; requires registration barrier |
| Account management | Medium; separate credential store |
| Complexity | High; duplicate auth system |

### Option D: Hybrid (Magic Link + Step-Up Verification)
Magic link for initial access, optional SMS/email OTP for high-risk actions (erasure, sensitive access).

| Criterion | Assessment |
|---|---|
| Security | Strongest; step-up verification for sensitive actions |
| Usability | Good; low friction for viewing, elevated for mutations |
| DPDP alignment | Strongest; identity verification is explicit |

---

## 5. Recommended Authentication Architecture

> [!IMPORTANT]
> **Recommendation: Option D — Hybrid (Magic Link + Step-Up Verification for High-Risk Actions)**

**Rationale:**
1. **No registration barrier.** Data Principals should not need to create an account to exercise their rights. This aligns with DPDP Section 11 and GDPR Article 12.
2. **Email is the existing identity anchor.** `data_subjects.email` is already the key lookup field used by both ConsentService and DsrService.
3. **Magic link provides strong email-based verified ownership** with minimal friction.
4. **OTP adds step-up verification** for destructive/sensitive operations (erasure, sensitive data access). 
5. **No parallel user store** reduces attack surface and complexity.

### The Identity-Verification Model
The system makes a strict terminology and policy distinction:
- **Verified Email Ownership (Magic Link):** Confirms the user has control of the email address associated with the data-subject record. It does **NOT** by itself prove the person's legal or physical identity. Sufficient for low-risk actions.
- **Step-Up Verification (OTP):** An OTP delivered through the same email channel acts as a fresh verification of active possession/control of the email channel. It is **NOT** true independent MFA. It confirms intent and presence immediately before a sensitive action.
- **Strong/Physical Identity Verification:** If stronger identity verification is required (e.g., verifying a government ID before exporting all data), it remains **outside the automated portal flow** and must use an approved stronger verification process (tracked via `data_requests.verification_status`).

#### Recommended Step-Up Verification Policy
The step-up policy should be configurable by action risk rather than hardcoded. The recommended initial policy is:
- **Viewing consent:** Verified Email Ownership
- **Modifying consent preference:** Step-Up Verification
- **Withdrawing consent:** Step-Up Verification
- **DSR Submission (General):** Verified Email Ownership
- **DSR Submission (Erasure):** Step-Up Verification
- **Sensitive data access/download:** Step-Up Verification

### Magic Link Flow
```
Data Principal enters email on portal
→ System looks up data_subjects WHERE email = ?
→ If found → generate token, store hash in portal_tokens, email link
→ If NOT found → generic "If this email exists, a link has been sent" (prevents enumeration)
→ Data Principal clicks link
→ System validates token hash, checks expiration, marks as used
→ Regenerates session ID, creates scoped portal session
→ Session is bound to data_subject_id
→ Clean redirect to portal dashboard (strips token from URL)
```

### OTP Step-Up Escalation
```
Data Principal initiates sensitive action (e.g., erasure)
→ System checks step-up policy for action
→ System generates 6-digit OTP, stores securely in portal_tokens (using HMAC)
→ Sends OTP to verified email
→ Data Principal enters OTP
→ System verifies OTP according to strict limit rules
→ Validates, sets short-lived step-up flag in session, proceeds with action
```

### Magic Link & Token Security Specification
To ensure tokens are secure and prevent leakage:
- **Generation:** Magic link tokens are `bin2hex(random_bytes(32))` (64 hex chars). OTPs are 6-digit numeric strings.
- **Storage Strategy:** 
  - Magic link tokens have high entropy; plain `hash('sha256', $token)` is sufficient.
  - OTPs (6 digits) have extremely low entropy. Therefore, OTP verification should preferably use a server-secret keyed mechanism such as HMAC rather than relying only on plain SHA-256 hashing.
  - Raw tokens/OTPs must never be logged in application logs.
- **Consumption:** Immediate token consumption on first successful use.
- **Session:** `session_regenerate_id(true)` is called immediately upon successful verification to prevent session fixation.
- **Redirect:** The application must perform a clean HTTP redirect after verification so the token is removed from the browser's address bar and history.
- **Referrer-Policy:** The portal must enforce `Referrer-Policy: no-referrer` to ensure tokens in URLs (before redirect) are not leaked to external sites via referer headers.
- **Analytics:** No analytics tracking (e.g., Google Analytics) on the verification URL.
- **Expiration:** Magic link: 15 minutes. OTP: 10 minutes.

### Rate-Limiting & Attempt Enforcement
It is critical to distinguish between **token issuance rate limiting** and **OTP verification-attempt limiting**.

#### 1. OTP Verification-Attempt Limiting
The schema includes `portal_tokens.attempts INT UNSIGNED NOT NULL DEFAULT 0`. However, the column alone does **NOT** enforce the security rule. The implementation MUST programmatically enforce the following:
- Reject expired tokens.
- Reject already-used tokens (`used_at IS NOT NULL`).
- Reject tokens where `attempts >= 5`.
- Increment the `attempts` counter atomically.
- Prevent concurrent requests from bypassing the 5-attempt limit (via row locks or transactional boundaries).
- Consume the token (`used_at = NOW()`) after successful verification.
- **Never** reset the attempts counter during verification.

#### 2. Token Issuance Rate Limiting
Issuance limits must be strictly scoped by `token_type` and subject/IP where applicable. Do **not** imply that all `portal_tokens` rows count toward every rate limit.
- **Magic-link issuance limit:** Maximum 3 per data subject/email per hour. (Counting rows MUST include `token_type='magic_link'`).
- **IP-based issuance limit:** Maximum 10 requests per IP per hour.
- **OTP issuance limit:** OTP issuance must have its own explicitly defined limit (e.g., maximum 5 OTPs per action session).

---

## 6. Consent Preference Center Architecture

### Conceptual Flow
```
Portal Login (magic link)
  ↓
Preference Center Dashboard
  ↓
├─ View all consent purposes
│   ├─ Purpose name, description
│   ├─ Current status (opt_in / opt_out / withdrawn)
│   ├─ Granted date
│   └─ Expiry date
│
├─ Modify preference (per purpose)
│   ├─ Toggle opt_in ↔ opt_out
│   ├─ Requires reason text
│   └─ Step-up verification required
│
├─ Withdraw consent (per purpose)
│   ├─ Step-up verification required
│   ├─ Records reason
│   └─ Cannot withdraw essential purposes (is_essential = 1)
│
├─ View consent history (timeline)
│   └─ All changes for this subject's consents
│
└─ Download consent records
    └─ JSON/PDF export of all consents + history (Step-up verification required)
```

### Public Identifiers vs. Internal Database IDs
The portal must use consistent identifier exposure and strict ownership validation:
- **DSR Tracking:** Must use the opaque `request_id_code` (e.g. `DSR-XXXXXXXX`) for all URLs and API paths (never the raw database ID).
- **Consent Operations:** May use an opaque/public identifier **OR** an internal consent ID, but **only if** the implementation always performs strict ownership validation on every request.
- **Security Guarantee:** Authorization must **NEVER** depend on an ID being secret.
- **Enforcement:** Every consent lookup/mutation must verify that the requested record belongs to `$_SESSION['portal_subject_id']`.
- **Constraint:** Never accept `data_subject_id` from the client; it must always be derived exclusively from the session context.

### Can Existing Schema Support This?

**Yes, with no schema changes.**

The existing `consents` + `consent_history` + `consent_purposes` schema already supports:
- Listing consents per subject (`WHERE data_subject_id = ?`)
- Status tracking per purpose
- Immutable history with `previous_status`, `new_status`, `reason`
- Essential purpose marking (`is_essential`)

**Limitation on `consent_history.changed_by`:** The column references `users.id` (internal admin users).
**Solution:** The column is nullable. We will use the convention `changed_by = NULL` to represent portal-initiated (self-service) changes. However, to maintain a strict audit trail, the `audit_logs` table (which also has a nullable `user_id`) must explicitly record the Data Principal identity. We will store `{"data_subject_id": <ID>}` in the `new_value` or `old_value` JSON payload of the audit log when `user_id` is `NULL`. Do NOT create a synthetic "portal admin" user in the `users` table.

---

## 7. DSR Self-Service Architecture

### Request Type Mapping & Dependency Analysis

| BRD Requirement | Existing `request_type` Enum | Gap |
|---|---|---|
| Access | `access` | ✅ Exists |
| Correction | `rectification` | ✅ Exists |
| Erasure | `erasure` | ✅ Exists |
| Consent Withdrawal | — | ⚪ MISSING from enum |
| Grievance | — | ⚪ MISSING from enum |

> [!WARNING]
> **Schema change required:** The `data_requests.request_type` enum must be expanded to include `consent_withdrawal` and `grievance` to satisfy the BRD.
> 
> **Repository-wide Dependency Analysis:** A read-only analysis confirms that `request_type` is used across the UI, controllers (`DsrController`), models (`DataRequest`), exports, and JS files (`dsr-management.js`, `my-tasks.js`). The values are treated dynamically as strings for display, mapping, and filtering.

### DSR Portal Workflow

```
Portal Session (authenticated data_subject_id)
  ↓
Select Request Type (access/correction/erasure/consent_withdrawal/grievance)
  ↓
Submit Request Form
  ├─ Description (required)
  ├─ Optional file attachment
  └─ Step-up verification (required for erasure)
  ↓
System creates data_request
  ├─ status = 'open'
  ├─ verification_status = 'pending' (if manual ID check needed) or 'verified'
  ├─ created_by = NULL (portal-initiated)
  ├─ data_subject_id = session subject
  ├─ request_id_code = auto-generated (DSR-XXXXXXXX)
  └─ due_date = +30 days (DPDP timeline)
  ↓
WorkflowService::dispatch('dsr.portal_submitted', [...])
  ↓
Admin notification created
  ↓
Admin processes internally (existing DsrController flow)
  ↓
Data Principal can track via portal:
  ├─ View status (open → assigned → processing → completed)
  ├─ View public notes (dsr_notes WHERE is_public = 1)
  └─ View history (request_history timeline)
```

### Existing DSR Reuse
The internal `DsrService::createRequest()` accepts `$userId` (admin who created it). For portal submissions, we create a thin `PortalDsrService` that:
1. Calls `DataSubject::findByEmail()` to get subject ID (already verified via magic link)
2. Calls `DataRequest::create()` directly with `createdBy = NULL`
3. Calls `RequestHistory::insert()` with `changed_by` as NULL
4. Dispatches `WorkflowService` event
5. Creates admin notification

This avoids modifying the existing `DsrService` or `DsrController`.

---

## 8. Data Ownership & Authorization Model

### Identity Chain
```
Portal Session
  → $_SESSION['portal_subject_id'] = data_subjects.id
  → Every query: WHERE data_subject_id = ?
```

### IDOR Prevention
Every portal API endpoint enforces ownership at the query level:

```php
// Consent listing — ownership is structural
SELECT c.* FROM consents c WHERE c.data_subject_id = :session_subject_id

// DSR listing — ownership is structural
SELECT dr.* FROM data_requests dr WHERE dr.data_subject_id = :session_subject_id AND dr.deleted_at IS NULL

// DSR detail — uses opaque code and double-checks ownership
$request = DataRequest::findByRequestCode($request_id_code);
if ($request['data_subject_id'] !== $_SESSION['portal_subject_id']) {
    → 403 Forbidden
}
```

---

## 9. API Architecture

### Separation: Internal vs. Portal

```
backend/api/consent/        ← INTERNAL (admin, requires users session)
backend/api/dsr/            ← INTERNAL (admin, requires users session)

backend/api/portal/         ← NEW (Data Principal, requires portal session)
  ├── auth/
  │   ├── request-link.php     POST — Request magic link
  │   ├── verify-token.php     GET  — Verify magic link token
  │   ├── request-otp.php      POST — Request OTP for sensitive action
  │   ├── verify-otp.php       POST — Verify OTP
  │   └── logout.php           POST — Destroy portal session
  ├── consent/
  │   ├── list.php             GET  — List subject's consents
  │   ├── history.php          GET  — Consent change history
  │   ├── update.php           POST — Modify preference (requires OTP)
  │   ├── withdraw.php         POST — Withdraw consent (requires OTP)
  │   └── download.php         GET  — Download consent records
  └── dsr/
      ├── submit.php           POST — Submit new DSR
      ├── list.php             GET  — List subject's DSRs
      ├── details.php          GET  — DSR details + public notes (via request_id_code)
      └── history.php          GET  — DSR status history (via request_id_code)
```

### Portal Bootstrap (NEW: `backend/api/portal/PortalBootstrap.php`)
Unlike `ApiBootstrap.php`, this checks for `$_SESSION['portal_subject_id']` instead of `$_SESSION['user_id']`. It does **not** load admin permissions.

```
Session configuration
→ session_name('privacyhq_portal') // Separate session cookie
→ session_start()
→ Check $_SESSION['portal_subject_id'] exists and is valid
→ CSRF enforcement on POST/PUT/PATCH/DELETE
→ Rate limiting check
→ Proceed to controller
```

### Endpoint Specifications

#### `POST /portal/api/auth/request-link.php`
| Field | Value |
|---|---|
| Authentication | None (public) |
| Rate limit | 3 requests per email per hour; 10 per IP per hour (scoped to `token_type='magic_link'`) |
| CSRF | Not required (no session yet) |
| Request | `{ email: string }` |
| Response | `{ success: true, message: "If this email exists..." }` (always 200) |
| Audit | Log attempt with IP, email hash |

#### `GET /portal/api/auth/verify-token.php?token=XXX`
| Field | Value |
|---|---|
| Authentication | Token in URL |
| Rate limit | None (Tokens inherently one-time) |
| CSRF | Not required (GET, establishes session) |
| Response | Clean redirect to portal dashboard |
| Audit | Log verification success/failure with IP |

#### `GET /portal/api/dsr/details.php?request_id_code=XXX`
| Field | Value |
|---|---|
| Authentication | Portal session |
| Authorization | `data_subject_id` from session verified against record |
| Output | DSR status and public notes |

*(Other endpoints omitted for brevity, but all strictly adhere to ownership and step-up rules)*

---

## 10. Database Impact Analysis

### Proposed Database Changes

#### MANDATORY

**1. New table: `portal_tokens`**

```sql
CREATE TABLE portal_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_subject_id BIGINT UNSIGNED NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    token_type ENUM('magic_link', 'otp') NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL DEFAULT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (data_subject_id) REFERENCES data_subjects(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_subject_type (data_subject_id, token_type)
) ENGINE=InnoDB;
```

**2. ALTER `data_requests.request_type` enum**

```sql
ALTER TABLE data_requests
MODIFY COLUMN request_type ENUM(
'access',
'erasure',
'rectification',
'portability',
'objection',
'consent_withdrawal',
'grievance'
) NOT NULL;
```

**Migration Context & Safety:** 
This `ALTER TABLE` statement is a schema-altering operation. While appending new values to the end of an `ENUM` list is generally a metadata-only operation in MariaDB:
- Existing enum values remain valid.
- New values are appended.
- Existing data is expected to remain valid.
- **Before execution**, the actual MariaDB version and table definition must be verified in the production environment.
- The migration must be executed as a controlled database change after human approval.
- **NO `ALTER TABLE` SHOULD BE EXECUTED DURING THIS DOCUMENTATION PASS.**

---

## 11. Security Architecture

### PHP Session Isolation
The current application config (`backend/config/db.php`) executes a simple `session_start()`, which defaults to the cookie name `PHPSESSID`. 
To guarantee true isolation, **the portal will use a genuinely separate session cookie.** 
In `PortalBootstrap.php`:
```php
session_name('privacyhq_portal');
session_start();
```
This guarantees:
- Portal APIs cannot authenticate using admin `user_id`.
- Admin APIs cannot authenticate using `portal_subject_id`.
- Complete immunity from privilege crossover within the same browser.
- Clean and separate logout procedures.

### CSRF
The portal utilizes the exact same, robust CSRF implementation as the admin backend. The `verify_csrf_token()` function in `db.php` relies on `$_SESSION['csrf_token']`, which is initialized universally for all sessions (including our separate `privacyhq_portal` session). By using `$_SESSION['csrf_token']` consistently in the portal, we ensure maximum security without weakening or duplicating the existing CSRF protection mechanism.

---

## 12. FINAL IMPLEMENTATION READINESS

| Decision | Status | Evidence | Human Approval Required |
|---|---|---|---|
| **OTP attempt limiting** | APPROVED WITH CONDITION | The `attempts` column is present, but programmatic enforcement (reject >=5, atomic increment, prevent bypass, consume token) must be rigorously implemented. | No |
| **Rate limiting (Issuance)** | APPROVED WITH CONDITION | Limits must be explicitly scoped by `token_type` and identifier (e.g. counting rows for `magic_link` vs `otp`). | No |
| **Consent/public identifiers** | APPROVED WITH CONDITION | DSR tracking uses `request_id_code`. Consent IDs may be internal only if strict `data_subject_id` ownership checks are applied universally. | No |
| **Session isolation** | APPROVED WITH CONDITION | `session_name('privacyhq_portal')` ensures complete separation of session cookies from the admin system. | No |
| **CSRF** | APPROVED | Reusing `$_SESSION['csrf_token']` preserves admin security and avoids duplicate logic. | No |
| **IDOR protection** | APPROVED | Absolute structural enforcement (`WHERE data_subject_id = ?`) derived strictly from the session context. | No |
| **Consent history audit** | APPROVED | `changed_by = NULL` in `consent_history` paired with subject ID mapped into `audit_logs`. | No |
| **DSR enum migration** | NEEDS DECISION | Safe to append in theory, but requires controlled environment verification and human approval prior to execution. | Yes |
| **Authentication** (Hybrid Magic Link + OTP) | NEEDS DECISION | Avoids parallel user store & passwords, establishing a verifiable email ownership flow. | Yes |
| **Step-up policy** | NEEDS DECISION | Risk-based application of OTP for high-risk actions (Erasure, Consent modifications, etc). | Yes |

## 13. FINAL IMPLEMENTATION RULE

**No implementation work begins until the human-approval items marked NEEDS DECISION are explicitly approved.**
