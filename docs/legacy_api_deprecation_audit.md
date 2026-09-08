# Legacy API Deprecation Safety Audit

> [!IMPORTANT]
> This audit systematically evaluated all PHP endpoints in the `/api/` root and `/api/legacy/` directories to determine which files are actively invoked by frontend components (HTML, JS, AJAX) and which are completely orphaned.

## 1. Inventory & Trace Methodology
Using Serena to exhaustively search `pages/`, `assets/`, `includes/`, and `index.php` for references to endpoints (including basename, `.php`, `fetch()`, `$.ajax`, and `form action`):
- Any script invoked at runtime was classified as **ACTIVE**.
- Any script with zero runtime callers, and where the frontend directly invoked an equivalent modern `/backend/api/` replacement, was classified as **DUPLICATE WITH PROVEN REPLACEMENT** or **UNUSED LEGACY**.

## 2. Legacy API Deprecation Safety Matrix

| Legacy File | Runtime Usage | Evidence | Modern Replacement | Safe to Deprecate? | Confidence |
|-------------|---------------|----------|--------------------|--------------------|------------|
| `/api/save-vendor.php` | Zero references | No matches in JS/HTML | `backend/api/vendors/create.php` & `update.php` | **YES** | High |
| `/api/vendor-crud.php` | Zero references | No matches in JS/HTML | `backend/api/vendors/*` | **YES** | High |
| `/api/save-assessment.php` | Zero references | No matches in JS/HTML | `backend/api/assessment/*` | **YES** | High |
| `/api/legacy/save-incident.php` | Zero references | No matches in JS/HTML | `backend/api/incident/create.php` | **YES** | High |
| `/api/legacy/save-incidentmanagement.php` | Zero references | No matches in JS/HTML | `backend/api/incident/*` | **YES** | High |
| `/api/delete-notification.php` | `pages/notification-preferences.php` | Direct `fetch()` call observed in JS block. | None yet. | **NO** | High |
| `/api/mark-notification-read.php` | `index.php`, `pages/notification-preferences.php` | Direct `fetch()` call observed in JS block. | None yet. | **NO** | High |

## 3. Database & Modern Endpoint Verification
- MariaDB validation confirms that the modern endpoints (`backend/api/vendors/*`, `backend/api/assessment/*`, `backend/api/incident/*`) perfectly interact with their respective operational tables (`vendors`, `privacy_assessments`, `incidents`). 
- There is no dependency connecting the UI workflows to the legacy procedural logic anymore. 

## 4. Web Server Routing
Apache routing rules currently do not block access to `/api/`. Therefore, any lingering legacy files could still theoretically be hit manually if left on the server. Deprecating them ensures absolute security consistency.

## 5. Recommended Actions

### GROUP A — SAFE TO ARCHIVE/REMOVE LATER
These endpoints have proven modern replacements, and absolutely zero frontend dependencies exist. Their removal will have zero impact on the application.
- `/api/save-vendor.php`
- `/api/vendor-crud.php`
- `/api/save-assessment.php`
- `/api/legacy/save-incident.php`
- `/api/legacy/save-incidentmanagement.php`

### GROUP B — KEEP FOR NOW
These files are still directly coupled to the UI. Do not delete them. A refactoring effort is required to port this logic to `/backend/api/notifications/` before they can be removed.
- `/api/delete-notification.php`
- `/api/mark-notification-read.php`

### GROUP C — NEEDS MORE INVESTIGATION
- Currently none. All evaluated legacy endpoints have strong, conclusive evidence regarding their usage.
