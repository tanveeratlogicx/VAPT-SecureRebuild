### 20260305_@0528 - License Directory Table Redesign (v2.1.0)

- **UI Realignment**: Transforming the "Domain License Directory" table to match the provided high-fidelity design.
- **Icon Integration**:
  - Added a globe icon (`dashicons-networking`) to the Domain column in **Slate Blue (`#64748b`)**.
  - Added a user icon (`dashicons-admin-users`) to the Usage column in **Slate Blue (`#64748b`)**.
- **Usage High-Fidelity Logic**:
  - Overhauled Usage display to a multi-element flex row: `[Count / Limit] [Bar] [%] [Icon]`.
  - **Smart Display**: Progress bars and percentages are now conditionally hidden for single-site licenses (limit=1), matching the reference design.
- **License Badge Refinement**:
  - Updated "DEVELOPER" to a premium violet/purple theme.
  - Updated "STANDARD" to a crisp blue/grey theme with borders.
- **Action Column Completion**:
  - Integrated stylized "✓ ACTIVE" status pill.
  - Standardized **Delete (Trash)** and **Invalidate (Lock)** icons with Slate Blue coloring.
  - Renamed column header to singular "Action".
- **Version Bump**: Bumping to `2.1.0`.

# License Manager Table Redesign (v2.1.0)

Complete final overhaul of the License Directory table to reach "Elite" commercial standards.

## Proposed Changes

### [VAPT-Secure Plugin]

#### [MODIFY] [admin.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

- Update `LicenseManager` `thead`:
  - Rename "Actions" to "Action".
- Update `LicenseManager` `tbody` rows:
  - **Usage**: Conditional flex layout (bars hidden if limit=1). Bold count and Slate Blue user icon.
  - **Domain**: Slate Blue globe icon added.
  - **License**: Refined violet and blue badge themes.
  - **Action**: "ACTIVE" pill + Trash + Lock icons in unified flex row.

#### [MODIFY] [admin.css](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/css/admin.css)

- Added `.vapt-usage-directory-wrapper` flex styles.
- Enhanced badge gradients and borders.
- Polished `.vapt-status-pill-btn.active` for better visibility.

#### [MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)

- Bump version to `2.1.0`.

## Verification Plan

### Manual Verification

- Navigate to VAPT Secure -> License Management.
- Verify the table exactly matches the provided reference image.
- Confirm single-site rows (limit 1) show no progress bar.
- Confirm multi-site rows (limit > 1) show the bar and percentage.
- Verify icons are correctly colored and sized.
