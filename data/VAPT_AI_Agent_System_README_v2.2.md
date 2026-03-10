# VAPT Risk Catalogue — AI Agent System
## Unified Bundle v2.2
**Version:** 2.1.0 | **Date:** 2026-02-21 | **Source:** VAPT-Risk-Catalogue-Full-125-v3_4_1.json (125 risks)

> **v2.2 — Balanced Protection.** Corrects five over-aggressive rules that were disrupting legitimate WordPress admin workflows — XML-RPC integrations, the REST API users endpoint, WordPress auto-updates, and the installer. Every rule now targets the specific attack vector while explicitly preserving the legitimate caller. Fully qualified clickable URLs added throughout.
>
> **v2.0 — Full Rebuild.** All five files generated together from source as a single coherent bundle. Replaced all prior versioned files (v1.0–v1.3). Zero cross-reference errors at build time.

---

## Bundle Files

### AI Agent Layer

| File | Role | Size |
|------|------|------|
| [`enforcer_pattern_library_v2.2.json`](enforcer_pattern_library_v2.2.json) | All 125 risks × all enforcer types — balanced corrected code, driver sub-objects, Cloudflare/IIS/Caddy derivations | ~927 KB |
| [`interface_schema_v2.2.json`](interface_schema_v2.2.json) | UI component definitions, `code_ref` and `driver_ref` pointers, available platforms per risk | ~1.1 MB |
| [`ai_agent_instructions_v2.2.json`](ai_agent_instructions_v2.2.json) | System prompt, task definitions, .htaccess syntax guard, balanced protection policy, 21-point self-check rubric | ~24 KB |

### Driver Layer

| File | Role | Size |
|------|------|------|
| [`vapt_driver_manifest_v2.2.json`](vapt_driver_manifest_v2.2.json) | Machine-executable instructions for all 125 risks — every field directly usable by the PHP driver | ~199 KB |
| [`VAPT_Driver_Reference_v2.2.php`](VAPT_Driver_Reference_v2.2.php) | Drop-in `VAPT_Driver` PHP class — full apply/rollback contract against the manifest | ~8 KB |

---

## Balanced Protection Policy

Every rule in this bundle is designed to block the specific attack vector without disrupting legitimate WordPress admin workflows. The following principles govern all enforcement code:

### Core Principles

1. **Prefer authentication-gating over blanket blocking** — if the endpoint serves legitimate authenticated users, require authentication rather than blocking entirely
2. **Block the attack method, not the protocol** — target `system.multicall` abuse in XML-RPC, not all XML-RPC calls
3. **Always allow localhost (127.0.0.1)** — WordPress auto-updater, `wp-cli`, and server-side tools run from the server itself
4. **Document what is preserved** — every rule note names the legitimate callers that continue to work
5. **PHP permission callbacks as primary defence for REST API** — more reliable than `.htaccess` for cookie-based auth detection
6. **Provide IP-allowlist instructions** — admin-only paths include commented `Allow from YOUR.ADMIN.IP.HERE`

### Never Block Entirely

| Endpoint / File | Why | Balanced Approach |
|----------------|-----|-------------------|
| `xmlrpc.php` | Breaks [Jetpack](https://jetpack.com), [WordPress mobile apps](https://apps.wordpress.org), [ManageWP](https://managewp.com), [UpdraftPlus](https://updraftplus.com), [MainWP](https://mainwp.com) | Block `system.multicall` POST method only |
| `/wp-json/wp/v2/users` | Breaks Gutenberg `@mention` autocomplete, [WooCommerce](https://woocommerce.com) admin, JWT auth plugins | Require `Authorization` header or WordPress login cookie |
| `upgrade.php` | Breaks WordPress auto-updates and `wp-cli core update` | Restrict to `127.0.0.1` + admin IP; deny external |
| `install.php` | Breaks fresh installs and multisite sub-site provisioning | Restrict to `127.0.0.1` + admin IP; remove rule during install |

### Admin-Safe REST API Endpoints (never block these)

The following REST endpoints are routinely called by WordPress admin panels, plugins, and headless frontends. None of these should be blocked without authentication-gating:

- `https://yoursite.com/wp-json/wp/v2/users` *(authenticated only)*
- `https://yoursite.com/wp-json/wp/v2/posts`
- `https://yoursite.com/wp-json/wp/v2/media`
- `https://yoursite.com/wp-json/wp/v2/settings`
- `https://yoursite.com/wp-json/wc/v3/*` *(WooCommerce)*
- `https://yoursite.com/wp-json/jetpack/*` *(Jetpack)*

---

## How the Files Connect

```
interface_schema_v2.2.json
  └─ platform_implementations[platform]
       ├─ lib_key   → key name in pattern library
       ├─ code_ref  → enforcer_pattern_library_v2.2.patterns[risk_id][lib_key]
       └─ driver_ref → vapt_driver_manifest_v2.2.risks[risk_id].steps[*]

enforcer_pattern_library_v2.2.json
  └─ patterns[risk_id][lib_key]
       └─ driver{}  → fields consumed by vapt_driver_manifest_v2.2

vapt_driver_manifest_v2.2.json
  └─ risks[risk_id].steps[n]  → consumed by VAPT_Driver_Reference_v2.2.php
```

---

## Enforcer Key Map

All files use these consistent `lib_key` names throughout:

| `lib_key` | Enforcer | Target File |
|-----------|----------|-------------|
| `htaccess` | Apache / .htaccess | `{ABSPATH}.htaccess` |
| `wp_config` | wp-config.php | `{ABSPATH}wp-config.php` |
| `php_functions` | PHP Functions / hooks | `{ABSPATH}wp-content/plugins/vapt-protection-suite/vapt-functions.php` |
| `wordpress` | WordPress action/filter hooks | Same plugin file |
| `wordpress_core` | WordPress Core filters | Same plugin file |
| `fail2ban` | fail2ban | `/etc/fail2ban/jail.local` |
| `nginx` | Nginx | `/etc/nginx/conf.d/vapt-security.conf` |
| `apache` | Apache httpd.conf | `/etc/apache2/conf-available/vapt-security.conf` |
| `caddy_native` | Native Caddy (RISK-123, RISK-124) | `/etc/caddy/Caddyfile` |
| `server_cron` | Server Cron | `crontab` |
| `cloudflare` | Cloudflare (derived from htaccess) | [Cloudflare Dashboard](https://dash.cloudflare.com) |
| `iis` | IIS web.config (derived from htaccess) | `web.config` |
| `caddy` | Caddyfile v2 (derived from htaccess) | `/etc/caddy/Caddyfile` |

**Special case — RISK-020:** `target_file` is `wp-content/uploads/.htaccess` — a separate file inside the uploads directory, not the WordPress root `.htaccess`. `<Directory>` blocks are silently ignored in `.htaccess`; this is the only correct approach.

---

## AI Agent Workflow

### Step 1 — Select Task Type

| Task | When to Use |
|------|-------------|
| `generate_ui_component_schema` | Build UI JSON for one or more risks |
| `generate_enforcement_code` | Platform code for one risk + one platform |
| `generate_full_risk_package` | UI schema + all platform codes for one risk |
| `diagnose_driver_failure` | Inspect driver manifest to find why a rule wasn't applied |
| `generate_new_manifest_entry` | Add a new risk across all three data files |

### Step 2 — Schema-First Lookup

Always read `interface_schema_v2.2.json` before writing any UI output:

```
interface_schema_v2.2.risk_interfaces[RISK-XXX]
  → risk_id, title, category, severity.level, severity.colors
  → ui_layout   { card_id, section, order, collapsible, default_expanded }
  → components  [{ component_id, type, label, default_value, on_change, settings_key }]
  → actions     [{ action_id, type, label, confirmation_required, rest_endpoint }]
  → available_platforms[]
  → platform_implementations[platform]
       { lib_key, code_ref, driver_ref, operation, requires, allowoverride, note }
```

### Step 3 — Pattern Library Lookup

Read `enforcer_pattern_library_v2.2.json` using the `lib_key`. **Never write enforcement code from memory.**

```
enforcer_pattern_library_v2.2.patterns[RISK-XXX][lib_key]
  → code             bare code only
  → wrapped_code     code with begin_marker / end_marker included — use this for output
  → begin_marker     e.g. "# BEGIN VAPT RISK-003"
  → end_marker       e.g. "# END VAPT RISK-003"
  → insertion_point  canonical token
  → anchor           { search, position, fallback }
  → requires         Apache modules required
  → allowoverride    AllowOverride directive class required
  → note             caveats, legitimate callers preserved, balanced approach rationale
  → verification     { command, expected }
  → driver           { write_mode, target_file, write_block, anchor_string, … }

  .htaccess risks also carry:
  → cloudflare       { implementation_type, expression, note, … }
  → iis              { implementation_type, web_config_snippet, … }
  → caddy            { implementation_type, caddyfile_snippet, … }
```

### Step 4 — Balanced Protection Check

Before emitting any enforcement code, apply the balanced protection policy:

| Risk | Check |
|------|-------|
| Any rule touching `xmlrpc.php` | Must NOT block all requests — must target multicall or specific method |
| Any rule touching `/wp-json/wp/v2/users` | Must NOT block all requests — must require auth (Authorization header or cookie) |
| Any rule touching `upgrade.php` | Must allow from `127.0.0.1` |
| Any rule touching `install.php` | Must allow from `127.0.0.1` and include remove-before-install note |
| Any REST API endpoint rule | Prefer PHP `permission_callback` as primary; `.htaccess` as secondary |

### Step 5 — Run the .htaccess Syntax Guard

Before emitting any `.htaccess` code:

**Hard-forbidden (silently ignored by Apache):**

| Directive | Why Forbidden | Correct Alternative |
|-----------|--------------|---------------------|
| `TraceEnable off` | Server-level only | `mod_rewrite` TRACE method blocker in `<IfModule>` |
| `ServerSignature Off` | Server-level only | `Header unset Server` via `mod_headers` |
| `ServerTokens Prod` | Server-level only | `Header unset Server` via `mod_headers` |
| `<Directory ...>` | Silently ignored | `<FilesMatch>` in the target subdirectory's `.htaccess` |

**Required structure for every RewriteRule / RewriteCond block:**

```apache
# BEGIN VAPT RISK-XXX
# Requires: mod_rewrite | AllowOverride: FileInfo or All
# Position: BEFORE # BEGIN WordPress
# Preserves: [list legitimate callers that still work]
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    {your_rules}
</IfModule>
# END VAPT RISK-XXX
```

**Why BEFORE `# BEGIN WordPress`:** WordPress's block ends with `RewriteRule . /index.php [L]`. The `[L]` flag stops all further rewrite processing. Any `RewriteRule` placed after `# END WordPress` is in a dead zone and will never execute.

**Required companions:**

| Directive | Requirement |
|-----------|-------------|
| `Header` directives | `mod_headers` — note: `sudo a2enmod headers && sudo service apache2 restart` |
| `Options` directives | `AllowOverride Options` or `All` in `httpd.conf` |
| `<Files>` / `<FilesMatch>` | `AllowOverride Limit` or `All` in `httpd.conf` |

### Step 6 — Self-Check Rubric (≥18 / 21 required)

| # | Check | Weight |
|---|-------|--------|
| 1 | Component IDs match `interface_schema_v2.2` exactly | 2 |
| 2 | Enforcement code read from `enforcer_pattern_library_v2.2` — not written from memory | 2 |
| 3 | Severity badge colors match `global_ui_config.severity_badge_colors` | 1 |
| 4 | Handler names follow naming conventions | 1 |
| 5 | Platform listed in `available_platforms` for this risk | 1 |
| 6 | VAPT block markers present in all enforcement code output | 1 |
| 7 | `verification.command` present and matches platform CLI | 1 |
| 8 | No forbidden naming patterns | 1 |
| 9 | No forbidden `.htaccess` directives (`TraceEnable`, `ServerSignature`, `<Directory>`) | 2 |
| 10 | All `RewriteRule`/`RewriteCond` placed BEFORE `# BEGIN WordPress` | 1 |
| 11 | All `RewriteRule`/`RewriteCond` wrapped in `<IfModule mod_rewrite.c>` with `RewriteEngine On` and `RewriteBase /` | 1 |
| 12 | `mod_headers` requirement noted for all `Header` directives | 1 |
| 13 | `AllowOverride` requirement noted for `Options` directives | 1 |
| 14 | RISK-020 `target_file` = `wp-content/uploads/.htaccess` | 1 |
| 15 | IIS `<rewrite>` sections note URL Rewrite Module 2.1 requirement | 1 |
| 16 | Caddy output uses v2 syntax only — no Apache directives, no semicolons | 1 |
| 17 | `code_ref` uses correct `lib_key` (e.g. `htaccess`, `wp_config`) | 1 |
| 18 | `driver_ref` points to `vapt_driver_manifest_v2.2` | 1 |
| 19 | Driver diagnosis/generation includes all required `driver{}` sub-fields | 1 |
| 20 | No blanket block of `xmlrpc.php`, `/wp-json/wp/v2/users`, `upgrade.php`, or `install.php` — balanced approach used | 2 |

**Score < 18 → identify failing checks and regenerate.**

### Step 7 — Understand the Driver Layer

The AI Agent does not run the driver, but must understand it for diagnosis and new entry generation.

**Diagnosing a driver failure** — read `vapt_driver_manifest_v2.2.risks[RISK-XXX].steps[n]` and check:

| Field | Common Failure |
|-------|----------------|
| `idempotency.check_string` | Already-present marker triggered skip — rule was already applied |
| `insertion.anchor_string` | Anchor string absent from target file — fallback used instead |
| `target_file` | `{ABSPATH}` resolved to wrong directory |
| `write_block` | Empty or missing begin/end markers |
| `write_mode` | Appended to dead zone (after WordPress `[L]` catch-all) |

**Generating a new manifest entry** — every step must include: `write_mode`, `target_file`, `write_block`, `begin_marker`, `end_marker`, `insertion.anchor_string`, `insertion.anchor_position`, `insertion.fallback`, `idempotency.check_string`, `idempotency.if_found`, `backup_required`, `verification.command`, `verification.expected`, `rollback.begin_marker`, `rollback.end_marker`, `rollback.target_file`.

---

## Driver Layer Reference

The `VAPT_Driver` class in [`VAPT_Driver_Reference_v2.2.php`](VAPT_Driver_Reference_v2.2.php) reads [`vapt_driver_manifest_v2.2.json`](vapt_driver_manifest_v2.2.json) and executes this sequence for each step:

```
1.  Resolve {ABSPATH} → full filesystem path
2.  Create target file if it does not exist (e.g. wp-content/uploads/.htaccess)
3.  Check idempotency.check_string in file → if found AND if_found=skip → return "already applied"
4.  Backup target file (append .vapt.bak.{timestamp})
5.  Find insertion.anchor_string in file content
6.  Insert write_block at anchor_position (before / after / prepend / append)
7.  If anchor not found → use insertion.fallback strategy
8.  Write new content to target_file
9.  On write failure → remove begin_marker..end_marker block (rollback)
```

**PHP API:**

```php
$driver = new VAPT_Driver(
    ABSPATH,
    plugin_dir_path( __FILE__ ) . 'vapt_driver_manifest_v2.2.json'
);
$driver->apply( 'RISK-003' );    // returns [['success'=>bool, 'message'=>string], ...]
$driver->rollback( 'RISK-003' ); // removes all VAPT blocks for this risk from their files
```

---

## Enforcer Block Markers

| Enforcer | Begin | End |
|----------|-------|-----|
| `.htaccess`, Nginx, Apache, Caddy, fail2ban, Server Cron | `# BEGIN VAPT RISK-XXX` | `# END VAPT RISK-XXX` |
| `wp-config.php` | `/* BEGIN VAPT RISK-XXX */` | `/* END VAPT RISK-XXX */` |
| PHP Functions, WordPress, WordPress Core | `// BEGIN VAPT RISK-XXX` | `// END VAPT RISK-XXX` |

---

## Insertion Point Reference

| Token | Anchor String | Position | Required For |
|-------|--------------|----------|--------------|
| `before_wordpress_rewrite` | `# BEGIN WordPress` | before | **All RewriteRule/RewriteCond** |
| `after_wordpress_rewrite` | `# END WordPress` | after | Non-rewrite directives only |
| `beginning_of_file` | *(none — prepend)* | prepend | Header, Options, Files blocks |
| `end_of_file` | *(none — append)* | append | Non-rewrite directives |
| `before_wp_settings` | `require_once ABSPATH` | before | All wp-config.php constants |
| `functions_php` | *(none — append)* | append | PHP / WordPress hooks |
| `jail_local` | *(none — append)* | append | fail2ban rules |
| `http_block` | `http {` | after | Nginx directives |
| `crontab_entry` | *(none — append)* | append | Server Cron jobs |

---

## Platform Verification Commands

| Platform | Validate | Reload | Reference |
|----------|----------|--------|-----------|
| Apache/.htaccess | `apachectl -t` | `service apache2 reload` | [Apache docs](https://httpd.apache.org/docs/2.4/howto/htaccess.html) |
| wp-config.php | `wp config get CONSTANT` | *(next PHP request)* | [wp-config reference](https://developer.wordpress.org/apis/wp-config-php/) |
| PHP Functions | `wp eval "do_action('hook');"` | *(next request)* | [Plugin API](https://developer.wordpress.org/plugins/hooks/) |
| Nginx | `nginx -t` | `nginx -s reload` | [Nginx docs](https://nginx.org/en/docs/) |
| Apache httpd.conf | `apachectl -t` | `service apache2 reload` | [Apache VirtualHost](https://httpd.apache.org/docs/2.4/vhosts/) |
| Caddy | `caddy validate --config /etc/caddy/Caddyfile` | `caddy reload` | [Caddy docs](https://caddyserver.com/docs/) |
| Cloudflare | Dashboard review | Instant on save | [CF WAF Custom Rules](https://developers.cloudflare.com/waf/custom-rules/) |
| IIS | `appcmd.exe validate config` | `iisreset /restart` | [IIS URL Rewrite](https://www.iis.net/downloads/microsoft/url-rewrite) |
| fail2ban | `fail2ban-client -t` | `fail2ban-client reload` | [fail2ban docs](https://www.fail2ban.org/wiki/index.php/MANUAL_0_8) |
| Server Cron | `crontab -l` | *(auto)* | [crontab reference](https://man7.org/linux/man-pages/man5/crontab.5.html) |

---

## Platform Coverage

### Cloudflare (derived for all 28 .htaccess risks)

| Type | Product | Balanced Scoping |
|------|---------|-|
| `transform_rule` | [HTTP Response Header Modification](https://developers.cloudflare.com/rules/transform/response-header-modification/) | Headers — no auth scoping needed |
| `waf_custom_rule` | [WAF Custom Rules](https://developers.cloudflare.com/waf/custom-rules/) | RISK-003: excludes `Authorization` header + WP login cookie |
| `waf_custom_rule` | [WAF Custom Rules](https://developers.cloudflare.com/waf/custom-rules/) | RISK-002: scoped to `system.multicall` POST body |
| `notes_only` | Origin server only | RISK-013, RISK-025, RISK-026 — no Cloudflare equivalent |

### IIS

Requires [IIS URL Rewrite Module 2.1](https://www.iis.net/downloads/microsoft/url-rewrite).
Verify: `appcmd list module /name:RewriteModule`

`removeServerHeader="true"` requires [IIS 10.0+](https://docs.microsoft.com/en-us/iis/configuration/system.webServer/security/requestFiltering/).

### Caddy

[Caddyfile v2 syntax](https://caddyserver.com/docs/caddyfile) only.  
Named matchers must be alphanumeric — no hyphens (e.g. `@risk003`, not `@risk-003`).  
Always run `caddy validate --config /etc/caddy/Caddyfile` before `caddy reload`.

---

## Naming Conventions

| Entity | Pattern | Example |
|--------|---------|---------|
| Component ID | `UI-RISK-{NNN}-{SEQ}` | `UI-RISK-003-001` |
| Action ID | `ACTION-{NNN}-{SEQ}` | `ACTION-003-001` |
| Toggle handler | `handleRISK{NNN}ToggleChange` | `handleRISK003ToggleChange` |
| Dropdown handler | `handleRISK{NNN}DropdownChange` | `handleRISK003DropdownChange` |
| Settings key | `vapt_risk_{nnn}_enabled` | `vapt_risk_003_enabled` |
| REST endpoint | `/wp-json/vapt/v1/risk-{nnn}` | `/wp-json/vapt/v1/risk-003` |
| PHP function | `vapt_{descriptive_name}` | `vapt_restrict_users_endpoint` |
| Caddy matcher | `@risk{nnn}` | `@risk003` |

---

## Usage Examples

### Example 1 — RISK-002: Balanced XML-RPC protection

**Prompt:** `Generate .htaccess enforcement for RISK-002 (XML-RPC Pingback Attack)`

**Agent steps:**
1. Read `enforcer_pattern_library_v2.2.patterns.RISK-002.htaccess`
2. Balanced protection check: rule targets `system.multicall` only — Jetpack, mobile apps, ManageWP unaffected ✓
3. Output `wrapped_code` with VAPT markers
4. Note in output: *"Legitimate callers preserved: Jetpack, WordPress mobile apps, ManageWP, MainWP, UpdraftPlus. To fully disable XML-RPC when no integrations require it, use `<Files xmlrpc.php> Require all denied </Files>` — verify with `grep -r xmlrpc wp-content/plugins` first."*
5. Self-check: check 20 passes (balanced approach, not blanket block) → score ≥18 → deliver

---

### Example 2 — RISK-003: Auth-gated REST API protection

**Prompt:** `Generate enforcement for RISK-003 (Username Enumeration via REST API)`

**Agent steps:**
1. Read `enforcer_pattern_library_v2.2.patterns.RISK-003.htaccess` — auth-gating rule
2. Read `enforcer_pattern_library_v2.2.patterns.RISK-003.php_functions` — permission callback
3. Output both: `.htaccess` as secondary defence, PHP hook as primary
4. Note: *"Preserved: Gutenberg @mention autocomplete, WooCommerce admin panel, JWT auth plugins, REST API clients with Authorization header or WordPress login cookie."*
5. Cloudflare WAF expression scoped: excludes `Authorization` header and `wordpress_logged_in` cookie

```apache
# BEGIN VAPT RISK-003
# Preserves: Gutenberg, WooCommerce, JWT auth, authenticated admin REST calls
# Blocks: unauthenticated bot enumeration
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_URI} ^/wp-json/wp/v2/users [NC]
    RewriteCond %{HTTP:Authorization} ^$ [NC]
    RewriteCond %{HTTP_COOKIE} !wordpress_logged_in [NC]
    RewriteRule ^ - [F,L]
</IfModule>
# END VAPT RISK-003
```

---

### Example 3 — Diagnose why RISK-003 isn't applying

**Prompt:** `RISK-003 was toggled on but nothing changed in .htaccess`

**Agent steps:**
1. Read `vapt_driver_manifest_v2.2.risks.RISK-003.steps[0]`
2. Check `idempotency.check_string`: `# BEGIN VAPT RISK-003` — already present? → skip triggered
3. Check `insertion.anchor_string`: `# BEGIN WordPress` — present in `.htaccess`?
4. Check `target_file`: `{ABSPATH}.htaccess` — ABSPATH resolves to correct WordPress root?
5. Check `write_block` non-empty and contains both markers
6. Report specific field that failed with corrected value

---

### Example 4 — Full package for RISK-022 (CSP Header) across all platforms

**Prompt:** `Generate full risk package for RISK-022 for .htaccess, Cloudflare, IIS, Caddy`

**Agent steps:**
1. Read `interface_schema_v2.2.risk_interfaces.RISK-022` → components, actions, available_platforms
2. Read `enforcer_pattern_library_v2.2.patterns.RISK-022` → `htaccess`, `cloudflare`, `iis`, `caddy` keys
3. Generate UI component schema using `UI-RISK-022-001`, `handleRISK022ToggleChange`
4. Output `.htaccess`: `Header always set Content-Security-Policy "..."` — note `mod_headers` required
5. Output Cloudflare: [Transform Rule](https://developers.cloudflare.com/rules/transform/response-header-modification/) → Set `Content-Security-Policy`
6. Output IIS: `<customHeaders><add name="Content-Security-Policy" .../>`
7. Output Caddy: `header { Content-Security-Policy "..." defer }`
8. Self-check all outputs → score ≥18 each → deliver package

---

## Source Risk Coverage

| Enforcer | Risks | Balanced Corrections in v2.2 |
|----------|-------|-------------------------------|
| .htaccess (Apache) | 28 | RISK-002, RISK-003, RISK-019, RISK-027, RISK-028 |
| PHP Functions | 28+1 | RISK-003 now also has `php_functions` (permission_callback) |
| wp-config.php | 21 | — |
| fail2ban | 16 | — |
| Server Cron | 10 | — |
| WordPress | 10 | — |
| Nginx | 7 | — |
| Apache | 2 | — |
| Caddy (native) | 2 | — |
| WordPress Core | 1 | — |

| Severity | Count |
|----------|-------|
| Critical | 9 |
| High | 36 |
| Medium | 46 |
| Low | 34 |

---

## Platform Limitations

### Apache / .htaccess
- `AllowOverride` must permit the relevant directive class in `httpd.conf` — without it, `.htaccess` rules are silently ignored with no error
- `mod_rewrite`: `sudo a2enmod rewrite` | `mod_headers`: `sudo a2enmod headers`
- `TraceEnable`, `ServerSignature`, `ServerTokens` are server-level only — add to `httpd.conf` in addition to the `.htaccess` workaround
- `<Directory>` is silently ignored in `.htaccess` — use per-directory `.htaccess` files instead
- Reference: [Apache .htaccess Guide](https://httpd.apache.org/docs/2.4/howto/htaccess.html)

### Cloudflare
- Cannot enforce `Options -Indexes`, `FileETag`, or PHP-level directives from the edge — enforce at origin
- `Header unset Server` → [Transform Rule](https://developers.cloudflare.com/rules/transform/response-header-modification/) (all plans including Free)
- Rate limiting beyond basic thresholds: [Cloudflare Pro or higher](https://www.cloudflare.com/plans/)
- Body inspection for `system.multicall`: only available with [Cloudflare WAF](https://developers.cloudflare.com/waf/) (Pro+)

### IIS
- [URL Rewrite Module 2.1](https://www.iis.net/downloads/microsoft/url-rewrite) required for `web_config_url_rewrite` rules
- `removeServerHeader="true"` requires [IIS 10.0+](https://docs.microsoft.com/en-us/iis/configuration/system.webServer/security/requestFiltering/)
- PHP `php_flag engine off` has no IIS equivalent — use `requestFiltering` on `.php` extension instead

### Caddy
- [Caddyfile v2 syntax](https://caddyserver.com/docs/caddyfile) only — v1 is incompatible
- Directory listing disabled by default in `file_server` — no directive needed unless `browse` is present
- Caddy cannot inspect POST body natively — recommend PHP-level filtering for XML-RPC multicall

---

## What Changed

### v2.2 — Balanced Protection

Five rules replaced with targeted, admin-safe equivalents:

| Risk | v2.0 Rule | v2.2 Balanced Rule |
|------|-----------|-------------------|
| RISK-002 | Blanket block of `xmlrpc.php` | Block `system.multicall` POST only — Jetpack, mobile apps, ManageWP unaffected |
| RISK-003 | Block all requests to `/wp-json/wp/v2/users` | Require `Authorization` header or WP login cookie — Gutenberg, WooCommerce unaffected; PHP `permission_callback` added as primary defence |
| RISK-019 | Blocked `.yml` and `.conf` files | Removed `.yml`/`.conf` from block list (WordPress tooling uses these); added `.htpasswd` |
| RISK-027 | Global block of `install.php` | Restrict to `127.0.0.1` + admin IP — multisite provisioning and fresh installs preserved |
| RISK-028 | Global block of `upgrade.php` | Restrict to `127.0.0.1` + admin IP — WordPress auto-updater and `wp-cli` preserved |

Also added:
- `balanced_protection_policy` section in agent instructions with never-block-entirely list
- Self-check rubric check #20 (weight 2): validates no blanket blocks of protected endpoints
- Rubric threshold raised from ≥18/21 (from ≥16/19 in v2.0)
- Fully qualified clickable URLs throughout README

### v2.0 — Full Rebuild
All five files generated together from source. 0 cross-reference errors. Replaced all prior versioned files (v1.0–v1.3). Key structural fixes: consistent `lib_key` naming, `code_ref`/`driver_ref` fields in interface schema, `driver{}` sub-object in every pattern library entry, correct rewrite rule placement (before WordPress `[L]` catch-all), and removal of all forbidden `.htaccess` directives.

---

## External References

| Resource | URL |
|----------|-----|
| WordPress REST API Handbook | [https://developer.wordpress.org/rest-api/](https://developer.wordpress.org/rest-api/) |
| WordPress XML-RPC API | [https://codex.wordpress.org/XML-RPC_WordPress_API](https://codex.wordpress.org/XML-RPC_WordPress_API) |
| Apache .htaccess Guide | [https://httpd.apache.org/docs/2.4/howto/htaccess.html](https://httpd.apache.org/docs/2.4/howto/htaccess.html) |
| Apache mod_rewrite | [https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html](https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html) |
| Apache mod_headers | [https://httpd.apache.org/docs/2.4/mod/mod_headers.html](https://httpd.apache.org/docs/2.4/mod/mod_headers.html) |
| Cloudflare WAF Custom Rules | [https://developers.cloudflare.com/waf/custom-rules/](https://developers.cloudflare.com/waf/custom-rules/) |
| Cloudflare Transform Rules | [https://developers.cloudflare.com/rules/transform/](https://developers.cloudflare.com/rules/transform/) |
| IIS URL Rewrite Module | [https://www.iis.net/downloads/microsoft/url-rewrite](https://www.iis.net/downloads/microsoft/url-rewrite) |
| Caddyfile v2 Reference | [https://caddyserver.com/docs/caddyfile](https://caddyserver.com/docs/caddyfile) |
| fail2ban Manual | [https://www.fail2ban.org/wiki/index.php/MANUAL_0_8](https://www.fail2ban.org/wiki/index.php/MANUAL_0_8) |
| WordPress Plugin API | [https://developer.wordpress.org/plugins/hooks/](https://developer.wordpress.org/plugins/hooks/) |
| WordPress wp-config.php | [https://developer.wordpress.org/apis/wp-config-php/](https://developer.wordpress.org/apis/wp-config-php/) |
| OWASP Top 10 | [https://owasp.org/www-project-top-ten/](https://owasp.org/www-project-top-ten/) |
| WP-CLI Commands | [https://developer.wordpress.org/cli/commands/](https://developer.wordpress.org/cli/commands/) |

---

*VAPT Risk Catalogue Transformation System — Bundle v2.2.0 | 2026-02-21*

---

### v2.2 — Operation Notes & Manual Verification Protocol

Both fields added to every enforcer entry in every risk (210 entries in the pattern library, 209 in the interface schema). Previously absent entirely — the `note` and `verification.command` fields existed but were not the same thing.

**`operation_notes`** — structured object documenting:

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

**`manual_verification_protocol`** — ordered verification sequence:

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

All curl commands use fully qualified `https://yoursite.com` URLs. All dashboard links are fully qualified `https://` URLs.
