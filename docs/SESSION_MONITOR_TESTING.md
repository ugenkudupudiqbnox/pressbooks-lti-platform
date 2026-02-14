# LTI Session Monitor Testing Guide

## How It Works

When a user is logged into Pressbooks via LTI, JavaScript monitors the Moodle session:
- Checks every 30 seconds if Moodle session is still valid
- Uses Moodle's `core_session_time_remaining` API
- After 2 consecutive failures, logs out Pressbooks user
- Redirects back to Moodle

## Testing Steps

### 1. Open Browser Console

**Chrome/Edge:** F12 or Ctrl+Shift+I
**Firefox:** F12 or Ctrl+Shift+K
**Safari:** Cmd+Option+I

### 2. Launch from Moodle

1. Log into Moodle as student or instructor
2. Click on any Pressbooks chapter activity
3. **Check console** - you should see:
   ```
   [LTI Session Monitor] Initialized - checking Moodle session every 30s
   [LTI Session Monitor] Moodle session check: OK
   ```

### 3. Test Logout

**Option A - Quick Test:**
1. Keep Pressbooks tab open with console visible
2. Open new tab → Go to Moodle → Log out
3. Return to Pressbooks tab
4. **Within 60 seconds**, you should see:
   ```
   [LTI Session Monitor] Moodle session check failed (attempt 1/2)
   [LTI Session Monitor] Moodle session check failed (attempt 2/2)
   [LTI Session Monitor] Moodle session expired, logging out...
   ```
5. Pressbooks should redirect to Moodle logout page

**Option B - Focus Trigger:**
1. Log out of Moodle in another tab
2. Click on Pressbooks tab to focus it
3. Should immediately check session and logout

### 4. Troubleshooting

#### Issue: No console logs appear

**Check if user is LTI user:**
```bash
docker exec pressbooks wp user meta get <username> _lti_user_id --path=/var/www/pressbooks/web/wp --allow-root
```

**Check if script is loaded:**
```bash
curl -s https://pb.lti.qbnox.com/test/chapter/chapter-1/ | grep "LTI Session Monitor"
```

#### Issue: CORS Error in Console

```
Access to fetch at 'https://moodle.lti.qbnox.com/...' from origin 'https://pb.lti.qbnox.com'
has been blocked by CORS policy
```

**Solution:** Configure Moodle CORS headers.

**Quick Fix (Nginx):**
Edit `/etc/nginx/sites-available/moodle.lti.qbnox.com`:

```nginx
location /lib/ajax/service.php {
    add_header 'Access-Control-Allow-Origin' 'https://pb.lti.qbnox.com' always;
    add_header 'Access-Control-Allow-Credentials' 'true' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type' always;

    if ($request_method = 'OPTIONS') {
        return 204;
    }

    try_files $uri =404;
    fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Then reload Nginx:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

**Alternative (Moodle config.php):**
```php
// Allow CORS from Pressbooks
$CFG->forced_plugin_settings['local_mobile'] = [
    'allowcors' => true,
    'alloweddomains' => 'https://pb.lti.qbnox.com'
];
```

#### Issue: Session check fails immediately

**Check if logged into Moodle:**
```bash
# In browser, go to:
https://moodle.lti.qbnox.com/lib/ajax/service.php

# Should return JSON if logged in
# Should return 401/403 if not logged in
```

#### Issue: Logout doesn't happen

**Check failure count:**
- Monitor console for "attempt X/2" messages
- Requires 2 consecutive failures to trigger logout
- Wait 60 seconds (2 × 30 second interval)

## Testing with Test Page

Open this test page in your browser:
```
file:///root/pressbooks-lti-platform/test-session-monitor.html
```

Or copy to web server and access via HTTP.

1. **While logged into Moodle**, click "Test Session Check"
   - Should show: ✓ Session check successful
2. **After logging out of Moodle**, click "Test Session Check"
   - Should show: ✗ Session check failed: Session expired

## Debug Logs

Check Pressbooks logs:
```bash
docker exec pressbooks tail -f /var/www/pressbooks/web/app/debug.log | grep "Session Monitor"
```

Check browser console:
```javascript
// Enable verbose logging
localStorage.setItem('lti_debug', '1');
location.reload();
```

## How to Disable

If session monitoring causes issues, disable it:

**Edit `plugin/bootstrap.php`:**
```php
// Comment out this line:
// add_action('init', ['PB_LTI\Services\SessionMonitorService', 'init']);
```

Then redeploy:
```bash
docker cp plugin/bootstrap.php pressbooks:/var/www/pressbooks/web/app/plugins/pressbooks-lti-platform/bootstrap.php
```

## Expected Behavior

✅ **Working correctly:**
- Console shows session checks every 30s
- After Moodle logout, Pressbooks logs out within 60s
- User redirected back to Moodle

❌ **Not working:**
- No console logs (script not loaded)
- CORS errors (Moodle blocking requests)
- Session checks always fail (authentication issue)
- No logout after Moodle logout (check failure count)
