# How the AI Agent Should Use These Files
*Step 1 — Select Task Type*  
The agent supports four task types defined in ai_agent_instructions.json:

  1. generate_ui_component_schema — UI JSON for one risk
  2. generate_enforcement_code — Platform code for one risk + platform
  3. generate_full_risk_package — UI + all platforms for one risk
  4. generate_bulk_schema — All 125 risks (filterable)

*Step 2 — Schema-First Lookup*  
interface_schema.risk_interfaces[RISK-XXX]  
  → components[], layout, actions, severity, platform_implementations

*Step 3 — Pattern Library Lookup*
  - enforcer_pattern_library.patterns[RISK-XXX].cloudflare
  - enforcer_pattern_library.patterns[RISK-XXX].iis
  - enforcer_pattern_library.patterns[RISK-XXX].caddy

*Step 4 — Apply Enforcer Validation Gate*  
  - Check implementation_type  
  - If notes_only: output note + fallback secondary defense rule
  - If code type: output from the appropriate field

*Step 5 — Self-Check Rubric (Score ≥9/10 required)*
  |Check|Weight|
  |---|---|
  Component IDs match schema exactly|2
  Enforcement code from pattern library|2
  Severity badge colors correct|1
  Handler names follow conventions|1
  Platform in available_platforms|1
  VAPT block markers present|1
  Verification command present|1
  No forbidden patterns violated|1

*Enforcer Block Markers (Required in all code output)*  
Apache/.htaccess  
apache# BEGIN VAPT RISK-XXX  
<your code>  
# END VAPT RISK-XXX
IIS web.config
xml<!-- BEGIN VAPT RISK-XXX -->
<your code>
<!-- END VAPT RISK-XXX -->
Caddy
# BEGIN VAPT RISK-XXX
<your code>
# END VAPT RISK-XXX
Cloudflare (comment in rule description)
VAPT RISK-XXX: [title]
wp-config.php
php/* BEGIN VAPT RISK-XXX */
<your code>
/* END VAPT RISK-XXX */

Platform Verification Commands
PlatformValidateReloadTestApache/.htaccessapachectl -tservice apache2 reloadcurl -sI https://site.comIISappcmd validate configiisresetcurl -sI https://site.comCaddycaddy validatecaddy reloadcurl -sI https://site.comCloudflareDashboard reviewInstant on savecurl -sI https://site.comNginxnginx -tnginx -s reloadcurl -sI https://site.comfail2banfail2ban-client -tfail2ban-client reloadfail2ban-client status

Key Limitations & Notes
Cloudflare

Cannot enforce Options -Indexes — this must be set at origin server
Cannot set ServerTokens Prod — strip Server header via Transform Rule as complement
Rate limiting (fail2ban equivalents) requires Cloudflare Pro or higher
wp-config.php constants cannot be enforced from the edge

IIS

URL Rewrite Module is required — install via Web Platform Installer or IIS Manager
PHP engine flag (php_flag engine off) has no direct IIS equivalent; use requestFiltering to block PHP files in uploads
AllowOverride equivalent does not exist in IIS — all config is in web.config hierarchy

Caddy

Uses Caddyfile v2 syntax only — do not use v1 directives
Directory listing is disabled by default — no explicit directive needed unless browse was enabled
For sites using Caddy as reverse proxy to PHP-FPM, some rules apply to the upstream, not Caddy itself


Usage Example
Prompt to AI Agent:
Generate the full risk package for RISK-022 (Missing Content-Security-Policy Header)
for platforms: .htaccess, Cloudflare, IIS, Caddy
Agent Workflow:

Load interface_schema.risk_interfaces.RISK-022
Load enforcer_pattern_library.patterns.RISK-022 for all 4 platforms
Generate UI schema from components + layout
Output .htaccess: Header always set Content-Security-Policy "default-src 'self';..."
Output Cloudflare: Transform Rule → HTTP Response Header → Content-Security-Policy
Output IIS: <customHeaders><add name="Content-Security-Policy" value="..." />
Output Caddy: header { Content-Security-Policy "default-src 'self'..." }
Self-check all outputs → score 10/10 → deliver package
