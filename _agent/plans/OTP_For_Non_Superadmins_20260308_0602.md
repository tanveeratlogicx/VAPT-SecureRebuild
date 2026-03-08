# OTP For Non-Superadmins

## User Review Required

The requested feature allows any WordPress Administrator (who has `manage_options` capability) to access the VAPT Secure Domain Admin page by requesting an OTP, which is sent strictly to the Superadmin's email. If the non-superadmin acquires and inputs this OTP, they will be granted access.

Please review the proposed approach and confirm if this meets your requirements.

## Proposed Changes

### VAPT-Secure Core (`vaptsecure.php`)

#### [MODIFY] `vaptsecure.php`

1. **Update `is_vaptsecure_superadmin()`:**
   - Modify the function to also return `true` if `class_exists('VAPTSECURE_Auth') && VAPTSECURE_Auth::is_authenticated()`. This ensures that a non-superadmin who successfully verifies the OTP is treated as a superadmin for the VAPT session duration.

2. **Update Admin Menu Registration (`vaptsecure_add_admin_menu`):**
   - Currently, the `vaptsecure-domain-admin` menu is only registered if `is_vaptsecure_superadmin()` is true. This prevents unauthenticated users from even opening the URL via WordPress (resulting in a default WordPress "Sorry, you are not allowed" error).
   - Change this to register the `vaptsecure-domain-admin` page for all users with `manage_options` capability. This allows unauthenticated admins to reach the page so the OTP check can engage.

3. **Update Permission Checks (`vaptsecure_render_admin_page`):**
   - Remove the `vaptsecure_check_permissions();` call inside `vaptsecure_render_admin_page()`.
   - `vaptsecure_check_permissions()` unconditionally throws a `wp_die()` if the user is not a superadmin, which blocks the OTP screen from rendering for non-superadmins.
   - By removing this, we allow execution to fall through to `vaptsecure_master_dashboard_page()`, which already securely handles the OTP transmission and form template if the user is not authenticated.

TIMESTAMP: 20260308_@0602

## Verification Plan

### Manual Verification

1. Log in to the WordPress dashboard as an Administrator (but fundamentally different from the VAPT Superadmin credentials).
2. Attempt to navigate to the Dashboard URL: `wp-admin/admin.php?page=vaptsecure-domain-admin`.
3. Verify that instead of a generic "Sorry, you are not allowed to access this page" or "You do not have permission" error, the custom VAPT Secure OTP Identity Verification screen successfully loads.
4. Verify that an OTP email is dispatched behind-the-scenes to the Superadmin's configured email address.
5. (Simulated) Enter the OTP on the screen.
6. Verify that upon successful OTP verification, the non-superadmin is granted access to the Domain Admin dashboard.
