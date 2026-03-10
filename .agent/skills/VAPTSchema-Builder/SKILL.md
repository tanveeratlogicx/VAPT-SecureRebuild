---
name: VAPTSchema Builder
description: Specialized skill for transforming VAPT-Risk-Catalogue-Full-125-v3.4.1.json definitions into highly accurate Interface Schema JSONs for the VAPTBuilder plugin. Uses the v2.2 Balanced Protection Policy and a 21-point self-check rubric to ensure >90% output accuracy.
version: "2.2.0"
schema_version: "2.2.0"
---

# VAPTSchema Builder Expert Skill (v2.2.0)

This skill acts as the precise translation layer between raw VAPT Risk Catalogs and the strict **Interface Schema JSON** format. Version 2.2 focuses on **Balanced Protection**, ensuring security rules do not disrupt legitimate WordPress admin workflows (XML-RPC, REST API, Upgrades).

## 🎯 Primary Goal

To achieve **>90% accuracy** (zero hallucination) when converting catalog definitions into VAPTBuilder `enforcement` and `controls` schemas, specifically adhering to the **Balanced Protection Policy**.

## 🧠 Trigger Condition

Use this skill whenever asked to **generate an Interface Schema**, **build VAPTBuilder UI configuration JSON**, or **translate a risk catalog item** into an enforcement schema.

---

## 🏛️ The Enforcer Pattern Library (v2.2 Rosetta Stone)

To eliminate AI hallucination, you must STRICTLY map the source data to the target schema using these deterministic patterns from `enforcer_pattern_library_v2.2.json`:

### .htaccess Syntax Guard (MANDATORY)

Before emitting any `.htaccess` code, you MUST ensure:

1. **No Forbidden Directives**: `TraceEnable`, `ServerSignature`, `ServerTokens`, and `<Directory>` are forbidden in `.htaccess`. Use recommended alternatives (e.g., `Header unset Server`).
2. **Placement**: All `RewriteRule` blocks MUST be placed **BEFORE `# BEGIN WordPress`**.
3. **Mandatory Wrapper**: All rewrite blocks MUST be wrapped in `<IfModule mod_rewrite.c>` and include `RewriteEngine On` and `RewriteBase /`.
4. **Target File**: For `RISK-020`, the target file MUST be `wp-content/uploads/.htaccess`.

### Balanced Protection Policy (v2.2)

Rules must protect WITHOUT disrupting legitimate admin workflows:

* **XML-RPC (RISK-002)**: Do NOT block `xmlrpc.php` entirely. Block `system.multicall` POST method only.
* **REST API (RISK-003)**: Do NOT block `/wp-json/wp/v2/users` entirely. Require `Authorization` header or WordPress login cookie. Use PHP `permission_callback` as primary defense.
* **Upgrades/Installs (RISK-027, RISK-028)**: Do NOT block `upgrade.php` or `install.php` entirely. Restrict to `127.0.0.1` + admin IP.

---

## 🎛️ UI Controls Translation Rules (v2.2)

Every control that is NOT of type `test_action`, `risk_indicators`, `assurance_badges`, `test_checklist`, or `evidence_list` MUST include a `help` property with specific sentence templates:

* **Toggle (Enable/Activate):** "Turns on [Feature]. This helps secure the site against [Risk]."
* **Toggle (Disable/Block):** "Enforces blocking for [Feature]. Designed to address: [Risk]."
* **Input (Number/Text):** "Defines the specific [Label] required by this rule."
* **Select/Dropdown:** "Selects the operational mode for [Label]."

---

## ✅ Self-Check Rubric (v2.2 - 21 Point Rubric)

Score every output. **Threshold: ≥18/21 points.**

| # | Check Item | Weight |
|---|---|---|
| 1 | Component IDs match `interface_schema_v2.2` exactly | 2 |
| 2 | Enforcement code read from `enforcer_pattern_library_v2.2` | 2 |
| 3 | Severity badge colors match `global_ui_config.severity_badge_colors` | 1 |
| 4 | Handler names follow naming conventions (`handleRISK{NNN}ToggleChange`) | 1 |
| 5 | Platform listed in `available_platforms` for this risk | 1 |
| 6 | VAPT block markers present in all enforcement code output | 1 |
| 7 | Verification command present and matches platform CLI | 1 |
| 8 | No forbidden patterns violated (snake_case IDs, etc.) | 1 |
| 9 | **[.htaccess]** No forbidden directives (`TraceEnable`, `ServerSignature`, etc.) | 2 |
| 10 | **[.htaccess]** All RewriteRules placed BEFORE `# BEGIN WordPress` | 1 |
| 11 | **[.htaccess]** Wrapped in `<IfModule mod_rewrite.c>` with `RewriteEngine On` | 1 |
| 12 | `mod_headers` requirement noted for all `Header` directives | 1 |
| 13 | `AllowOverride` requirement noted for `Options` directives | 1 |
| 14 | RISK-020 `target_file` = `wp-content/uploads/.htaccess` | 1 |
| 15 | IIS snippets note URL Rewrite Module 2.1 requirement | 1 |
| 16 | Caddy output uses v2 syntax only | 1 |
| 17 | `code_ref` in interface schema uses correct `lib_key` | 1 |
| 18 | `driver_ref` points to `vapt_driver_manifest_v2.2` | 1 |
| 19 | Driver diagnosis includes all required `driver{}` sub-fields | 1 |
| 20 | **[MANDATORY]** Balanced Protection: No blanket blocks of XML-RPC, REST Users, or Upgrades | 2 |

---

## 📋 File Connectivity Reference

* **Lookup Entry**: `interface_schema_v2.2.json` (UI & platform implementations)
* **Logic Source**: `enforcer_pattern_library_v2.2.json` (Exact code via `lib_key`)
* **Driver Manifest**: `vapt_driver_manifest_v2.2.json` (Execution steps)

---

## 📝 Operation Notes Structure (v2.2)

Every enforcer entry in the pattern library includes an `operation_notes` object with structured documentation:

| Sub-field | Content |
|-----------|---------|
| `what_this_does` | Plain-language description sourced from the catalogue step description |
| `balanced_protection` | Present only on RISK-002, RISK-003, RISK-019, RISK-027, RISK-028 — documents which legitimate callers are preserved |
| `deployment_note` | Caveats that don't qualify as balanced protection notices |
| `apache_requirements` | `modules_required`, `allowoverride_class`, `enable_modules` commands, `reload_command` |
| `placement_critical` | Present on all `before_wordpress_rewrite` rules — explains the WordPress dead zone |
| `separate_file_required` | Present on RISK-020 — explains the uploads `.htaccess` requirement |
| `wp_config_requirements` | `constant`, `insertion_rule`, `backup_first`, `file_permissions` |
| `hook_requirements` | `hook_name`, `hook_type`, `plugin_file`, `must_not_use` |
| `fail2ban_requirements` | `jail_name`, `config_file`, `filter_dir`, `restart_command` |
| `nginx_requirements` | `config_file`, `test_command`, `reload_command` |
| `caddy_requirements` | `config_file`, `validate_command`, `reload_command`, `syntax`, `matcher_note` |
| `cloudflare_requirements` | `dashboard_url`, `implementation_type`, `plan_required`, `propagation` |
| `iis_requirements` | `config_file`, `url_rewrite_module`, `validate_command`, `iis_version` |
| `compliance_context` | `frameworks`, `evidence_needed`, `audit_trail`, `pentest_required`, `retention_days` |
| `severity_context` | `level`, `category`, `owasp` |

---

## 🔍 Manual Verification Protocol (v2.2)

Every enforcer entry includes a `manual_verification_protocol` with an ordered verification sequence:

| Sub-field | Content |
|-----------|---------|
| `pre_application_checks` | Module checks, backup commands, config validation — run before applying |
| `application_steps` | Numbered steps for manually applying the rule (mirrors what the driver does) |
| `automated_verification` | `command` + `expected_result` — the CLI command to run immediately after applying |
| `manual_http_tests` | Real HTTP test cases from the source catalogue, with `curl_command`, `url` (FQDN), `expected_http_status` |
| `functional_checks` | Enforcer-specific checks — header grep, hook registration, jail status, log tailing |
| `negative_tests_confirm_legitimate_access` | Tests confirming admin workflows still work — Jetpack, Gutenberg, WooCommerce, wp-cli |
| `evidence_to_collect` | `type`, `description`, `required`, `retention_days` — from compliance requirements |
| `rollback_verification` | `how_to_rollback` + `confirm_rollback` — specific to each enforcer |

**Important:** All curl commands use fully qualified `https://yoursite.com` URLs. All dashboard links are fully qualified `https://` URLs.
