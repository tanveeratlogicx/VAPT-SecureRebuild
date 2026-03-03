# Separating Workbench into workbench.js

This plan outlines the steps required to separate the Superadmin Workbench functionality into a dedicated `workbench.js` file, ensuring `client.js` is only used for the WordPress Admin User dashboard.

## User Review Required

Please review this plan. This will result in copying the existing `client.js` into a new `workbench.js` and configuring WordPress to load the correct script based on the specific page.

### 20260304_@0120

**Review:** Does this approach of cloning `client.js` into `workbench.js` and splitting the enqueue logic in `vaptsecure.php` align with your expectations?

## Proposed Changes

---

### File Modifications

#### [NEW] [workbench.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/workbench.js)

1. Determine the path to the original `client.js`.
2. Copy the content of `client.js` into this new file, as it currently powers both pages.
3. Update the console log in this new file to say `VAPT Secure: workbench.js loaded` for clarity.

#### [MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)

1. Split the script enqueue conditions in `vaptsecure_enqueue_admin_assets` (around line 720).
2. For `$screen->id === 'toplevel_page_vaptsecure'`, enqueue `client.js`.
3. For `strpos($screen->id, 'vaptsecure-workbench') !== false`, enqueue `workbench.js`.
4. Register the new `vapt-workbench-js` handle and localize it with `vaptSecureSettings` identical to how `client.js` is currently localized.
5. In the new enqueue logic, Ensure the "Generated Interface UI Component" is still enqueued for BOTH pages if it remains shared.

## Verification Plan

### Automated Tests

- No automated tests available, manual verification required.

### Manual Verification

1. Access the regular VAPT Secure Dashboard as a WP Admin. Open Developer Tools (Network/Console tab) and verify `client.js` is loaded and `workbench.js` is NOT loaded.
2. Access the Superadmin Workbench (`admin.php?page=vaptsecure-workbench`). Verify `workbench.js` is loaded and `client.js` is NOT loaded.
3. Ensure the UI still functions fully on both pages without console errors.
