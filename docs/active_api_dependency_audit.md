# Active API Dependency Audit

> [!IMPORTANT]
> This audit was performed by tracing the actual JavaScript controllers executing in the browser back to their respective PHP APIs, supplemented with codebase references. This establishes a definitive "source of truth" regarding which endpoints are currently active.

## 1. Executive Summary
The application has successfully migrated its frontend workflows to the modern `backend/api/` architecture. The older procedural endpoints located in `/api/` (such as `save-vendor.php`, `save-assessment.php`, and `/legacy/save-incident.php`) are completely orphaned. They are not invoked by any frontend JavaScript and can be safely marked for deprecation. 

Security controls (CSRF, RBAC) are actively enforced on the modern endpoints. 

## 2. API Layer Inventory
| API Directory | Typical Workflow | Status |
| :--- | :--- | :--- |
| `/backend/api/` | AJAX CRUD operations via JS controllers | **ACTIVE** |
| `/api/` | Old monolithic procedural handlers | **UNUSED LEGACY** (Mostly) |
| `/api/legacy/` | Broken/Deprecated incident management | **UNUSED LEGACY** |

## 3. Frontend → API Dependency Map
| Module | Page | JS Controller | Active Backend API |
| :--- | :--- | :--- | :--- |
| **Vendor Risk** | `vendor-management.php` | `assets/js/vendor-management.js` | `backend/api/vendors/*` |
| **Assessments** | `assessments.php` | `assets/js/assessments.js` | `backend/api/assessment/*` |
| **Cookie Gov.** | `cookie-governance.php` | `assets/js/cookie-governance.js` | `backend/api/cookie-governance/*` |
| **Incidents** | `incident-management.php` | `assets/js/incident-management.js` | `backend/api/incident/*` |

## 4. Vendor API findings
- **Frontend Caller**: `assets/js/vendor-management.js` uses `fetch('backend/api/vendors/create.php')` and `update.php`.
- **Status of `/api/save-vendor.php`**: **UNUSED LEGACY**.
- **Status of `/api/vendor-crud.php`**: **UNUSED LEGACY**.
- **Database Tables Touched**: `vendors`, `vendor_assessments`.
- **Security Check**: CSRF tokens are actively appended to `FormData` (`csrf_token`) in the JS before submitting.

## 5. Assessment API findings
- **Frontend Caller**: `assets/js/assessments.js` triggers `backend/api/assessment/create.php` and `/update.php`.
- **Status of `/api/save-assessment.php`**: **UNUSED LEGACY**.
- **Database Tables Touched**: `privacy_assessments`, `assessment_history`.
- **Workflow Verified**: The JS handles dynamic form submissions via AJAX without page reloads.

## 6. Cookie Governance findings
- **Frontend Caller**: `assets/js/cookie-governance.js`.
- **Scan Workflow**: The "Start Scan" button invokes `backend/api/cookie-governance/scanner.php` with `action=start` via `controlScan()`. It polls for status via `action=status`.
- **Banner Customization**: Posts directly to `backend/api/cookie-governance/banner.php`.
- **Status**: The backend APIs exist and are perfectly wired to the frontend.
- **Database Tables Touched**: `cookie_scan_runs`, `cookie_inventory`, `cookie_categories`.

## 7. Incident findings
- **Frontend Caller**: `assets/js/incident-management.js`.
- **Status of `/api/legacy/save-incident.php`**: **UNUSED LEGACY**. The frontend explicitly calls `backend/api/incident/create.php` and `/timeline.php`.
- **Database Tables Touched**: `incidents`.

## 8. Consent findings
- **Frontend Caller**: `assets/js/cookie-governance.js` and `assets/js/consent-management.js`.
- **Active Endpoint**: `backend/api/cookie-governance/consent.php`.
- **Status**: **ACTIVE**.
- **Database Tables Touched**: `consents`.

## 9. ROPA findings
- **Frontend Caller**: `assets/js/ropa.js`.
- **Active Endpoint**: `backend/api/ropa/create.php`, `/update.php`, `/delete.php`.
- **Status**: **ACTIVE**.
- **Database Tables Touched**: `processing_activities`.

## 10. Security Consistency Findings
Based on the trace of the ACTIVE `backend/api/` layer:
- **CSRF Check**: **Enforced**. `G_CSRF_TOKEN` is injected into every JS payload.
- **RBAC**: **Enforced**. `has_permission()` is utilized.
- **Input Validation**: Handled by the backend controllers.
- **Prepared Statements**: PDO used in modern services.

## 11. Database Correlation
- **Database**: `privacyhq` (Host 127.0.0.1)
- The active API endpoints perfectly map to the 59 tables returned by `information_schema.tables` during MariaDB verification.

## 12. Legacy Endpoint Candidates (For Future Deprecation)
All procedural files in the root `/api/` directory that have functional equivalents in `/backend/api/`.
- `/api/save-vendor.php`
- `/api/vendor-crud.php`
- `/api/save-assessment.php`
- `/api/legacy/save-incident.php`
- `/api/legacy/save-incidentmanagement.php`

## 13. Endpoints That MUST NOT Be Deleted
- Anything under `/backend/api/` as these are the actively running controllers serving the frontend UI.
- Core logic in `/backend/core/` (e.g., `ApiBootstrap.php`).

## 14. Endpoints Safe to Consider for Later Deprecation
- Refer to Section 12. These endpoints should be safely removed in a future architecture cleanup phase.

## 15. Remaining Uncertainties
- While the major modules (Vendor, Assessment, Cookie, Incident) are cleanly migrated to `/backend/api/`, minor utility files (like `delete-notification.php` in the root `/api/` folder) might still be in use if their UI components haven't been refactored yet. A secondary pass to consolidate notifications and dashboard tasks into `/backend/` is recommended.
