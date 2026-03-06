# Implementation Plan - Removals & UI Cleanup

## Revision History

- **20260306_@1608**: Refined save fix: Identified missing DB columns (`is_adaptive_deployment`, `override_schema`, `override_implementation_data`) in `$schema_map` as likely cause of 400/500 errors.
- **20260306_@1605**: Refined plan: **KEEP "Enable Protection"** toggle; FIX "Save Failed" error; Proceed with removing "Refresh Data" and "SYNC" labels.
- **20260306_@1524**: Initial plan for removing "Enforce Rule" and `is_enforced`.

---

## Latest Improvements & Tasks

### [20260306_@1608] - UI Cleanup and Save Fix

**Objective**: Clean up redundant UI elements ("Refresh Data", "SYNC") while ensuring the core "Enable Protection" toggle works correctly without save failures by fixing missing database mappings.

#### Proposed Changes

##### 1. Database & Schema Alignment

- **File**: `vaptsecure.php`
- **Action**: Update table creation and `vaptsecure_manual_db_fix` to include:
  - `is_adaptive_deployment TINYINT(1) DEFAULT 0`
  - `override_schema LONGTEXT DEFAULT NULL`
  - `override_implementation_data LONGTEXT DEFAULT NULL`
- **File**: `includes/class-vaptsecure-db.php`
- **Action**: Add these 3 columns to `$schema_map` to allow the `replace` query to persist them.

##### 2. Workbench Header

- **File**: `assets/js/workbench.js`
- **Action**: Remove the "Refresh Data" button (labeled `Refresh Data` with icon `update`) next to the `SUPERADMIN` label.

##### 3. Verification UI

- **File**: `assets/js/modules/generated-interface.js`
- **Action**: Remove `SyncAsyncToggle` component (the SYNC button) and its usage in `TestRunnerControl`.

##### 4. REST API Refinement

- **File**: `includes/class-vaptsecure-rest.php`
- **Action**: Remove residual `$is_enforced` parameter extraction.

---

## Previous Tasks

### [20260306_@1524] - Remove "Enforce Rule" & Backend is_enforced (Completed)

- Removed from `workbench.js`
- Dropped DB column in `vaptsecure.php`
- Updated all backend enforcers and rest controllers.
