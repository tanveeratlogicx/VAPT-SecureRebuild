# Design Implementation Modal Fix (A+ JSON)

## Goal Description

The objective is to enrich the "Functional Implementation" section of the "Design Implementation" modal with meaningful "Operational Notes" and "Manual Verification Steps".

Currently, the A+ JSON schema generator hardcodes defaults like *"Specific operational notes are currently unavailable."* when specific fields are missing in the source JSON (`Interface Schema v2.0.json`).

This plan introduces a dynamic fallback mechanism in the A+ Schema Generator and updates the Data Mapping Configuration modal to allow user overrides.

Additionally, we will add a brief sentence above the "Enable Protection" toggle to clearly explain the advantage or protection being added to the system based on the current risk.

## User Review Required

Please review the dynamic fallback logic for the A+ Generator. If no mapping is found for `operational_notes` or `verification_steps`, the system will automatically parse the `summary` and `platform_implementations` to generate actionable guidance.

**Do you approve adding two new fields to the Mapping Configuration Modal for `operational_notes` and `verification_steps`?**

## Proposed Changes

### 1. A+ Schema Generator (`aplus-generator.js`)

#### [MODIFY] `assets/js/modules/aplus-generator.js`

- **Dynamic Feature Extraction:**
  Modify `APlusGenerator.generate` to synthesize `operational_notes` and `verification_steps` if the keys are undefined or empty in the raw `feature` data.
  - **Verification Steps Fallback:** Create steps pointing to the protection toggle, followed by a check against the `summary` description, and instructing the use of the "Automated Verification" panel.
  - **Operational Notes Fallback:** Concatenate the `summary` and iterate through `platform_implementations` to list exactly which driver/file (e.g., `.htaccess` or `wp-config.php`) is being targeted, providing rich contextual data to the "Design Implementation" modal out-of-the-box.
- **Protection Summary Text:**
  Add a new `description` element to the `controls` array, placed immediately above the `Enable Protection` toggle, using the `feature.summary` (or a fallback message) to explain the specific protection being applied.

### 2. Admin Dashboard & Mapping (`admin.js`)

#### [MODIFY] `assets/js/admin.js`

- **Mapping Modal UI (`FieldMappingModal`):**
  Add a new section for **"Additional Context"** in the Field Mapping modal.
  Include two new Mapping Selection drop-downs:
  - `Operational Notes / Context` (`operational_notes`)
  - `Verification Steps` (`verification_steps`)
- **Auto Map Logic:**
  Add detection keywords for the new fields within the `handleAutoMap` utility (e.g., `['operational_notes', 'notes', 'operation_notes']` and `['verification_steps', 'manual_verification', 'steps', 'test_method']`). This allows existing robust schemas to map correctly in the future.

TIMESTAMP: 20260308_@0621

## Verification Plan

### Manual Verification

1. Open the VAPT Secure Dashboard as Superadmin.
2. Select `Interface Schema v2.0.json` (or map one).
3. Access the **Mapping Configuration Modal** to verify the new fields (`Operational Notes / Context` and `Verification Steps`) exist and that "Auto Map" properly looks for them.
4. Open the "Design Implementation" modal (transition a feature to "Develop").
5. In the preview panel, check the "Functional Implementation" component to verify that `Manual verification steps not provided.` has been replaced with the dynamically generated steps and the `Specific operational notes are currently unavailable.` has been replaced with meaningful insights extracted from `platform_implementations`.
6. Verify that immediately above the "Enable Protection" toggle, there is a clear sentence explaining the advantage/protection being added based on the current risk's summary.
