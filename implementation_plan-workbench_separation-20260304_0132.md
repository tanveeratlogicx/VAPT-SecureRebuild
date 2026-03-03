# Separating Workbench into workbench.js

This plan outlines the steps required to separate the Superadmin Workbench functionality into a dedicated `workbench.js` file, ensuring `client.js` is strictly used for the WordPress Admin User dashboard and only displays features released to the domain.

## User Review Required

Please review this updated plan. It incorporates your feedback that the standard dashboard (`client.js`) should only have access to features released to the domain by the license.

### 20260304_@0132

**Review:** Does this revised approach accurately capture the required restrictions for `client.js`? Are there any specific UI elements in `client.js` you want removed completely (e.g., the categories sidebar) now that it acts merely as a client dashboard?

## Proposed Changes

---

### File Modifications

#### [NEW] [workbench.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/workbench.js)

1. Clone the current content of `client.js` into this new file, as it will power the Superadmin Workbench.
2. Hardcode the API path to constantly fetch `vaptsecure/v1/features` without the `scope=client` restriction.
3. Keep all "Status" tabs (All Lifecycle, Develop, Release) and design tools.
4. Update the console log to say `VAPT Secure: workbench.js loaded`.

#### [MODIFY] [client.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/client.js)

1. Remove all logic related to checking `isWorkbench`.
2. Hardcode the API path to `vaptsecure/v1/features?scope=client&domain=${domain}`.
3. If necessary, restrict the UI to only display features with a 'Release' status natively (hiding 'Draft' and 'Develop' tabs from the WordPress Admin view).

#### [MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)

1. Split the script enqueue conditions in `vaptsecure_enqueue_admin_assets` (around line 720).
2. For `$screen->id === 'toplevel_page_vaptsecure'`, enqueue `client.js`.
3. For `strpos($screen->id, 'vaptsecure-workbench') !== false`, enqueue `workbench.js`.
4. Register the new `vapt-workbench-js` handle and localize it with `vaptSecureSettings`.
5. Ensure the "Generated Interface UI Component" is still enqueued for BOTH pages if they continue to share it.

## Verification Plan

### Manual Verification

1. Access the regular VAPT Secure Dashboard as a WP Admin.
   - Verify `client.js` is loaded and `workbench.js` is NOT loaded.
   - Verify it only displays features released to the current domain.
2. Access the Superadmin Workbench (`admin.php?page=vaptsecure-workbench`).
   - Verify `workbench.js` is loaded and `client.js` is NOT loaded.
   - Verify it displays the complete workbench with all functionalities and Draft/Develop features.
