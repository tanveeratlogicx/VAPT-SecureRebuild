# Implementation Plan - Workbench Save Feature Fix

## Revision History

- **20260306_@2157**: Initial proposed plan to fix 403 Forbidden error and clean up hotpatches. Approved by user.

## Latest Comments/Suggestions

- User approved the plan at 20260306_@2133.

Address the "Save Failed" (403 Forbidden) error when toggling features in the Workbench and clean up redundant REST API hotpatches.

## User Review Required

> [!IMPORTANT]
> A PHP warning was identified in `vaptsecure.php` where `$vapt_settings` was occasionally undefined during script localization. This leads to missing nonces in the REST API requests, causing 403 Forbidden errors.

## Proposed Changes

### VAPT-Secure Plugin Core

#### [MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)

- Move the initialization of `$vapt_settings` to the top of `vaptsecure_enqueue_admin_assets` to ensure it is always defined before any `wp_localize_script` calls.
- Refactor script enqueuing to be more robust, ensuring dependencies are always registered before use.
- Update the global REST hotpatch to be more resilient against `Headers` objects and case-sensitivity in methods.

#### [MODIFY] [workbench.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/workbench.js)

- Remove the redundant local REST hotpatch, relying on the global one in `vaptsecure.php`.
- Ensure it uses the globally patched `wp.apiFetch`.

#### [MODIFY] [class-vaptsecure-rest.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-rest.php)

- Add more detailed error logging in `check_read_permission` and `update_feature` to help diagnose any future permission issues.
- Ensure 403 responses always return valid JSON instead of allowing WordPress to potentially fallback to HTML.

## Verification Plan

### Automated Tests

- None available for this environment.

### Manual Verification

1. Load the Workbench page and verify no PHP warnings in `debug.log`.
2. Toggle the "Enable Protection" feature on any risk (e.g., XML-RPC Protection).
3. Verify that the request to `vaptsecure/v1/features/update` returns a `200 OK` and the UI shows "Saved".
4. Check the browser console to ensure the "Global REST Hotpatch Active" message is logged.
