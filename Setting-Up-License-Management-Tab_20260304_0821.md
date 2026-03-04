# Setting Up License Management Tab

This plan outlines the changes required to update the "License Management" tab UI in the `admin.js` file of the VAPT-Secure plugin to match the provided mockup.

## Proposed Changes

### 20260304_@0821 - UI Implementation Review Required

#### [MODIFY] admin.js(file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

The `LicenseManager` component needs structural modifications to match the reference image:

**1. Left Card: "License Status"**

- Change "Add License" (which seems to be incorrectly labeled in the current code for the left side or right side depending) to "License Status". Wait, the current code has "License Status" on the left.
- Add an edit box for the "Domain Name" or keep it read-only but styled like an input. (Currently read-only `TextControl`).
- Add "First Activated" and "Expiry Date" side-by-side.
- Add "Terms Renewed" block.
- Add the license description text below.

**2. Right Card: "Update License"**

- Change heading from "Add License" to "Update License".
- Add a "+ Add New Domain" button in the header of this card.
- "DOMAIN NAME (RENAME)": Make the domain name field editable. Add warning text below it: "Warning: Changing this will rename the current domain."
- "DOMAIN TYPE": Standard/Wildcard dropdown in the same row.
- "LICENSE SCOPE", "LIMIT", and "LICENSE ID - UNIQUE LICENSE IDENTIFIER" on the next row.
- "LICENSE TYPE" and "EXPIRY STATUS" in the next row.
- "Auto Renew" toggle switch.
- Action buttons: "Update License" (primary), "Manual Renew" (secondary).

**3. Bottom Table: "Domain License Directory"**

- Ensure the table headers match the mockup: `License ID`, `Limit`, `Domain`, `Version`, `License`, `Activated At`, `Expiry`, `Renewals`, `Actions`. (Need to add `Version` to the table layout).
- Restyle the badge for "DEVELOPER".
- Ensure the search box "Search domains..." is correctly aligned above the table on the right.

### [Component Name: React Frontend UI]

#### [MODIFY] admin.css(file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/css/admin.css)

- Add or adjust styles for the "+ Add New Domain" button.
- Adjust grid layouts to match the mockup exactly.

## Verification Plan

### Automated Tests

- None currently set up for React component visual regression.

### Manual Verification

- Open the WordPress admin dashboard.
- Navigate to the "VAPT Secure Domain Admin" page.
- Switch to the "License Management" tab.
- Visually compare the rendered UI with the provided mockup image.
- Test that changing the Domain Name updates the state (or if it's meant to be renaming, verify the API endpoint supports it).
- Verify the "+ Add New Domain" button opens a dialog or resets the form to add a new domain.
