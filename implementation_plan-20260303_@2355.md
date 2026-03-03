# Update Schema Button & A+ Workbench Color Fix

This plan outlines the addition of the "Update Schema" button to the Design Implementation Modal and fixing the "A+ Workbench" button color logic based on implementation status.

## User Review Required

Please review the proposed placement of the `Update Schema` button and the logic for determining if a feature is "Implemented". Currently, the logic considers a feature implemented if `is_enforced` is 1 or its status is `Develop` or `Release`.

> [!IMPORTANT]
> A backup of this plan will be saved to the plugin directory as required by global rules.

## Proposed Changes

### VAPT-Secure Plugin Adjustments

20260303_@2355 - Feature Implementation Section

#### [MODIFY] admin.js(file:///t:/~/Local925 Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

1. **Add `Update Schema` Button:** Within the `DesignModal` component's `vapt-design-modal-actions` wrapper, add a new `Button` element labeled "Update Schema".
2. **Handle `Update Schema` Click:** The new button's `onClick` handler will assemble an updated `feature` object applying the current states of `includeProtocol` and `includeNotes`. It will then call `window.VAPTSECURE_APlusGenerator.generate` with the updated feature and current `customizationText`, and update `schemaText` via `onJsonChange`.
3. **Change "A+ Workbench" Button Color:** In the `FeatureList` component, update the `style` of the `vapt-aplus-workbench-btn` Button. If `parseInt(f.is_enforced) === 1` || `['Develop', 'develop', 'Release', 'release'].includes(f.status)`, the background `linear-gradient` will switch to green (`#10b981` to `#059669`). Otherwise, it remains the default blue.

#### [MODIFY] aplus-generator.js(file:///t:/~/Local925 Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/modules/aplus-generator.js)

1. **Honor Toggles:** Add logic inside the `generate` function to interpret `feature.include_manual_protocol` and `feature.include_operational_notes`.
2. **Inject Sections:** If toggles are active, append `manual_protocol` (populated with `feature.verification_steps` or a default payload) and `operational_notes` (populated with `feature.operational_notes` or a standard instruction) to the final output schema object before returning.

## Verification Plan

### Automated Tests

1. Currently relying on the build configuration tests.
2. We'll simulate `window.VAPTSECURE_APlusGenerator.generate` to verify the modified output.

### Manual Verification

1. Open the VAPT Dashboard.
2. Select a feature and open the Workbench window.
3. Toggle "Include Manual Verification Protocol" and "Include Operational Notes Section" on and off.
4. Click "Update Schema".
5. Verify that the "A+ Adaptive Script (Source JSON)" text visibly reflects the presence or absence of the nodes.
6. Click "Save Status" to invoke a "Develop" state and `is_enforced` flip.
7. Close the modal, and observe the feature main list; the "A+ Workbench" button should now be Green.
