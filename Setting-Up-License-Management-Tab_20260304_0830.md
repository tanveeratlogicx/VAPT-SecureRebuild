# Setting Up License Management Tab

This plan outlines the changes required to update the "License Management" tab UI in the `admin.js` file of the VAPT-Secure plugin. After reviewing the reference implementation on `ielnetpk`, it is clear that the code has already been written there.

## Proposed Changes

### 20260304_@0830 - Porting Existing Implementation Review Required

#### [MODIFY] admin.js(file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

- Replace the current `LicenseManager` component (lines `3014-3601`) with the updated `LicenseManager` component from the `ielnetpk` workspace (`t:\~\Local925 Sites\ielnetpk\app\public\wp-content\plugins\VAPTSecure\assets\js\admin.js`, lines `3008-3812`).
- This updated component includes the new "Register New Domain" form logic, the improved grid layout, the multi-site usage bar, and the updated table columns/sorting (including "Version").

#### [MODIFY] admin.css(file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/css/admin.css)

- Replace the "License Management Styles" block in the current `admin.css` with the updated block from the `ielnetpk` workspace's `admin.css` (lines `211-404`).
- This includes styles for `.vapt-license-grid`, `.vapt-license-card`, the refined `.vapt-license-badge` definitions, the `.vapt-usage-bar-container`, and the `.vapt-correction-controls`.

## Verification Plan

### Manual Verification

- Open the WordPress admin dashboard on the `hermasnet` site.
- Navigate to the "VAPT Secure Domain Admin" page.
- Switch to the "License Management" tab.
- Visually verify that the new card layout is present ("License Status" on the left, "Update License" / "Register New Domain" on the right).
- Check that the "+ Add New Domain" button toggles the creation form.
- Verify the Domain License Directory table includes the "Version" column and the usage progress bar for Multi-Site licenses.
