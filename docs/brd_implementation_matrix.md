# PrivacyHQ BRD Implementation Matrix

This document tracks the end-to-end implementation status of the PrivacyHQ Business Requirements Document (BRD).

## 1. Consent Lifecycle Management
* **BRD requirement**: End-to-end consent tracking (purposes, categories, capture, withdrawal, history, audit trail).
* **Current implementation**: Existing `Consent.php`, `ConsentHistory.php`, `ConsentPurpose.php` models exist. Basic creation API exists. UI relies on real database persistence, removing fake success behavior.
* **Status**: COMPLETE
* **Required work**:
  - Connect UI to real database persistence.
  - Remove fake success behaviors.
  - Implement consent withdrawal logic.
  - Complete history and audit trail logic.
  - Validate search/filtering.
* **Priority**: High
* **Acceptance criteria**: Real consent can be created, viewed, withdrawn, and audited end-to-end.

## 2. Data Principal / DSR
* **BRD requirement**: Full DSR workflow (identity verification, request, assignment, processing, closure).
* **Current implementation**: Portal Phase 1 exists. `DataRequest.php` and `RequestHistory.php` exist. Portal UI extended to allow DSR submission and tracking. Admin DSR dashboard processes requests natively.
* **Status**: COMPLETE
* **Required work**:
  - Connect portal UI to real DSR API. (Done)
  - Implement full status tracking. (Done)
  - Admin view, assignment, status update, processing, and closure API. (Done)
  - Maintain robust audit trail. (Done)
* **Priority**: High
* **Acceptance criteria**: Create a DSR from portal → process in admin → close → visible in audit/history. (Done)

## 3. Cookie Consent
* **BRD requirement**: Cookie inventory, categorization, and manual/import discovery. Preference management.
* **Current implementation**: Mocked/fake cookie discovery behavior in UI. `CookieGovernance.php` model exists.
* **Status**: MOCKED
* **Required work**:
  - Replace fake scanner with reliable manual/import-based inventory.
  - Implement cookie tracking attributes (name, domain, provider, duration, etc.).
  - Implement necessary vs marketing categories.
  - Consent capture, history, and audit.
* **Priority**: Medium
* **Acceptance criteria**: Working cookie inventory and manual management without fake automated scanning.

## 4. PIA / Privacy Assessment
* **BRD requirement**: Complete assessment lifecycle (Draft → Review → Completed), secure evidence, risk rating.
* **Current implementation**: SEC-02 remediated evidence upload. Templates, questions, responses mostly exist.
* **Status**: PARTIAL
* **Required work**:
  - Implement findings, remediation, due dates, ownership, risk severity.
  - Solidify review and approval workflows.
* **Priority**: Medium
* **Acceptance criteria**: Complete assessment can be created, performed, submitted, reviewed, approved, and reported.

## 5. TPRM (Vendor Risk)
* **BRD requirement**: Vendor lifecycle (Onboarding → Assessment → Approval → Reassessment).
* **Current implementation**: Models `Vendor.php`, `VendorAssessment.php`, `VendorRisk.php` exist. Basic UI exists.
* **Status**: PARTIAL
* **Required work**:
  - Implement complete questionnaire system for vendors.
  - Implement inherent vs residual risk calculation and transparent scoring model.
  - Remediation, approval, monitoring loops.
* **Priority**: Medium
* **Acceptance criteria**: Create vendor → questionnaire → score → review → approve → monitor → reassess.

## 6. Incident Management
* **BRD requirement**: Full incident lifecycle (Detection → Remediation → Closure).
* **Current implementation**: `Incident.php` model exists. Minimal endpoints.
* **Status**: PARTIAL
* **Required work**:
  - Affected data/subjects mapping.
  - Timeline tracking, actions, findings, remediation.
  - Notification tracking.
* **Priority**: Medium
* **Acceptance criteria**: Create incident → investigate → remediate → close.

## 7. GRC Core
* **BRD requirement**: Risk Register, Policy Management, Issues/Remediation, Audit logic.
* **Current implementation**: Models `RiskRegister.php`, `Policy.php` exist, but lacking broad lifecycle capabilities.
* **Status**: MISSING
* **Required work**:
  - Build out Risk Register (inherent/residual scores, controls, treatment).
  - Policy Management (versions, approval, effective date).
  - Issues/remediation and audit scopes.
* **Priority**: High
* **Acceptance criteria**: Fully functional CRUD and workflow for risks, policies, and issues.

## 8. Compliance Automation
* **BRD requirement**: Framework-driven compliance model (GDPR, SOC 2, etc.) assessing percentage of compliance.
* **Current implementation**: Not implemented.
* **Status**: MISSING
* **Required work**:
  - Build framework catalogue architecture (Framework → Requirements → Controls → Evidence).
  - Calculate compliance score percentages.
* **Priority**: High
* **Acceptance criteria**: System can answer "What percentage of this framework is currently compliant?".

## 9. Data Governance / DSPM MVP
* **BRD requirement**: Practical MVP for data source inventory, classification, sensitivity, and risk.
* **Current implementation**: `DataDiscovery.php`, `DataMapping.php` exist. UI mocked.
* **Status**: PARTIAL
* **Required work**:
  - Implement manual inventory of data assets and mapping.
  - Integrate with RoPA.
  - Owners, sensitivity, risk tracking.
* **Priority**: Medium
* **Acceptance criteria**: Working manual data asset inventory tracking and RoPA integration.

## 10. Dashboard
* **BRD requirement**: Real metrics calculated from DB (Compliance, Risk Score, Open Assessments, etc.).
* **Current implementation**: Mocked hardcoded values in frontend.
* **Status**: MOCKED
* **Required work**:
  - Build backend endpoints calculating true aggregates from the database.
  - Update UI to consume live data.
* **Priority**: Low
* **Acceptance criteria**: Dashboard displays accurate metrics with no hardcoded stats.

## 11. Audit & System Controls
* **BRD requirement**: System-wide audit tracking for all critical actions.
* **Current implementation**: `AuditLog.php` and `log_audit_event()` helper exist.
* **Status**: PARTIAL
* **Required work**:
  - Guarantee all modules (DSR, consent, incidents, policies) call audit logger accurately.
* **Priority**: Ongoing
* **Acceptance criteria**: Complete audit trails for every state change.

## 12. Frontend / UX
* **BRD requirement**: Functional frontend matching backend logic without fakes.
* **Current implementation**: Some buttons broken, mocked success states.
* **Status**: PARTIAL
* **Required work**:
  - Connect UI to real APIs.
  - Consistent loading/error states.
* **Priority**: Ongoing
* **Acceptance criteria**: No mocked UI elements for completed modules.
