# VAPT Secure - REST API 500 Error Fix Deployment Checklist

## Files Modified (Local)

- ✅ `vaptsecure.php` - Added error handling to service initialization and activation hook
- ✅ `includes/class-vaptsecure-enforcer.php` - Added backward compatibility alias for class name
- ✅ `includes/enforcers/class-vaptsecure-hook-driver.php` - Added defensive checks for REST API auth

## Files to Deploy to Production

### 1. Update Plugin Files

Deploy these files to production:

```
/wp-content/plugins/VAPT-Secure/vaptsecure.php
/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-enforcer.php
/wp-content/plugins/VAPT-Secure/includes/enforcers/class-vaptsecure-hook-driver.php
```

### 2. Update .htaccess (CRITICAL)

Add the following rules to your WordPress `.htaccess` file:

```apache
# VAPT Secure - REST API Fix
# Add AFTER WordPress default rules

# Allow /users/me endpoint for current user profile (needed by WordPress admin)
RewriteCond %{REQUEST_URI} ^wp-json/wp/v2/users/me [NC]
RewriteRule ^ - [L]

# Skip SQL injection checks for REST API requests
RewriteCond %{REQUEST_URI} !^/wp-json/ [NC]
RewriteCond %{QUERY_STRING} !rest_route= [NC]
```

### 3. Enable Debug Logging (Optional but Recommended)

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Deployment Steps

### Step 1: Backup

1. Backup the current plugin files
2. Backup the `.htaccess` file

### Step 2: Deploy Plugin Updates

1. Upload the modified plugin files via FTP/SFTP
2. Or use WordPress plugin update mechanism

### Step 3: Update .htaccess

1. Download current `.htaccess`
2. Add the REST API fix rules (see above)
3. Upload updated `.htaccess`

### Step 4: Clear Caches

1. Clear any WordPress caching plugins
2. Clear server-side caching (if applicable)
3. Clear browser cache

### Step 5: Test

1. Visit `/wp-admin/plugins.php` - should load without console errors
2. Check browser console - should see no 500 errors
3. Test `/wp-json/wp/v2/users/me` - should return 200 OK
4. Try activating VAPT Secure plugin

## Troubleshooting

### If Still Getting 500 Errors

1. Check PHP error logs
2. Check WordPress debug log at `/wp-content/debug.log`
3. Try deactivating other plugins to isolate the issue

### If Plugin Activation Fails

1. Check error logs for "VAPTSECURE_Enforcer not found"
2. Ensure all plugin files were uploaded correctly
3. Check file permissions

## Expected Results After Fix

- ✅ `/wp-json/wp/v2/users/me` returns 200 OK with valid JSON
- ✅ No console errors on plugins page
- ✅ VAPT Secure plugin activates successfully
- ✅ Workbench loads without errors
- ✅ Security protections remain intact

## Rollback Plan

If issues occur:

1. Restore original `.htaccess` backup
2. Restore original plugin files
3. Clear caches
4. Test again
