# REST API 500 Error Fix Plan - VAPT Secure Plugin

**Date:** 2026-03-07
**Priority:** CRITICAL
**Status:** Planning Phase

## 🐛 Problem Summary

When visiting the Plugins page on the production server (`https://vaptsecure.net/vapts/wp-admin/plugins.php`), users see:

1. **500 Internal Server Error** on `/wp-json/wp/v2/users/me?context=edit&_locale=user`
2. **"invalid_json"** console error: `The response is not a valid JSON response.`
3. **500 Internal Server Error** on `/favicon.ico`
4. **Plugin activation failure** with error: `class "VAPT_SECURE_Enforcer" not found`

## 🔍 Root Cause Analysis

### 1. Class Loading Issue

- Error log shows: `class "VAPT_SECURE_Enforcer" not found`
- Actual class name: `VAPTSECURE_Enforcer` (note: `VAPT_SECURE` vs `VAPTSECURE`)
- This suggests either:
  - Typo in code referencing wrong class name
  - Case sensitivity issue on production server
  - Class file not being loaded properly

### 2. REST API 500 Errors

- `/wp-json/wp/v2/users/me` endpoint returning 500 (not 403)
- This is likely due to:
  - `.htaccess` rules blocking the endpoint
  - PHP errors in WordPress REST API handlers
  - Authentication/authorization issues

### 3. Production vs Local Discrepancy

- Plugin works locally but not on production
- Suggests environment-specific issues:
  - File permissions
  - PHP version differences
  - Web server configuration (Apache/Nginx)
  - .htaccess rules

## 🛠️ Proposed Fixes

### Phase 1: Immediate Hotfixes

#### 1.1 Fix Class Loading Issue

**File:** `vaptsecure.php`
**Issue:** Check for any references to `VAPT_SECURE_Enforcer` instead of `VAPTSECURE_Enforcer`
**Solution:**

```php
// Ensure class exists before calling
if (class_exists('VAPTSECURE_Enforcer')) {
    VAPTSECURE_Enforcer::init();
} else {
    error_log('VAPTSECURE_Enforcer class not found - check includes/class-vaptsecure-enforcer.php');
}
```

#### 1.2 Check .htaccess Rules

**File:** WordPress root `.htaccess`
**Issue:** Overly restrictive rules blocking `/wp-json/wp/v2/users/me`
**Solution:** Apply fix from `REST_API_Fix_Summary.md`:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
# Allow /users/me endpoint for current user profile (needed by WordPress admin)
RewriteCond %{REQUEST_URI} ^wp-json/wp/v2/users/me
RewriteRule ^ - [L]
# Block user listing and user by ID (prevent enumeration)
RewriteRule ^wp-json/wp/v2/users/?$ - [F,L]
RewriteRule ^wp-json/wp/v2/users/(\d+) - [F,L]
</IfModule>
```

#### 1.3 Fix REST API Authentication

**File:** `includes/enforcers/class-vaptsecure-hook-driver.php`
**Issue:** Overly broad REST API blocking causing 500 errors
**Solution:** Ensure `block_rest_api()` method doesn't interfere with WordPress core endpoints

### Phase 2: Diagnostic Steps

#### 2.1 Enable WordPress Debugging

Add to `wp-config.php` on production:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### 2.2 Check PHP Error Logs

- Review `/var/log/apache2/error.log` or equivalent
- Check WordPress debug log at `/wp-content/debug.log`

#### 2.3 Test REST API Endpoint Directly

```bash
curl -I https://vaptsecure.net/wp-json/wp/v2/users/me
```

### Phase 3: Plugin Activation Fix

#### 3.1 Fix Activation Hook

**File:** `vaptsecure.php`
**Issue:** `register_activation_hook` might be calling undefined functions
**Solution:** Ensure all required files are included before activation hook runs

#### 3.2 Add Error Handling

Wrap activation function in try-catch:

```php
function vaptsecure_activate_plugin() {
    try {
        // Activation code
    } catch (Exception $e) {
        error_log('VAPT Secure activation failed: ' . $e->getMessage());
        // Don't crash WordPress
    }
}
```

## 📋 Implementation Checklist

### Immediate Actions

- [ ] Check for `VAPT_SECURE_Enforcer` vs `VAPTSECURE_Enforcer` typos
- [ ] Review production `.htaccess` file
- [ ] Enable WordPress debug logging
- [ ] Check PHP error logs

### Code Changes

- [ ] Fix class loading in `vaptsecure.php`
- [ ] Update `.htaccess` rules
- [ ] Add error handling to activation hook
- [ ] Test REST API endpoints

### Testing

- [ ] Test plugin activation on production
- [ ] Verify `/wp-json/wp/v2/users/me` returns 200
- [ ] Check plugins page for console errors
- [ ] Test VAPT Secure Workbench functionality

## 🔄 Rollback Plan

If fixes cause issues:

1. Restore original `.htaccess` backup
2. Disable plugin temporarily
3. Revert code changes

## 📊 Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Breaking other plugins | Low | Medium | Test thoroughly before deployment |
| Security regression | Low | High | Keep security rules for `/users/` and `/users/{id}` |
| WordPress core broken | Low | Critical | Test admin functionality after changes |

## 🚀 Deployment Steps

1. **Backup** production `.htaccess` and plugin files
2. **Deploy** code fixes to production
3. **Update** `.htaccess` rules
4. **Clear** any caching plugins
5. **Test** plugin activation
6. **Verify** REST API endpoints
7. **Monitor** error logs

## 📚 Related Documentation

- `REST_API_Fix_Summary.md` - Previous fix for similar issue
- `REST_API_Blocking_Fix.md` - REST API security considerations
- `SQL_Injection_False_Positive_Fix.md` - SQL injection rule adjustments

## 🎯 Success Criteria

- [ ] `/wp-json/wp/v2/users/me` returns 200 OK with valid JSON
- [ ] No console errors on plugins page
- [ ] VAPT Secure plugin activates successfully
- [ ] Workbench loads without errors
- [ ] All security protections remain intact

---

**Next Steps:**

1. Review this plan
2. Provide .htaccess contents for analysis
3. Share PHP error logs from production
4. Approve implementation approach
