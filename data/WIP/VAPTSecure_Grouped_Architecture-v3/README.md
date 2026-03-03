
# VAPT WordPress Protection Suite
## v3.0 — Full Test Package (With Interface Schema)

This package includes:

- Grouped Apache enforcement architecture
- driver_manifest_v3.json
- Original interface_schema_v2.0.json (for integration testing)

## Structure

/vapt-core
  - interface_schema_v2.0.json

/vapt-patterns
  - index.json
  - group_*.json

/vapt-driver
  - driver_manifest_v3.json

## Purpose

This build allows real integration testing between:
- Existing interface schema
- New group-based enforcement engine

You may now:
1. Map risk_id → group_id
2. Test group resolution logic
3. Validate compiled .htaccess blocks
4. Compare v2 vs v3 outputs

Version: 3.0.0 (Test Bundle)
