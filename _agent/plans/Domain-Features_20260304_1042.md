# Domain-Features Implementation Plan

> **Task ID:** Domain-Features  
> **Branch:** Domain-Features  
> **Timezone:** UTC+5 (PKT)

---

## 20260304_@1638 — Plan Update: Remove Add New Domain Section

### Objective

Remove the "Add New Domain" header and form row from the Domain Features tab as per user request.

### Changes

- **admin.js**: Remove lines ~2100–2171.

---

## 20260304_@1540 — Task Completed ✅

### Implementation Summary

- **UI Tweak**: Replaced `PanelBody` with a static container and `h2` header.
- **Summary Row**: Removed "Data Sources" checkboxes; preserved Total/Active/Disabled stats.
- **Version**: Bumped to `1.53.0`.
- **Branch**: `Domain-Features`.
- **Verification**: UI verified via browser screenshot; correctly matches the user's annotated request.

---

## 20260304_@1042 — Initial Plan (Approved)

### Goal

Update the VAPT-Secure Domain Admin page UI to match the reference implementation from `ielnetpk`, specifically:

1. **Remove the Summary Pill Row** — The banner showing "SUMMARY: Total Domains: X, Active: X, Disabled: X" and the "DATA SOURCES:" checklist.
2. **Remove the collapsible PanelBody** — Replace the WordPress `PanelBody` wrapper (which adds the expand/collapse chevron) with a plain section container so the heading "Domain Specific Features" is static, not collapsible.

### Key Finding

- `hermasnet` `DomainFeatures` (line 1957–2391 in admin.js) uses `el(PanelBody, {...})` as the root element — this creates the chevron/collapse behavior indicated in the [image] as "Remove".
- `ielnetpk` reference (line 2093–2180) uses `el(Fragment, null, [ el(PanelBody, {...}, [/* Just Summary */]), el('div', { ... }, [/* Table */]) ])` — separates the summary from the table.
- Per user instruction, we are NOT copying from `ielnetpk`; We are tweaking `hermasnet` VAPT-Secure only.

### Changes Required in `admin.js`

| # | Change | Location |
|---|--------|----------|
| 1 | Wrap `DomainFeatures` return in `el(Fragment, null, [...])` instead of `el(PanelBody, {...})` | Line ~2065 |
| 2 | Add a plain styled header `el('div', { className: 'vapt-domain-header' }, 'Domain Specific Features')` | Replace PanelBody |
| 3 | Remove entire Summary Pill Row block | Lines ~2066–2150 |
| 4 | Move Add-Domain form + table to be direct children of Fragment | Already correct, just unwrap from PanelBody |

### Files Affected

- `assets/js/admin.js` — VAPT-Secure plugin only

### Verification

- [ ] Navigate to `http://hermasnet.local/wp-admin/admin.php?page=vaptsecure-domain-admin`
- [ ] Confirm "Domain Specific Features" heading shows (no chevron/toggle)
- [ ] Confirm summary bar is gone
- [ ] Confirm Add New Domain form is visible
- [ ] Confirm domain table renders with correct data
