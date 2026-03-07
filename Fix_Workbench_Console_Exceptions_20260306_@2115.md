# Implementation Plan - Fix Workbench Console Exceptions - 2026/03/06_@2115

This plan addresses browser console exceptions on the VAPT-Secure Workbench page by ensuring all necessary WordPress dependencies are enqueued and that the global REST hotpatch is available on all plugin pages.

## Proposed Changes

### [VAPT-Secure Plugin]

#### [MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)

- Move the **Global REST Hotpatch** logic outside of the specific `domain-admin` block so it applies to all VAPT-Secure plugin pages.
- Add `wp-api-fetch` and `wp-i18n` as dependencies for `vapt-workbench-js` and `vapt-client-js`.
- Add `wp-i18n` as a dependency for `vapt-generated-interface-ui`.
- Ensure `vaptSecureSettings` is consistently localized for all scripts that need it.

#### [MODIFY] [workbench.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/workbench.js)

- Add a safety check for `settings.homeUrl` before calling `.replace()` to prevent `TypeError`.
- Remove redundant `window.vaptSecureSettings || window.vaptSecureSettings` assignment.

## Verification Plan

### Automated Tests

- N/A (UI-based changes in WordPress admin).

### Manual Verification

1. Load `http://hermasnet.local/wp-admin/admin.php?page=vaptsecure-workbench`.
2. Check the browser console for any `TypeErrors` or `ReferenceErrors`.
3. Verify that the features load correctly in the workbench.
4. Verify that toggling a feature works (if applicable in workbench) and doesn't throw errors.
5. Check the "VAPT Secure" status page as well to ensure no regressions.
