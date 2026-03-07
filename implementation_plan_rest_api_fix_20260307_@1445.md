# Resolve REST API 403 Forbidden Error - FRESH PLAN 20260307_@1505

Latest Status: Corrected environment assumptions and versioning. Targeted folder: `VAPT-Secure`.

## Revision History

### [20260307_@1505] - Corrected Context

- **Correction**: Local (Flywheel) is also using Apache.
- **Correction**: Plugin version is `2.2.8`, NOT `3.13.x`. Targeting `2.2.9`.
- **Insight**: Missing `.htaccess` on Production Apache breaks REST routing regardless of `RISK-003`.

### [20260307_@1500] - "Missing .htaccess" Theory

- **Discovery**: On Apache, missing `.htaccess` leads to 403 on `/wp-json/`.

---

## User Review Required

> [!IMPORTANT]
> I am working in: `t:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure`.
> I will bump the version to **2.2.9** in `vaptsecure.php`.
> (The `3.13.x` references I made earlier were mistakenly pulled from internal module comments).

## Proposed Changes

### [VAPT-Secure Pattern Library]

#### [MODIFY] [enforcer_pattern_library_v2.0.json](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/data/enforcer_pattern_library_v2.0.json)

- Update `RISK-003` `htaccess` code to explicitly allow `/users/me`.

### [VAPT-Secure Enforcers]

#### [MODIFY] [class-vaptsecure-htaccess-driver.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/enforcers/class-vaptsecure-htaccess-driver.php)

- Improve `write_batch` logging to diagnose Production write failures.

### [Plugin Maintenance]

- Bump version to `2.2.9` in `vaptsecure.php`.

## Verification Plan

### Manual

- User to verify `.htaccess` creation and REST API access on Production.
