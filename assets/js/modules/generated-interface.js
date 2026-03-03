// React Component to Render Generated Interfaces
// Version 3.0 - Global Driver & Probe Architecture
// Expects props: { feature, onUpdate }

(function () {
  const { createElement: el, useState, useEffect, useRef } = wp.element;
  const { Button, TextControl, ToggleControl, SelectControl, TextareaControl, Modal, Icon, Tooltip } = wp.components;
  const { __, sprintf } = wp.i18n;

  /**
   * Universal URL Resolver (v3.13.2)
   * Standardizes on vaptSecureSettings.homeUrl and detects absolute paths/URLs.
   */
  const resolveUrl = (path, configUrl, featureKey = '') => {
    const homeUrl = (window.vaptSecureSettings && window.vaptSecureSettings.homeUrl) ? window.vaptSecureSettings.homeUrl.replace(/\/$/, '') : window.location.origin;

    // 🛡️ Logic Refinement (v3.13.8): Context-Aware Specificity
    let base = homeUrl;
    let sub = path || '';

    // If path is root default but feature implies a specific target, nudge it (v3.13.8)
    if ((!path || path === '/') && !configUrl && featureKey) {
      if (featureKey.includes('cron') || featureKey === 'RISK-001') sub = 'wp-cron.php';
      else if (featureKey.includes('xmlrpc')) sub = 'xmlrpc.php';
      else if (featureKey.includes('login')) sub = 'wp-login.php';
    }

    if (configUrl) {
      if (configUrl.startsWith('http')) {
        const normalizedConfig = configUrl.replace(/\/$/, '');
        // If configUrl is just the root domain, AND we have a better sub, JOIN them.
        if (normalizedConfig === homeUrl && (sub && sub !== '/' && !sub.startsWith('http'))) {
          // sub is already set above or from path
        } else {
          return configUrl; // Absolute override
        }
      } else {
        sub = configUrl; // Relative override
      }
    } else if (path && path.startsWith('http')) {
      return path; // Path is already absolute
    }

    const normalizedPath = sub.startsWith('/') ? sub : '/' + sub;
    const result = base + (normalizedPath === '/' ? '' : normalizedPath);

    // Final Fallback: if result is still root but sub had content, force it
    if ((result === homeUrl || result === homeUrl + '/') && sub && sub.length > 1 && !sub.startsWith('http')) {
      return homeUrl + (sub.startsWith('/') ? sub : '/' + sub);
    }

    return result;
  };

  /**
   * Helper: Safely render a value that might be an object or a URL to linkify
   */
  const safeRender = (val) => {
    if (val === null || val === undefined) return '';
    if (typeof val === 'string') {
      // Linkify URLs in text
      const urlRegex = /(https?:\/\/[^\s]+)/g;
      const parts = val.split(urlRegex);
      if (parts.length > 1) {
        return parts.map((part, i) =>
          part.match(urlRegex)
            ? el('a', { key: i, href: part, target: '_blank', rel: 'noopener noreferrer', style: { color: '#2563eb', textDecoration: 'underline' } }, part)
            : part
        );
      }
      return val;
    }
    if (typeof val === 'number' || typeof val === 'boolean') return val.toString();
    if (typeof val === 'object') {
      if (val.label) return safeRender(val.label);
      if (val.message) return safeRender(val.message);
      if (val.content) return safeRender(val.content);
      return JSON.stringify(val);
    }
    return '';
  };

  /**
   * PROBE REGISTRY: Global Verification Handlers
   */
  const PROBE_REGISTRY = {
    // 1. Header Probe: Verifies HTTP response headers
    check_headers: async (siteUrl, control, featureData, featureKey) => {
      const url = resolveUrl('/', control.config?.url);
      const contextParam = (featureKey && (featureKey.includes('login') || featureKey.includes('brute'))) ? '&vaptsecure_test_context=login' : '';
      const resp = await fetch(url + '?vaptsecure_header_check=' + Date.now() + contextParam, { method: 'GET', cache: 'no-store' });
      const headers = {};
      resp.headers.forEach((v, k) => { headers[k] = v; });
      console.log("[VAPT] Full Response Headers:", headers);

      const vaptEnforced = resp.headers.get('x-vapt-enforced');
      const enforcedFeature = resp.headers.get('x-vapt-feature'); // Can be comma-separated

      let headerStr = '';
      const keepHeaders = ['strict-transport-security', 'x-vapt-enforced', 'x-frame-options', 'x-content-type-options', 'x-xss-protection', 'referrer-policy', 'permissions-policy', 'content-security-policy'];
      const isSuperAdmin = window.vaptSecureSettings && window.vaptSecureSettings.isSuper;

      for (const [k, v] of Object.entries(headers)) {
        if (k.toLowerCase() === 'x-vapt-feature' && !isSuperAdmin) continue; // Explicitly hide the feature list for non-superadmins (v3.13.19)
        if (keepHeaders.includes(k.toLowerCase()) || k.toLowerCase().startsWith('x-') || (isSuperAdmin && k.toLowerCase() === 'x-vapt-feature')) {
          headerStr += `\n${k}: ${v}`;
        }
      }

      if (vaptEnforced === 'php-headers' || vaptEnforced === 'htaccess') {
        if (featureKey && enforcedFeature) {
          const features = enforcedFeature.split(',').map(f => f.trim());
          if (!features.includes(featureKey)) {
            return { success: false, message: `Inconclusive: Headers are present, but this specific feature ('${featureKey}') is not listed in enforcement. Found: ${enforcedFeature}.`, raw: `URL: ${url} | Status: ${resp.status} | Expected: A+ Headers\n${headerStr.trim()}` };
          }
        }
        return { success: true, message: `Plugin is actively enforcing headers (${vaptEnforced}).`, raw: `URL: ${url} | Status: ${resp.status} | Expected: A+ Headers\n\n${headerStr.trim()}` };
      }

      return { success: false, message: `Security headers present, but NOT by this plugin. VAPT enforcement header missing.`, raw: `URL: ${url} | Status: ${resp.status} | Expected: A+ Headers\n\n${headerStr.trim()}` };
    },

    // 2. Batch Probe: Verifies Rate Limiting (Sends 125% of RPM) (v3.6.25 Sequential)
    spam_requests: async (siteUrl, control, featureData, featureKey, onProgress) => {
      try {
        let rpm = parseInt(control.numTests || featureData['rpm'] || featureData['rate_limit'], 10);

        // Dynamic Context Detection (v3.3.40 / v3.6.24 expanded)
        let contextParam = '';
        const loginKeywords = ['login', 'brute', 'auth', 'password', 'email', 'reset'];
        if (featureKey && loginKeywords.some(kw => featureKey.toLowerCase().includes(kw))) {
          contextParam = '&vaptsecure_test_context=login';
        }

        if (isNaN(rpm)) {
          const limitKey = Object.keys(featureData).find(k => k.includes('limit') || k.includes('max') || k.includes('rpm'));
          if (limitKey) rpm = parseInt(featureData[limitKey], 10);
        }

        // Fallback for custom strictness keywords (v3.6.25/26)
        if (isNaN(rpm)) {
          const val = control.numTests || featureData['rpm'] || featureData['rate_limit'];
          if (val === 'strict') rpm = 5;
          else if (val === 'moderate') rpm = 10;
          else if (val === 'permissive') rpm = 20;
        }

        if (isNaN(rpm)) rpm = 5;

        console.log(`[VAPT] spam_requests Debug: rpm=${rpm}, load=${Math.ceil(rpm * 1.25)}, data=`, featureData);
        if (isNaN(rpm) || rpm <= 0) {
          throw new Error('Invalid rate limit configuration. RPM must be a positive number.');
        }

        const load = Math.ceil(rpm * 1.25);
        if (load > 1000) {
          console.warn('[VAPT] Warning: Rate limit test sending more than 1000 requests. This may impact server performance.');
        }

        try {
          const resetRes = await fetch(siteUrl + '/wp-json/vaptsecure/v1/reset-limit', { method: 'POST', cache: 'no-store' });
          const resetJson = await resetRes.json();
          console.log('[VAPT] Rate limit reset debug:', resetJson);
        } catch (e) {
          console.warn('[VAPT] Failed to reset rate limit:', e);
        }

        const responses = [];
        const stats = {};
        let debugInfo = '';
        let lastCount = -1;
        let traceInfo = '';
        let hasVaptHeader = false;

        // Process sequentially for real-time reporting (v3.6.25)
        for (let i = 0; i < load; i++) {
          try {
            const url = resolveUrl('/', control.config?.url);
            const r = await fetch(url + '?vaptsecure_test_spike=' + i + contextParam, { cache: 'no-store' });
            const respData = { status: r.status, headers: r.headers };
            responses.push(respData);

            // Update stats
            stats[r.status] = (stats[r.status] || 0) + 1;
            if (r.headers.has('x-vapt-debug')) debugInfo = r.headers.get('x-vapt-debug');
            if (r.headers.has('x-vapt-count')) lastCount = r.headers.get('x-vapt-count');
            if (r.headers.has('x-vapt-trace')) traceInfo = r.headers.get('x-vapt-trace');
            if (r.headers.get('x-vapt-enforced') === 'php-rate-limit') hasVaptHeader = true;

            // Report progress every 2 requests or if blocked
            if (onProgress && (i % 2 === 0 || r.status === 429 || i === load - 1)) {
              onProgress({
                total: load,
                current: i + 1,
                accepted: stats[200] || 0,
                blocked: stats[429] || 0,
                errors: stats[500] || 0
              });

              // Always trigger monitor refresh for immediate feedback (v3.6.26/28)
              // Standardized to lowercase (v3.6.28)
              window.dispatchEvent(new CustomEvent('vapt-refresh-stats', { detail: { featureKey: featureKey.toLowerCase() } }));
            }
          } catch (err) {
            console.warn(`[VAPT] Request ${i} failed:`, err);
            stats[0] = (stats[0] || 0) + 1;
          }
        }

        const blocked = stats[429] || 0;
        const total = load;
        const successCount = stats[200] || 0;
        const errorCount = stats[500] || 0;
        const debugMsg = `(Debug: ${debugInfo || 'None'}, Count: ${lastCount}, Trace: ${traceInfo || 'None'})`;

        const resultMeta = {
          total: total,
          accepted: successCount,
          blocked: blocked,
          errors: errorCount,
          details: debugMsg
        };

        if (blocked > 0 && hasVaptHeader) {
          window.dispatchEvent(new CustomEvent('vapt-refresh-stats', { detail: { featureKey } }));
          return {
            success: true,
            message: `Rate limiter is ACTIVE. Security measures are working correctly.`,
            meta: resultMeta,
            raw: `URL: ${resolveUrl('/', control.config?.url)} | Status: 429 | Expected: 429`
          };
        }

        if (successCount > 0 || lastCount > 0) {
          window.dispatchEvent(new CustomEvent('vapt-refresh-stats', { detail: { featureKey } }));
        }

        if (errorCount > 0) {
          return {
            success: false,
            message: `Server Error (500). Internal configuration or logic error detected.`,
            meta: resultMeta,
            raw: `URL: ${resolveUrl('/', control.config?.url)} | Status: 500 | Expected: 429`
          };
        }

        return {
          success: false,
          message: `Rate Limiter is NOT active. Traffic was not restricted.`,
          meta: resultMeta,
          raw: `URL: ${resolveUrl('/', control.config?.url)} | Status: 200 | Expected: 429`
        };
      } catch (err) {
        return {
          success: false,
          message: `Test Error: ${err.message}. Rate limit test could not complete.`,
          raw: { error: err.message, stack: err.stack }
        };
      }
    },

    // 3. Status Probe: Verifies specific file block (e.g., XML-RPC)
    block_xmlrpc: async (siteUrl, control, featureData, featureKey) => {
      const url = resolveUrl('/xmlrpc.php', control.config?.url);
      const resp = await fetch(url, { method: 'POST', body: '<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName><params></params></methodCall>' });
      const vaptEnforced = resp.headers.get('x-vapt-enforced');
      const enforcedFeature = resp.headers.get('x-vapt-feature');

      if (vaptEnforced === 'php-xmlrpc') {
        if (featureKey && enforcedFeature && enforcedFeature !== featureKey) {
          return { success: false, message: `Inconclusive: XML-RPC is blocked by another VAPT feature ('${enforcedFeature}'). You must disable it there to verify this control independently.`, raw: `URL: ${url} | Status: ${resp.status} | Expected: 403` };
        }
        return { success: true, message: `Plugin is actively blocking XML-RPC (${vaptEnforced}).`, raw: `URL: ${url} | Status: ${resp.status} | Expected: 403` };
      }

      const isVulnerable = resp.status === 200;
      return {
        success: false,
        message: isVulnerable
          ? `SECURITY FAILURE: XML-RPC is OPEN and VULNERABLE (HTTP 200). Plugin enforcement is not working.`
          : `XML-RPC is blocked (HTTP ${resp.status}), but NOT by this plugin. VAPT enforcement header missing.`,
        raw: `URL: ${url} | Status: ${resp.status} | Expected: 403`
      };
    },

    // 4. Directory Probe: Verifies Indexing Block
    disable_directory_browsing: async (siteUrl, control, featureData, featureKey) => {
      const target = resolveUrl('/wp-content/uploads/', control.config?.url);
      const resp = await fetch(target, { cache: 'no-store' });
      const text = await resp.text();
      const snippet = text.substring(0, 500);
      const vaptEnforced = resp.headers.get('x-vapt-enforced');
      const enforcedFeature = resp.headers.get('x-vapt-feature');

      if (vaptEnforced === 'php-dir') {
        if (featureKey && enforcedFeature && enforcedFeature !== featureKey) {
          return { success: false, message: `Inconclusive: Directory browsing blocked by '${enforcedFeature}'.`, raw: `URL: ${target} | Status: ${resp.status}\n\n${snippet}` };
        }
        return { success: true, message: `PASS: Plugin is actively blocking directory listing (${vaptEnforced}).`, raw: `URL: ${target} | Status: ${resp.status}\n\n${snippet}` };
      }

      return { success: false, message: `Directory browsing blocked (HTTP ${resp.status}), but NOT by this plugin. VAPT enforcement header missing.`, raw: `URL: ${target} | Status: ${resp.status}\n\n${snippet}` };
    },

    // 5. Null Byte Probe (and aliases)
    inject_null_unicode: async (siteUrl, control, featureData) => {
      return PROBE_REGISTRY.block_null_byte_injection(siteUrl, control, featureData);
    },
    block_null_byte_injection: async (siteUrl, control, featureData) => {
      const target = resolveUrl('/', control.config?.url) + '?vaptsecure_test_param=safe&vaptsecure_attack=test%00payload';
      const resp = await fetch(target, { cache: 'no-store' });
      const vaptEnforced = resp.headers.get('x-vapt-enforced');

      if (vaptEnforced === 'php-null-byte' || resp.status === 400) {
        return { success: true, message: `PASS: Null Byte Injection Blocked (HTTP ${resp.status}). Enforcer: ${vaptEnforced || 'Server'}`, raw: `URL: ${target} | Status: ${resp.status} | Expected: 400 or 403` };
      }

      return { success: false, message: `FAIL: Null Byte Payload Accepted (HTTP ${resp.status}).`, raw: `URL: ${target} | Status: ${resp.status} | Expected: 400 or 403` };
    },

    // 6. Version Hide Probe
    hide_wp_version: async (siteUrl, control, featureData) => {
      const url = resolveUrl('/', control.config?.url);
      const resp = await fetch(url + '?vaptsecure_version_check=1', { method: 'GET', cache: 'no-store' });
      const text = await resp.text();
      const vaptEnforced = resp.headers.get('x-vapt-enforced');

      const hasGenerator = text.toLowerCase().includes('name="generator" content="wordpress');

      if (!hasGenerator) {
        return { success: true, message: `Secure: WordPress generator tag is hidden.`, raw: `URL: ${url} | Status: ${resp.status} | Expected: No generator tag` };
      }

      return { success: false, message: `Vulnerable: WordPress generator tag is present in the page source.`, raw: `URL: ${url} | Status: ${resp.status} | Expected: No generator tag` };
    },

    // 7. Universal Payload Probe (Dynamic Real-World Testing)
    universal_probe: async (siteUrl, control, featureData, featureKey) => {
      const config = control.test_config || {};
      const method = config.method || 'GET';
      const path = config.path || '/';
      const params = config.params || {};
      const headers = config.headers || {};
      const body = config.body || null;
      const expectedStatus = config.expected_status;
      const expectedText = config.expected_text;
      const expectedHeaders = config.expected_headers;

      let url = resolveUrl(path, config.url, featureKey);
      const contextParam = (featureKey && (featureKey.includes('login') || featureKey.includes('brute'))) ? 'vaptsecure_test_context=login' : '';

      if (method === 'GET') {
        const urlParams = new URLSearchParams(params);
        if (contextParam) urlParams.append('vaptsecure_test_context', 'login');
        const qs = urlParams.toString();
        if (qs) url = url + (url.includes('?') ? '&' : '?') + qs;
      } else if (contextParam) {
        url = url + (url.includes('?') ? '&' : '?') + contextParam;
      }

      const fetchOptions = {
        method: method,
        headers: headers,
        cache: 'no-store'
      };

      if (method !== 'GET' && body) {
        fetchOptions.body = typeof body === 'object' ? JSON.stringify(body) : body;
        if (typeof body === 'object' && !fetchOptions.headers['Content-Type']) {
          fetchOptions.headers['Content-Type'] = 'application/json';
        }
      } else if (method !== 'GET' && Object.keys(params).length > 0) {
        const formData = new URLSearchParams();
        for (const k in params) formData.append(k, params[k]);
        fetchOptions.body = formData;
        if (!fetchOptions.headers['Content-Type']) {
          fetchOptions.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
      }

      const resp = await fetch(url, fetchOptions);
      const text = await resp.text();

      let isSecure = false;
      let statusMatches = false;
      let headerMatches = false;
      const code = resp.status;

      let expectedStatusArray = [];
      if (expectedStatus) {
        expectedStatusArray = Array.isArray(expectedStatus)
          ? expectedStatus.map(s => parseInt(s))
          : [parseInt(expectedStatus)];
      }

      if (expectedStatusArray.length > 0) {
        statusMatches = expectedStatusArray.includes(code);
      }

      if (expectedHeaders && typeof expectedHeaders === 'object') {
        headerMatches = true;
        const responseHeaders = {};
        resp.headers.forEach((v, k) => { responseHeaders[k.toLowerCase()] = v; });

        for (const [key, expectedValue] of Object.entries(expectedHeaders)) {
          const actualValue = responseHeaders[key.toLowerCase()];
          // Support multi-value OR logic with | separator (v3.13.17)
          let expectedValueTransformed = expectedValue;

          // Global Platform Normalization: Alias 'htaccess' or 'nginx' to allow fallbacks (v3.13.18)
          // This prevents false failures on Nginx/PHP environments for legacy probes.
          if (key.toLowerCase() === 'x-vapt-enforced' && (expectedValue === 'htaccess' || expectedValue === 'nginx')) {
            expectedValueTransformed = 'htaccess|nginx|php-headers';
          }

          const expectedOptions = expectedValueTransformed.split('|').map(v => v.trim().toLowerCase());
          if (!actualValue || !expectedOptions.includes(actualValue.toLowerCase())) {
            headerMatches = false;
            break;
          }
        }
      }

      const expectsBlock = expectedStatusArray.length > 0 && expectedStatusArray.every(s => s >= 400);
      const expectsAllow = expectedStatusArray.includes(200);
      const hasHeaderCheck = expectedHeaders && typeof expectedHeaders === 'object';
      const enforcedFeature = resp.headers.get('x-vapt-feature');

      if (hasHeaderCheck) {
        isSecure = headerMatches && (code === 200 || expectsAllow || statusMatches);
      } else if (expectsBlock) {
        // Allow 404 and 400 as valid "Blocks" (Global Security Best Practice & REST API Protection)
        const is404Acceptable = code === 404;
        const is400Acceptable = code === 400; // v3.12.21: REST API blocks often return 400
        isSecure = (statusMatches || is404Acceptable || is400Acceptable) && code >= 400;
      } else if (expectsAllow) {
        isSecure = code === 200 && (expectedText ? text.includes(expectedText) : true);
      } else if (statusMatches) {
        isSecure = true;
      } else {
        isSecure = code >= 400;
      }

      // Helper to resolve feature aliases (v3.6.20)
      const areFeaturesEquivalent = (f1, f2) => {
        if (!f1 || !f2) return false;
        if (f1 === f2) return true;

        // Normalize known aliases
        const aliases = {
          'user-enumeration': ['username-enumeration-via-wordpress-rest-api', 'block-user-enumeration'],
          'username-enumeration-via-wordpress-rest-api': ['user-enumeration', 'block-user-enumeration'],
          'xmlrpc': ['block-xmlrpc', 'disable-xmlrpc'],
          'block-xmlrpc': ['xmlrpc', 'disable-xmlrpc']
        };

        return (aliases[f1] && aliases[f1].includes(f2));
      };


      if (isSecure && expectsBlock && featureKey && enforcedFeature && !areFeaturesEquivalent(enforcedFeature, featureKey)) {
        isSecure = false;
        return {
          success: false,
          message: `Inconclusive: Request blocked by overlapping feature '${enforcedFeature}'. Disable it to verify this control.`,
          raw: `URL: ${url} | Status: ${code} | Enforcer: ${enforcedFeature} vs ${featureKey}`
        };
      }

      let message = '';
      if (isSecure) {
        if (hasHeaderCheck && headerMatches) {
          message = `Protection Headers Present (HTTP ${code}). All expected headers verified.`;
        } else if (expectsBlock && statusMatches) {
          message = `Attack Blocked (HTTP ${code}). Expected block code (${expectedStatus}).`;
        } else if (expectsBlock && code === 404) {
          message = `Attack Blocked (HTTP 404). Resource hidden successfully (Expected ${expectedStatus}).`;
        } else if (expectsBlock && code === 400) {
          message = `Attack Blocked (HTTP 400). Request rejected as malformed/invalid (Expected ${expectedStatus}).`;
        } else if (expectsAllow && code === 200) {
          message = `Normal Response (HTTP ${code}) with protection indicators.`;
        } else {
          message = `Expected Response Received (HTTP ${code}).`;
        }
      } else {
        if (code === 200 && expectsBlock) {
          message = `Attack Accepted (HTTP 200). Expected Block (${expectedStatus}).`;
        } else if (hasHeaderCheck && !headerMatches) {
          if (expectsBlock && statusMatches) {
            isSecure = true;
            message = `PASS: Request was blocked (HTTP ${code}). Note: VAPT enforcement header is missing, indicating a Server-Level block (e.g., .htaccess or Firewall) instead of PHP.`;
          } else {
            // New logic: Check if it's actually missing or just mismatching
            const vaptEnforced = resp.headers.get('x-vapt-enforced');
            if (vaptEnforced) {
              message = `Header Mismatch (HTTP ${code}). VAPT is active (${vaptEnforced}) but headers do not match expected values.`;
            } else {
              message = `Missing Protection Headers (HTTP ${code}). Verification failed.`;
            }
          }
        } else if (statusMatches === false && expectedStatus) {
          message = `Mismatch: Got HTTP ${code}, expected ${expectedStatus}.`;
        } else {
          message = `Unexpected Response (HTTP ${code}). Could not verify security.`;
        }
      }

      return {
        success: isSecure,
        message: message,
        raw: `URL: ${url} | Status: ${code} | Expected: ${expectedStatus || 'N/A'}`
      };
    },

    // 8. Default Generic Probe
    default: async (siteUrl, control) => {
      const resp = await fetch(siteUrl + '?vaptsecure_ping=1');
      return { success: resp.ok, message: `Probe result: HTTP ${resp.status}`, raw: `URL: ${siteUrl} | Status: ${resp.status} | Time: ${new Date().toISOString()}` };
    }
  };

  /*
   * Evidence Gallery Component (v3.5.2)
   * Handles multiple screenshot rendering with modal preview
   */
  const EvidenceGallery = ({ screenshots }) => {
    const [selectedImage, setSelectedImage] = useState(null);

    if (!screenshots || !Array.isArray(screenshots) || screenshots.length === 0) return null;

    return el('div', { className: 'vapt-evidence-gallery', style: { marginTop: '10px' } }, [
      el('div', { style: { fontSize: '11px', fontWeight: '700', textTransform: 'uppercase', color: '#64748b', marginBottom: '6px' } },
        sprintf(__('%d Evidence Captured', 'vaptsecure'), screenshots.length)
      ),
      el('div', {
        style: {
          display: 'flex',
          gap: '8px',
          overflowX: 'auto',
          padding: '4px',
          background: '#f1f5f9',
          borderRadius: '4px',
          border: '1px solid #e2e8f0'
        }
      }, screenshots.map((url, i) =>
        el('div', {
          key: i,
          onClick: () => setSelectedImage(url),
          style: {
            width: '60px',
            height: '60px',
            flexShrink: 0,
            cursor: 'pointer',
            backgroundImage: `url(${url})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            borderRadius: '3px',
            border: '1px solid #cbd5e1',
            position: 'relative'
          }
        }, el(Icon, { icon: 'search', size: 12, style: { position: 'absolute', bottom: '2px', right: '2px', background: 'rgba(255,255,255,0.8)', borderRadius: '50%', padding: '2px' } }))
      )),

      selectedImage && el(Modal, {
        title: __('Evidence Detail', 'vaptsecure'),
        onRequestClose: () => setSelectedImage(null),
        style: { maxWidth: '90vw', maxHeight: '90vh' }
      }, [
        el('div', { style: { display: 'flex', justifyContent: 'center', background: '#000', borderRadius: '4px', overflow: 'hidden' } },
          el('img', { src: selectedImage, style: { maxWidth: '100%', maxHeight: '70vh' } })
        ),
        el('div', { style: { marginTop: '15px', textAlign: 'right' } },
          el(Button, { isPrimary: true, onClick: () => setSelectedImage(null) }, __('Close', 'vaptsecure'))
        )
      ])
    ]);
  };

  /*
   * File Inspector Component (v3.13.15)
   * Specialized rendering for file contents/directory listings
   */
  const FileInspector = ({ content, label = __('Verification Trace', 'vaptsecure'), testContext = '' }) => {
    if (!content) return null;

    // 🛡️ Robust Type Handling (v3.13.15)
    let displayContent = content;
    if (typeof content === 'object') {
      try {
        displayContent = JSON.stringify(content, null, 2);
      } catch (e) {
        displayContent = String(content);
      }
    }

    // Auto-detect if content implies a directory listing
    const isDir = typeof displayContent === 'string' && (displayContent.includes('Index of /') || displayContent.includes('Parent Directory'));
    const isTrace = label === __('Verification Trace', 'vaptsecure');
    const displayLabel = isDir ? __('Directory Listing Exposed', 'vaptsecure') : label;
    const resolvedIcon = isTrace ? 'info' : 'media-code';

    return (isTrace && typeof displayContent === 'string' && displayContent.startsWith('URL: ')) ? el('div', { className: 'vapt-file-inspector', style: { marginTop: '10px' } }, [
      el(Tooltip, {
        text: el('div', { style: { textAlign: 'left', maxWidth: '300px' } }, [
          testContext ? el('div', { style: { marginBottom: '8px', lineHeight: '1.4' } }, testContext) : null,
          el('div', { style: { color: '#94a3b8', whiteSpace: 'pre-wrap' } }, displayContent.split(' | ').slice(1).join(' | '))
        ]), placement: 'top'
      },
        el('span', { style: { fontSize: '10px', fontWeight: '700', textTransform: 'uppercase', cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: '5px' } }, [
          el('span', { style: { color: '#3b82f6', display: 'flex', alignItems: 'center' } }, el(Icon, { icon: 'info', size: 14 })),
          el('span', { style: { color: '#94a3b8' } }, displayLabel)
        ])
      )
    ]) : el('div', { className: 'vapt-file-inspector', style: { marginTop: '10px', display: 'flex', flexDirection: 'column' } }, [
      el('div', { style: { fontSize: '10px', fontWeight: '700', textTransform: 'uppercase', color: '#94a3b8', marginBottom: '4px', display: 'flex', alignItems: 'center', gap: '5px' } }, [
        el(Icon, { icon: resolvedIcon, size: 14 }),
        displayLabel
      ]),
      typeof displayContent === 'string' && displayContent.startsWith('URL: ') ? el('div', {
        style: {
          fontSize: '11px',
          background: '#f8fafc',
          border: '1px solid #e2e8f0',
          borderRadius: '4px',
          padding: '10px',
          color: '#334155',
          fontFamily: 'monospace',
          textAlign: 'left'
        }
      }, displayContent.split(/(https?:\/\/[^\s]+)/g).map((part, i) =>
        part.match(/^https?:\/\//)
          ? el('a', { key: i, href: part, target: '_blank', rel: 'noopener noreferrer', style: { color: '#2563eb', textDecoration: 'underline', fontWeight: 'bold' } }, part)
          : el('span', { key: i }, part)
      )) : el('pre', {
        style: {
          fontSize: '10px',
          fontFamily: 'monospace',
          background: '#fff',
          border: '1px solid #e2e8f0',
          borderRadius: '4px',
          padding: '10px',
          maxHeight: '200px',
          overflow: 'auto',
          whiteSpace: 'pre-wrap',
          color: '#334155'
        }
      }, typeof displayContent === 'string' ? displayContent.split(/(https?:\/\/[^\s]+)/g).map((part, i) =>
        part.match(/^https?:\/\//)
          ? el('a', { key: i, href: part, target: '_blank', rel: 'noopener noreferrer', style: { color: '#2563eb', textDecoration: 'underline' } }, part)
          : part
      ) : displayContent)
    ]);
  };

  /*
   * Sync/Async Toggle Component (v3.5.2)
   * Stateful toggle for execution mode
   */
  const SyncAsyncToggle = ({ isAsync, onChange, disabled }) => {
    return el('div', {
      className: 'vapt-sync-async-toggle',
      style: { display: 'flex', alignItems: 'center', gap: '6px' }
    }, [
      el(Tooltip, { text: isAsync ? __('Async: Runs via background process (Simulated)', 'vaptsecure') : __('Sync: Runs directly in browser session', 'vaptsecure') },
        el(Button, {
          isSmall: true,
          variant: 'tertiary',
          onClick: () => !disabled && onChange(!isAsync),
          disabled: disabled,
          style: {
            fontSize: '10px',
            padding: '0 4px',
            height: '20px',
            minHeight: '20px',
            color: isAsync ? '#7c3aed' : '#0f172a',
            borderColor: isAsync ? '#ddd6fe' : '#e2e8f0',
            background: isAsync ? '#f5f3ff' : '#f8fafc'
          }
        }, [
          el(Icon, { icon: isAsync ? 'update' : 'controls-repeat', size: 12, style: { marginRight: '4px' } }),
          isAsync ? 'ASYNC PROCESSED' : 'SYNC'
        ])
      )
    ]);
  };

  const TestRunnerControl = ({ control, featureData, featureKey }) => {
    const [status, setStatus] = useState('idle');
    const [result, setResult] = useState(null);
    const [progress, setProgress] = useState(null);
    const [numTests, setNumTests] = useState(''); // Custom test count (v3.6.26)
    // Stateful toggle (Default false/Sync)
    const [isAsync, setIsAsync] = useState(false);

    const runTest = async () => {
      setStatus('running');
      setResult(null);

      const { test_logic } = control;
      const siteUrl = window.location.origin;
      const handler = PROBE_REGISTRY[test_logic] || PROBE_REGISTRY['default'];

      try {
        const timeoutPromise = new Promise((_, reject) =>
          setTimeout(() => reject(new Error('Test timeout after 120 seconds')), 120000)
        );
        // Pass isAsync and progress callback to handler (v3.6.25)
        // Also pass custom numTests (v3.6.26)
        const handlerPromise = handler(siteUrl, { ...control, isAsync, numTests }, featureData, featureKey, (p) => {
          setProgress(p);
        });
        const res = await Promise.race([handlerPromise, timeoutPromise]);

        if (res && typeof res === 'object') {
          setStatus(res.success ? 'success' : 'error');
          setResult(res);
        } else {
          throw new Error('Invalid test result format');
        }
      } catch (err) {
        setStatus('error');
        setResult({ success: false, message: `Error: ${err.message}` });
      }
    };

    const handleClick = () => {
      runTest();
    };

    let rpmValue = parseInt(featureData['rpm'] || featureData['rate_limit'], 10);
    if (isNaN(rpmValue)) {
      const limitKey = Object.keys(featureData).find(k => k.includes('limit') || k.includes('max') || k.includes('rpm'));
      if (limitKey) rpmValue = parseInt(featureData[limitKey], 10);
    }
    if (isNaN(rpmValue)) rpmValue = 5;

    const currentRPM = parseInt(numTests || rpmValue, 10);
    const loadValue = Math.ceil(currentRPM * 1.25);
    const displayLabel = control.test_logic === 'spam_requests'
      ? control.label.replace(/\(\s*\d+.*\)/g, '').trim() + ` (${loadValue} requests)`
      : control.label;

    return el('div', { className: 'vapt-test-runner', style: { padding: '15px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '6px', marginBottom: '10px' } }, [
      el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2px' } }, [
        el('div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } }, [
          el('strong', { style: { fontSize: '12px', color: '#334155' } }, displayLabel),
          // Inject Sync/Async Toggle
          el(SyncAsyncToggle, { isAsync, onChange: setIsAsync, disabled: status === 'running' })
        ]),
        el(Button, { isSecondary: true, isSmall: true, isBusy: status === 'running', onClick: handleClick, disabled: status === 'running' }, 'Run Verify')
      ]),
      control.help && el('p', { style: { margin: '2px 0 0', fontSize: '11px', color: '#64748b', opacity: 0.8 } }, control.help),

      // Real-time Progress Bar (v3.6.25)
      status === 'running' && progress && el('div', { style: { marginTop: '10px', background: '#e2e8f0', borderRadius: '4px', height: '4px', overflow: 'hidden' } }, [
        el('div', { style: { background: '#2563eb', width: `${(progress.current / progress.total) * 100}%`, height: '100%', transition: 'width 0.3s' } })
      ]),
      status === 'running' && progress && el('div', { style: { display: 'flex', justifyContent: 'space-between', marginTop: '4px', fontSize: '10px', color: '#64748b' } }, [
        el('span', null, sprintf(__('Testing: %d/%d requests...', 'vaptsecure'), progress.current, progress.total)),
        el('div', { style: { display: 'flex', gap: '8px' } }, [
          el('span', { style: { color: '#10b981' } }, `${progress.accepted} Accepted`),
          el('span', { style: { color: progress.blocked > 0 ? '#ef4444' : '#64748b' } }, `${progress.blocked} Blocked`)
        ])
      ]),

      // Custom Test Count Input (v3.6.26/28)
      // Visible whenever NOT running (v3.6.28)
      status !== 'running' && control.test_logic === 'spam_requests' && el('div', { style: { marginTop: '10px', display: 'flex', alignItems: 'center', gap: '10px' } }, [
        el('div', { style: { flex: 1 } }, [
          el(TextControl, {
            label: __('Number of Tests to Run', 'vaptsecure'),
            value: numTests,
            type: 'number',
            placeholder: sprintf(__('Default: %d', 'vaptsecure'), rpmValue),
            onChange: (val) => setNumTests(val),
            style: { marginBottom: 0 }
          })
        ]),
        el('div', { style: { fontSize: '11px', color: '#64748b', marginTop: '20px' } },
          numTests ? sprintf(__('Target: %d requests', 'vaptsecure'), Math.ceil(parseInt(numTests) * 1.25)) : ''
        )
      ]),

      status !== 'idle' && status !== 'running' && result && el('div', {
        style: {
          marginTop: '10px',
          padding: '12px',
          background: status === 'success' ? '#f0fdf4' : '#fef2f2',
          border: `1px solid ${status === 'success' ? '#bbf7d0' : '#fecaca'}`,
          borderRadius: '6px',
          fontSize: '13px',
          color: status === 'success' ? '#166534' : '#991b1b'
        }
      }, [
        el('div', { style: { fontWeight: '700', marginBottom: '8px' } }, status === 'success' ? '✅ SUCCESS' : '❌ FAILURE'),
        el('div', { style: { marginBottom: '12px', wordBreak: 'break-all', borderLeft: '4px solid #3b82f6', paddingLeft: '12px' } }, [
          el('div', { style: { marginBottom: '8px' } }, result.message),
          (typeof result.raw === 'string' && result.raw.startsWith('URL: ')) ? el('div', { style: { fontSize: '11px', marginTop: '8px', opacity: 0.9 } }, [
            el('strong', null, 'URL: '),
            el('a', { href: result.raw.split(' | ')[0].replace('URL:', '').trim(), target: '_blank', rel: 'noopener noreferrer', style: { color: 'inherit', textDecoration: 'underline', fontWeight: 'bold' } }, result.raw.split(' | ')[0].replace('URL:', '').trim())
          ]) : null
        ])
      ]),

      // v3.5.2: Multiple Evidence Gallery Renderer (v3.13.16 Safety Fix)
      result && (result.screenshot_paths || (result.meta && result.meta.screenshot_paths)) &&
      el(EvidenceGallery, { screenshots: result.screenshot_paths || (result.meta ? result.meta.screenshot_paths : []) }),

      // v3.5.2: Specialized File Inspector for Directory/Raw Content
      result && (result.raw) &&
      el(FileInspector, { content: result.raw, testContext: control.description || control.help }),

      result && result.meta && !result.meta.screenshot_paths && el('div', { style: { background: 'rgba(255,255,255,0.5)', padding: '10px', borderRadius: '4px', border: '1px solid rgba(0,0,0,0.05)' } }, [
        el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.05em' } }, [
          el('div', { style: { color: '#059669' } }, [__('Accepted: '), el('strong', null, result.meta.accepted)]),
          el('div', { style: { color: '#dc2626' } }, [__('Blocked (429): '), el('strong', null, result.meta.blocked)]),
          el('div', { style: { color: '#4b5563' } }, [__('Errors: '), el('strong', null, result.meta.errors)]),
          el('div', { style: { color: '#4b5563' } }, [__('Total: '), el('strong', null, result.meta.total)])
        ]),
        // v3.12.20: Linkify Debug Details
        el('div', { style: { marginTop: '8px', fontSize: '10px', opacity: 0.7, fontFamily: 'monospace' } },
          result.meta.details.split(/(https?:\/\/[^\s]+)/g).map((part, i) =>
            part.match(/^https?:\/\//)
              ? el('a', { key: i, href: part, target: '_blank', rel: 'noopener noreferrer', style: { color: '#2563eb', textDecoration: 'underline' } }, part)
              : part
          )
        )
      ])
    ]);
  };

  /**
   * Rate Limit Observability Monitor
   */
  const RateLimitMonitor = ({ featureKey }) => {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(false);
    const [resetting, setResetting] = useState(false);

    const fetchStats = async () => {
      setLoading(true);
      try {
        const response = await fetch(`${window.vaptSecureSettings.root}vaptsecure/v1/features/${featureKey}/stats`, {
          headers: { 'X-WP-Nonce': window.vaptSecureSettings.nonce }
        });
        const data = await response.json();
        setStats(data);
      } catch (e) {
        console.error('[VAPT] Failed to fetch stats:', e);
      } finally {
        setLoading(false);
      }
    };

    const resetStats = async () => {
      if (!confirm(__('Are you sure you want to reset all active rate limit blocks for this feature?', 'vaptsecure'))) return;
      setResetting(true);
      try {
        await fetch(`${window.vaptSecureSettings.root}vaptsecure/v1/features/${featureKey}/reset`, {
          method: 'POST',
          headers: { 'X-WP-Nonce': window.vaptSecureSettings.nonce }
        });
        await fetchStats();
      } catch (e) {
        console.error('[VAPT] Failed to reset stats:', e);
      } finally {
        setResetting(false);
      }
    };

    useEffect(() => {
      fetchStats();
      const interval = setInterval(fetchStats, 10000); // Poll every 10s

      // Listen for sync events (v3.6.24)
      const handleSync = (e) => {
        if (e.detail && (e.detail.featureKey === featureKey || e.detail.featureKey === featureKey.toLowerCase())) {
          fetchStats();
        }
      };
      window.addEventListener('vapt-refresh-stats', handleSync);

      return () => {
        clearInterval(interval);
        window.removeEventListener('vapt-refresh-stats', handleSync);
      };
    }, [featureKey]);

    if (!stats) return null;

    return el('div', {
      className: 'vapt-rate-limit-monitor',
      style: {
        padding: '12px',
        background: '#f1f5f9',
        border: '1px solid #cbd5e1',
        borderRadius: '6px',
        marginBottom: '20px',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center'
      }
    }, [
      el('div', { style: { display: 'flex', gap: '20px' } }, [
        el('div', null, [
          el('div', { style: { fontSize: '10px', textTransform: 'uppercase', color: '#64748b', fontWeight: '700' } }, __('Active Blocks (IPs)', 'vaptsecure')),
          el('div', { style: { fontSize: '18px', fontWeight: '800', color: stats.active_ips > 0 ? '#ef4444' : '#10b981' } }, stats.active_ips)
        ])
        // Total Attempts removed as requested (redundant with Verification results - v3.6.24)
      ]),
      el('div', null, [
        el(Button, {
          isSecondary: true,
          isSmall: true,
          isDestructive: true,
          onClick: resetStats,
          isBusy: resetting,
          disabled: resetting || stats.active_ips === 0,
          style: { height: '32px' }
        }, __('Reset Counter', 'vaptsecure'))
      ])
    ]);
  };

  const GeneratedInterface = ({ feature, onUpdate, isGuidePanel = false, hideMonitor = false, hideOpNotes = false, hideProtocol = false }) => {
    console.log('[VAPT] GeneratedInterface Render:', { key: feature?.key, controls: feature?.generated_schema?.controls, isGuidePanel });
    let schema = feature.generated_schema ? (typeof feature.generated_schema === 'string' ? JSON.parse(feature.generated_schema) : feature.generated_schema) : {};

    // 🛡️ Resilience: Auto-Convert Legacy "manual" type (v3.6.15)
    if (schema && schema.type === 'manual') {
      schema = {
        controls: [
          { type: 'header', label: __('Implementation Status', 'vaptsecure') },
          { type: 'toggle', label: __('Enable Feature', 'vaptsecure'), key: 'feat_enabled', default: true },
          { type: 'info', label: __('Manual Implementation Required', 'vaptsecure'), content: schema.instruction || __('Please refer to the manual verification protocol.', 'vaptsecure') }
        ],
        enforcement: { driver: 'manual', mappings: {} },
        _instructions: schema.instruction
      };
    }

    const currentData = feature.implementation_data ? (typeof feature.implementation_data === 'string' ? JSON.parse(feature.implementation_data) : feature.implementation_data) : {};
    const [localAlert, setLocalAlert] = useState(null);
    const [statusMap, setStatusMap] = useState({});
    const timeoutsRef = useRef({});

    if (!schema || !schema.controls || !Array.isArray(schema.controls)) {
      return el('div', { style: { padding: '20px', textAlign: 'center', color: '#999', fontStyle: 'italic' } },
        __('No functional controls defined for this implementation.', 'vaptsecure')
      );
    }

    // Identify if this is a Rate Limiting feature
    const isRateLimit = feature.key?.includes('limit') || feature.key?.includes('brute') ||
      (schema.enforcement?.mappings && Object.values(schema.enforcement.mappings).includes('limit_login_attempts'));


    const handleChange = (key, value) => {
      const updated = { ...currentData, [key]: value };
      if (onUpdate) onUpdate(updated);
    };

    const toBool = (val) => {
      if (val === true || val === 1 || val === '1' || val === 'true' || val === 'on') return true;
      return false;
    };

    const isRemovalContext = (key, currentVal) => {
      const status = statusMap[key];
      if (!status) return false;
      return toBool(currentVal) && (status.message === __("Removing...", "vaptsecure") || status.message === __("Removed Successfully", "vaptsecure"));
    };

    const renderControl = (control, index) => {
      const { type, label, key, help, options, rows, action } = control;
      const value = currentData[key] !== undefined ? currentData[key] : (control.default || '');
      const uniqueKey = key || `ctrl-${index}`;
      const isEnforced = feature.is_enforced === true || feature.is_enforced === 1 || feature.is_enforced === '1';
      // const conditionalTypes = ['info', 'html', 'warning', 'alert'];

      // if (conditionalTypes.includes(type) && isEnforced) return null; // Removed per user request v3.3.9

      switch (type) {
        case 'test_action':
          return el(TestRunnerControl, { key: uniqueKey, control, featureData: currentData, featureKey: feature.key || feature.id });

        case 'button':
          return el('div', { key: uniqueKey, style: { marginBottom: '15px' } }, [
            el(Button, {
              isSecondary: true,
              onClick: () => {
                if (action === 'reset_validation_logs') setLocalAlert({ message: __('Reset signal sent.', 'vaptsecure'), type: 'success' });
              }
            }, safeRender(label)),
            help && el('p', { style: { margin: '5px 0 0', fontSize: '12px', color: '#666' } }, safeRender(help))
          ]);

        case 'toggle':
          const mapping = (schema.enforcement?.mappings || {})[key];
          const isDevelop = (feature.status || '').toLowerCase() === 'develop' || (feature.normalized_status || '').toLowerCase() === 'develop';
          const isSuperAdmin = window.vaptSecureSettings?.isSuper || false;

          return el('div', { key: uniqueKey, style: { marginBottom: '15px' } }, [
            el(ToggleControl, {
              label: el('div', { style: { display: 'flex', alignItems: 'center', gap: '6px' } }, [
                el('strong', { style: { fontSize: '12px', color: '#334155' } }, safeRender(label)),
                el(Tooltip, {
                  text: el('div', { style: { padding: '8px', maxWidth: '300px' } }, [
                    el('div', { style: { fontWeight: '700', marginBottom: '4px', fontSize: '11px' } }, __('Technical Preview', 'vaptsecure')),
                    el('div', { style: { fontSize: '10px', color: '#64748b', marginBottom: '8px' } },
                      sprintf(__('Target: %s', 'vaptsecure'), (schema.enforcement?.driver === 'htaccess' ? '.htaccess' : (schema.enforcement?.target || 'root')))
                    ),
                    mapping ? el('pre', { style: { margin: 0, fontSize: '9px', background: '#1e293b', color: '#f8fafc', padding: '6px', borderRadius: '4px', overflowX: 'auto' } }, mapping) : el('em', null, __('No code mapping defined.', 'vaptsecure'))
                  ])
                }, el(Icon, { icon: 'info-outline', size: 14, style: { color: '#94a3b8', cursor: 'help' } }))
              ]),
              help: safeRender(control.description || help),
              checked: toBool(value),
              onChange: (val) => {
                const isRemoval = toBool(value) && !val;
                const progressMsg = isRemoval ? __("Removing...", "vaptsecure") : __("Applying...", "vaptsecure");
                const successMsg = isRemoval ? __("Removed Successfully", "vaptsecure") : __("Code Injected Successfully", "vaptsecure");

                if (timeoutsRef.current[key]) {
                  timeoutsRef.current[key].forEach(clearTimeout);
                }
                timeoutsRef.current[key] = [];

                setStatusMap(prev => ({ ...prev, [key]: { message: progressMsg, type: "info" } }));
                handleChange(key, val);

                const t1 = setTimeout(() => {
                  setStatusMap(prev => ({ ...prev, [key]: { message: successMsg, type: "success" } }));
                  const t2 = setTimeout(() => {
                    setStatusMap(prev => {
                      const nu = { ...prev };
                      delete nu[key];
                      return nu;
                    });
                    delete timeoutsRef.current[key];
                  }, 2000);
                  if (timeoutsRef.current[key]) timeoutsRef.current[key].push(t2);
                }, 600);
                timeoutsRef.current[key].push(t1);
              }
            }),
            // 🛡️ Localized Status Pill (v3.13.12)
            statusMap[key] && el('div', {
              style: {
                marginTop: '-8px',
                marginBottom: '8px',
                marginLeft: '35px',
                display: 'flex'
              }
            }, el('span', {
              style: {
                fontSize: '10px',
                fontWeight: '600',
                padding: '2px 8px',
                borderRadius: '12px',
                background: statusMap[key].type === 'success' ? '#ecfdf5' : (isRemovalContext(key, value) ? '#fef2f2' : '#f0f9ff'),
                color: statusMap[key].type === 'success' ? '#059669' : (isRemovalContext(key, value) ? '#b91c1c' : '#0369a1'),
                border: `1px solid ${statusMap[key].type === 'success' ? '#10b981' : (isRemovalContext(key, value) ? '#f87171' : '#0ea5e9')}`,
                boxShadow: '0 1px 2px rgba(0,0,0,0.05)',
                display: 'flex',
                alignItems: 'center',
                gap: '4px'
              }
            }, [
              el(Icon, { icon: statusMap[key].type === 'success' ? 'yes' : 'update', size: 12 }),
              statusMap[key].message
            ])),
            // 🛡️ Visual Indicator for Code Addition (v3.13.15 Enhanced)
            toBool(value) && (mapping && typeof mapping === 'string') && el(Tooltip, {
              text: el('div', { style: { padding: '5px', maxHeight: '300px', overflow: 'auto' } }, [
                el('div', { style: { fontWeight: '700', marginBottom: '5px', fontSize: '10px', textTransform: 'uppercase' } }, __('Injected Protections', 'vaptsecure')),
                el('pre', { style: { margin: 0, fontSize: '10px', background: '#1e293b', color: '#f8fafc', padding: '8px', borderRadius: '4px' } }, mapping)
              ])
            }, el('div', {
              style: {
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px',
                padding: '2px 8px',
                background: '#ecfdf5',
                color: '#059669',
                borderRadius: '12px',
                fontSize: '10px',
                fontWeight: '600',
                marginTop: '-8px',
                marginBottom: '8px',
                marginLeft: '35px',
                border: '1px solid #10b981',
                boxShadow: '0 1px 2px rgba(0,0,0,0.05)',
                cursor: 'help'
              }
            }, [
              el(Icon, { icon: 'editor-code', size: 12 }),
              sprintf(__('Code Injected to %s', 'vaptsecure'), (schema.enforcement?.driver === 'htaccess' ? '.htaccess' : (schema.enforcement?.target || 'root')))
            ])),
          ]);

        case 'input':
          return el('div', { key: uniqueKey, style: { marginBottom: '15px', padding: '10px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '4px' } }, [
            el(TextControl, {
              label: el('strong', null, safeRender(label)),
              help: safeRender(help),
              value: value ? value.toString() : '',
              onChange: (val) => handleChange(key, val),
              __nextHasNoMarginBottom: true,
              __next40pxDefaultSize: true
            })
          ]);

        case 'select':
          return el(SelectControl, {
            key: uniqueKey,
            label: safeRender(label),
            help: safeRender(help),
            value: value,
            options: (options || []).map(o => ({ label: safeRender(o.label || o), value: o.value !== undefined ? o.value : o })),
            onChange: (val) => handleChange(key, val)
          });

        case 'textarea':
        case 'code':
          return el('div', { key: uniqueKey, style: { marginBottom: '10px' } }, [
            el('div', { style: { display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '4px' } }, [
              el('label', { style: { fontSize: '12px', fontWeight: '600', color: '#334155' } }, safeRender(label)),
              help && el(Tooltip, { text: safeRender(help) }, el(Icon, { icon: 'info-outline', size: 14, style: { color: '#94a3b8', cursor: 'help' } }))
            ]),
            el(TextareaControl, {
              value: value,
              rows: rows || (type === 'code' ? 4 : 3),
              onChange: (val) => handleChange(key, val),
              placeholder: value ? '' : __('No data available.', 'vaptsecure'),
              __nextHasNoMarginBottom: true,
              style: type === 'code' ? { fontFamily: 'monospace', fontSize: '11px', background: '#f8fafc' } : { fontSize: '12px' }
            })
          ]);

        case 'header':
          return el('h3', { key: uniqueKey, style: { fontSize: '14px', fontWeight: '700', borderBottom: '1px solid #e2e8f0', paddingBottom: '6px', marginTop: '8px', marginBottom: '8px', color: '#1e293b' } }, safeRender(label));

        case 'section':
          return el('h4', { key: uniqueKey, style: { fontSize: '11px', fontWeight: '700', textTransform: 'uppercase', color: '#64748b', marginTop: '12px', marginBottom: '6px', letterSpacing: '0.025em' } }, safeRender(label));

        case 'risk_indicators':
          return el('div', { key: uniqueKey, style: { padding: '10px 0' } }, [
            label && el('strong', { style: { display: 'block', fontSize: '11px', color: '#991b1b', marginBottom: '5px', textTransform: 'uppercase' } }, safeRender(label)),
            el('ul', { style: { margin: 0, paddingLeft: '18px', color: '#b91c1c', fontSize: '12px', listStyleType: 'disc' } },
              (control.risks || control.items || []).map((r, i) => el('li', { key: i, style: { marginBottom: '4px' } }, safeRender(r))))
          ]);

        case 'assurance_badges':
          return el('div', { key: uniqueKey, style: { display: 'flex', gap: '8px', flexWrap: 'wrap', padding: '10px 0', marginTop: '10px', borderTop: '1px solid #fed7aa' } },
            (control.badges || control.items || []).map((b, i) => el('span', { key: i, style: { display: 'flex', alignItems: 'center', background: '#ffffff', color: '#166534', padding: '4px 10px', borderRadius: '15px', fontSize: '12px', border: '1px solid #bbf7d0', fontWeight: '600', boxShadow: '0 1px 2px rgba(0,0,0,0.05)' } }, [
              el('span', { style: { marginRight: '6px', fontSize: '14px' } }, '🛡️'),
              safeRender(b)
            ]))
          );

        case 'test_checklist':
        case 'evidence_list':
          return el('div', { key: uniqueKey, style: { marginBottom: '10px' } }, [
            label && el('strong', { style: { display: 'block', fontSize: '12px', color: '#334155', marginBottom: '6px' } }, safeRender(label)),
            el('ol', { style: { margin: 0, paddingLeft: '20px', color: '#475569', fontSize: '12px' } },
              (control.items || control.tests || control.checklist || control.evidence || []).map((item, i) => el('li', { key: i, style: { marginBottom: '4px' } }, safeRender(item))))
          ]);

        case 'info':
        case 'html':
          return el('div', { key: uniqueKey, style: { padding: '8px 12px', background: '#f0f9ff', borderLeft: '3px solid #0ea5e9', fontSize: '11px', color: '#0c4a6e', marginBottom: '8px', lineHeight: '1.4' }, dangerouslySetInnerHTML: { __html: control.content || control.html || label } });

        case 'warning':
        case 'alert':
          const alertType = (label || 'info').toLowerCase();
          const alertMap = {
            success: { icon: 'yes', color: '#166534', bg: '#f0fdf4', border: '#bbf7d0' },
            warning: { icon: 'warning', color: '#9a3412', bg: '#fff7ed', border: '#fed7aa' },
            error: { icon: 'no', color: '#991b1b', bg: '#fef2f2', border: '#fecaca' },
            info: { icon: 'info', color: '#0c4a6e', bg: '#f0f9ff', border: '#bae6fd' },
            tip: { icon: 'lightbulb', color: '#3f6212', bg: '#f7fee7', border: '#d9f99d' },
            alert: { icon: 'warning', color: '#9a3412', bg: '#fff7ed', border: '#fed7aa' }
          };
          const style = alertMap[alertType] || alertMap.info;
          return el('div', {
            key: uniqueKey,
            style: {
              display: 'flex',
              gap: '10px',
              padding: '12px',
              background: style.bg,
              borderLeft: `4px solid ${style.color}`,
              borderTop: `1px solid ${style.border}`,
              borderRight: `1px solid ${style.border}`,
              borderBottom: `1px solid ${style.border}`,
              borderRadius: '4px',
              fontSize: '13px',
              color: style.color,
              marginBottom: '15px',
              alignItems: 'center'
            }
          }, [
            el(Icon, { icon: style.icon, size: 20, style: { flexShrink: 0 } }),
            el('div', {
              style: { lineHeight: '1.5' },
              dangerouslySetInnerHTML: { __html: control.message || control.content || label }
            })
          ]);

        case 'remediation_steps':
        case 'evidence_uploader':
          return null;

        default:
          return null;
      }
    };

    const verificationTypes = ['verification_action', 'automated_test', 'risk_indicators', 'assurance_badges'];
    const guideTypes = ['test_checklist', 'evidence_list', 'remediation_steps', 'evidence_uploader'];

    const mainControlsRaw = schema.controls.filter(c => {
      const isVerification = verificationTypes.includes(c.type);
      const isGuide = guideTypes.includes(c.type);

      // 🧹 Visibility Logic (Schema-Driven)
      if (c.visibility && c.visibility.condition === 'has_content') {
        const val = currentData[c.key];
        const hasContent = val && val.toString().trim().length > 0;
        if (!hasContent && c.visibility.fallback === 'hide') return false;
      }

      // 🧹 Legacy / Key-Based Suppression (v3.3.40 fallback)
      if (['textarea', 'code', 'input', 'html', 'info'].includes(c.type)) {
        const val = currentData[c.key];
        const hasContent = val && val.toString().trim().length > 0;

        // Legacy: Always hide these specific keys if empty
        const legacyKeys = ['operational_notes', 'manual_protocol', 'implementation_notes'];
        if (!hasContent && (legacyKeys.includes(c.key) || c.key?.includes('note') || c.key?.includes('protocol'))) {
          return false;
        }
      }

      // Hide empty Guide items
      if (isGuide) {
        if (['test_checklist', 'evidence_list'].includes(c.type)) {
          const items = c.items || c.tests || c.checklist || c.evidence || [];
          if (items.length === 0) return false;
        }
      }

      if (isGuidePanel) {
        return isGuide;
      } else {
        if (isVerification || isGuide) return false;

        if (c.type === 'section') {
          const label = (c.label || '').toLowerCase();
          const redundantLabels = [
            'verification',
            'automated verification',
            'functional verification',
            'manual verification guidelines',
            'threat coverage',
            'verification & assurance'
          ];
          if (redundantLabels.some(rl => label.includes(rl))) return false;
        }
        return true;
      }
    });

    // Orphan Logic (v3.3.10) - Remove headers/sections if they contain zero functional children
    const mainControls = mainControlsRaw.filter((c, i) => {
      if (['header', 'section'].includes(c.type)) {
        const nextContent = mainControlsRaw.slice(i + 1).find(nc => !['header', 'section', 'divider', 'group'].includes(nc.type));
        return !!nextContent;
      }
      return true;
    });

    const riskControls = schema.controls.filter(c => c.type === 'risk_indicators');
    const badgeControls = schema.controls.filter(c => c.type === 'assurance_badges');
    const otherVerificationControls = schema.controls.filter(c =>
      verificationTypes.includes(c.type) &&
      c.type !== 'risk_indicators' &&
      c.type !== 'assurance_badges' &&
      c.type !== 'verification_action' &&
      c.type !== 'automated_test'
    );

    const getBadgeIcon = (text) => {
      const t = (text || '').toString().toLowerCase();
      if (t.includes('prevent') || t.includes('block')) return '🛡️';
      if (t.includes('detect') || t.includes('log')) return '👁️';
      if (t.includes('limit') || t.includes('rate')) return '⚡';
      if (t.includes('secure') || t.includes('safe')) return '🔒';
      if (t.includes('complian') || t.includes('audit')) return '📋';
      return '✅';
    };

    // If all controls are hidden, return null to avoid rendering empty wrappers
    if (mainControls.length === 0 && riskControls.length === 0 && badgeControls.length === 0 && otherVerificationControls.length === 0) {
      // Still show monitor if explicitly asked and present
      if (isRateLimit && !hideMonitor) {
        return el('div', { className: 'vapt-generated-interface' }, el(RateLimitMonitor, { featureKey: feature.key || feature.id }));
      }
      return null;
    }

    const metadata = schema.metadata || {};

    const opNotes = feature.operational_notes || schema.operational_notes;
    const protocolData = feature.manual_protocol || schema.manual_protocol;

    // 🛡️ Robust Protocol Parsing (v3.12.19)
    let protocolSteps = null;
    if (protocolData) {
      const parsed = (typeof protocolData === 'string' ? JSON.parse(protocolData) : protocolData);
      if (Array.isArray(parsed)) {
        protocolSteps = parsed;
      } else if (parsed && Array.isArray(parsed.steps)) {
        protocolSteps = parsed.steps;
      } else if (parsed) {
        protocolSteps = [parsed];
      }
    }

    // 🛡️ Helper: Convert URLs to clickable links (v3.12.21)
    const linkify = (text) => {
      if (!text || typeof text !== 'string') return text;

      // 1. Handle Markdown Links first: [label](url) (v3.13.15)
      const mdRegex = /\[([^\]]+)\]\((https?:\/\/[^\s#?)]+[^)]*)\)/g;
      let parts = [];
      let lastIndex = 0;
      let match;

      while ((match = mdRegex.exec(text)) !== null) {
        if (match.index > lastIndex) {
          parts.push(text.substring(lastIndex, match.index));
        }
        parts.push(el('a', {
          key: 'md-' + match.index,
          href: match[2],
          target: '_blank',
          rel: 'noopener noreferrer',
          style: { color: '#2563eb', textDecoration: 'underline' }
        }, match[1]));
        lastIndex = mdRegex.lastIndex;
      }

      if (lastIndex < text.length) {
        const remaining = text.substring(lastIndex);
        // 2. Handle raw URLs in the remaining text (excluding what was already matched)
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        const rawParts = remaining.split(urlRegex);
        rawParts.forEach((part, i) => {
          if (part.match(urlRegex)) {
            parts.push(el('a', {
              key: 'raw-' + i,
              href: part,
              target: '_blank',
              rel: 'noopener noreferrer',
              style: { color: '#2563eb', textDecoration: 'underline' }
            }, part));
          } else {
            parts.push(part);
          }
        });
      }

      return parts.length > 0 ? parts : text;
    };

    return el('div', { className: 'vapt-generated-interface', style: { display: 'flex', flexDirection: 'column', gap: '20px' } }, [

      // 🛡️ Operational Notes (v3.12.18) - Collapsible (v3.12.20)
      !hideOpNotes && opNotes && el('details', {
        className: 'vapt-op-notes',
        open: false, // Default collapsed
        style: {
          padding: '12px 15px',
          background: '#f0fdfa',
          border: '1px solid #ccfbf1',
          borderLeft: '4px solid #0d9488',
          borderRadius: '6px',
          fontSize: '13px',
          color: '#134e4a',
          lineHeight: '1.5',
          marginBottom: '20px'
        }
      }, [
        el('summary', { style: { fontWeight: '700', cursor: 'pointer', outline: 'none', listStyle: 'none', display: 'flex', alignItems: 'center', gap: '6px' } }, [
          el(Icon, { icon: 'info', size: 16 }),
          __('Business Impact & Security Benefit', 'vaptsecure')
        ]),
        el('div', { style: { marginTop: '10px' } },
          typeof opNotes === 'string' ? linkify(opNotes) : JSON.stringify(opNotes)
        )
      ]),

      // Functional Controls Panel

      // Functional Controls Panel

      // Live Rate Limit Monitor
      mainControls.length > 0 && el('div', { className: 'vapt-functional-panel', style: { background: '#fff', borderRadius: '8px', padding: '0' } }, [
        el('div', { style: { display: 'flex', flexDirection: 'column', gap: '15px' } }, mainControls.map(renderControl)),
      ]),

      // Live Rate Limit Monitor (Moved below controls v3.3.45)
      isRateLimit && !hideMonitor && el(RateLimitMonitor, { featureKey: feature.key || feature.id }),

      (feature.include_verification_guidance == 1 || feature.include_verification_guidance === true || feature.include_verification_guidance === undefined) && (riskControls.length > 0 || otherVerificationControls.length > 0) && el('div', {
        className: 'vapt-threat-panel',
        style: {
          background: '#fff7ed',
          border: '1px solid #fed7aa',
          borderRadius: '8px',
          padding: '15px'
        }
      }, [
        el('h4', { style: { margin: '0 0 10px 0', fontSize: '12px', fontWeight: '700', textTransform: 'uppercase', color: '#9a3412' } }, __('Threat Coverage', 'vaptsecure')),
        riskControls.map(renderControl),
        otherVerificationControls.map(renderControl)
      ]),

      (feature.include_verification_guidance == 1 || feature.include_verification_guidance === true || feature.include_verification_guidance === undefined) && badgeControls.length > 0 && el('div', {
        className: 'vapt-badges-row',
        style: { display: 'flex', flexWrap: 'wrap', gap: '10px' }
      },
        badgeControls.map(c =>
          (c.badges || c.items || []).map((b, i) => {
            const label = typeof b === 'object' ? (b.label || JSON.stringify(b)) : b;
            return el('span', { key: i, style: { display: 'flex', alignItems: 'center', background: '#ffffff', color: '#166534', padding: '6px 12px', borderRadius: '20px', fontSize: '12px', border: '1px solid #bbf7d0', fontWeight: '600', boxShadow: '0 1px 2px rgba(0,0,0,0.05)' } }, [
              el('span', { style: { marginRight: '6px', fontSize: '14px' } }, getBadgeIcon(label)),
              label
            ]);
          }))
      ),

      // 🛡️ Manual Verification Protocol (v3.12.18) - Collapsible (v3.12.20)
      !hideProtocol && (feature.include_manual_protocol == 1 || feature.include_manual_protocol === true || feature.include_manual_protocol === undefined) && protocolSteps && el('details', {
        className: 'vapt-protocol-panel',
        open: false, // Default collapsed
        style: {
          background: '#f8fafc',
          border: '1px solid #e2e8f0',
          borderRadius: '8px',
          padding: '15px'
        }
      }, [
        el('summary', { style: { fontSize: '12px', fontWeight: '700', textTransform: 'uppercase', color: '#334155', cursor: 'pointer', outline: 'none' } }, __('Manual Verification Protocol', 'vaptsecure')),
        el('ol', { style: { margin: '15px 0 0 0', paddingLeft: '20px', fontSize: '12px', color: '#475569' } },
          (Array.isArray(protocolSteps) ? protocolSteps : [protocolSteps]).map((s, i) => {
            let stepText = typeof s === 'object' ? (s.action || s.description || s.step || JSON.stringify(s)) : s;
            // 🛡️ Enhanced Numbering Cleanup (v3.13.14): Handles "1. ", "Step 1: ", "1) ", etc.
            stepText = stepText.replace(/^(Step\s*\d+[:\s]*|\d+[\.\)]\s*)+/i, '');
            return el('li', { key: i, style: { marginBottom: '6px' } }, linkify(stepText));
          })
        )
      ]),

      localAlert && el(Modal, {
        title: localAlert.type === 'error' ? __('Error', 'vaptsecure') : __('Notice', 'vaptsecure'),
        onRequestClose: () => setLocalAlert(null),
        style: { maxWidth: '400px' }
      }, [
        el('div', { style: { display: 'flex', gap: '10px', alignItems: 'center', marginBottom: '15px' } }, [
          localAlert.type === 'success' && el(Icon, { icon: 'yes', size: 24, style: { color: 'green', background: '#dcfce7', borderRadius: '50%', padding: '4px' } }),
          el('p', { style: { fontSize: '14px', color: '#1f2937', margin: 0 } }, safeRender(localAlert.message))
        ]),
        el('div', { style: { textAlign: 'right' } },
          el(Button, { isPrimary: true, onClick: () => setLocalAlert(null) }, __('OK', 'vaptsecure'))
        )
      ])

    ]);
  };

  window.VAPTSECURE_GeneratedInterface = GeneratedInterface;
})();
