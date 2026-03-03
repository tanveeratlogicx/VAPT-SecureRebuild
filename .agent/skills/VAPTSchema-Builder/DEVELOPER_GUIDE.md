# VAPTSchema Builder Developer Guide (v1.2.0)

## Core Concept
The VAPTBuilder dynamically generates its UI and enforces security controls entirely via JSON schemas (`generated_schema` in the `vapt_feature_meta` table). When evaluating a risk from the `VAPT-Risk-Catalogue-Full-125-v3.4.1.json`, this skill translates the raw narrative and steps into this executable mapping.

## The Generation Pipeline

When a developer (or the AI) is prompted to generate an Interface Schema for a Risk ID (e.g. `RISK-003`):

1. **Locate Source Truth**: Search `v1.2` data files (`interface_schema_v1.2.json`, `enforcer_pattern_library_v1.2.json`) for the exact `risk_id`.
2. **Determine Driver**: Analyze the enforcer type. Match this to the Enforcer Pattern Library.
3. **Map the Mappings**: 
    - The `controls[0].key` MUST strictly match the key in `enforcement.mappings`.
    - Extract the raw string from the pattern library.
    - **v1.2 Compliance**: For `.htaccess` rewrites, ensure `insertion_point: "before_wordpress_rewrite"` is specified.
4. **Determine the Probe**: Parse `verification` details to populate `test_config`.
5. **Output**: Return ONLY the JSON object.

## Avoiding Common Mistakes (Hallucinations)

### 1. The Rewrite Rule "Dead Zone" (v1.2 Critical)
In v1.0, rewrite rules were sometimes placed at the end of `.htaccess`. **This is a silent failure.** WordPress's catch-all rule ends with `[L]`, stopping all subsequent processing.
- **Fix**: All rewrite rules must be placed **BEFORE** the `# BEGIN WordPress` block.
- **Spec**: Ensure the schema specifies `insertion_point: "before_wordpress_rewrite"`.

### 2. Mandatory Rewrite Wrappers
All Apache rewrite blocks MUST be wrapped in:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    ...
</IfModule>
```
Without `<IfModule>`, if `mod_rewrite` is disabled, the site will suffer a **500 Internal Server Error**.

### 3. Integer Status Codes
`expected_status` inside `test_config` MUST be an integer (`403`), never a string (`"403"`).

### 4. Match the Key
If your toggle creates the `key` `"UI-RISK-003-001"`, your `enforcement.mappings` must have the exact key `"UI-RISK-003-001"`.

