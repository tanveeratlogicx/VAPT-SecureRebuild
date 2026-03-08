# Resolve REST API 403 Forbidden Error - REVISION 1 20260307_@1545

Latest Status: **CRITICAL BUG FOUND**. The plugin's `.htaccess` driver wipes the root file if Global Enforcement is OFF, deleting WordPress routing rules.

## Revision History

### [20260307_@1545] - The "Self-Wiping" Bug Fix

- **Discovery**: `write_batch` writes an empty file if no features are enforced (e.g. Global OFF), which deletes the `# BEGIN WordPress` block.
- **Fix**: Added a mandatory safeguard to `write_batch` to ensure the WordPress core block is always preserved/restored for the site root.
- **Path Correction**: Switched to `get_home_path()` for better Apache site root detection.

---

## User Review Required

> [!IMPORTANT]
> This explains why you are seeing 403s: the plugin accidentally deleted your WordPress Permalink rules when you activated it or saved a setting while Global Protection was OFF.
> My fix will **automatically restore** these rules.

## Proposed Changes

### [VAPT-Secure Pattern Library]

- Update `RISK-003` to allow `/users/me`.

### [VAPT-Secure Enforcers]

- **Safeguard**: Rewrite `write_batch` in `class-vaptsecure-htaccess-driver.php` to prevent destructive empty writes.
- **Self-Healing**: Automatically insert/restore the standard WordPress block if missing.

### [Plugin Maintenance]

- Bump version to `2.2.9`.

## Verification Plan

- User to re-save any feature on Production; verify that the `.htaccess` is restored and 403s disappear.
