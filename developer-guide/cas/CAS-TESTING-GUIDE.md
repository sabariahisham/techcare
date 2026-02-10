# CAS/SATUID Testing Guide

## Overview
This guide explains how to test SATUID authentication and toggle between CAS and local authentication modes across different server environments.

## Server Environments

| Environment | Server URL | IP Address | Git Branch | CAS Enabled | Debug Mode |
|------------|------------|------------|------------|-------------|------------|
| **Local** | `http://ehelp.test` | N/A | N/A | false/true | true |
| **Development** | `http://techcare-dev.treasury.gov.my` | 10.23.105.181 | `server-dev` | true | true |
| **Staging** | `http://techcare-staging.treasury.gov.my` | 10.23.105.182 | `server-staging` | true | false |
| **Production** | `http://techcare.treasury.gov.my` | 10.23.105.180 | `main` | true | false |

## Quick Toggle - Switch Authentication Modes

### Step 1: Edit .env File
Open `.env` file and find the `CAS_ENABLED` setting:

```env
# Set to true for SATUID authentication
CAS_ENABLED=true

# Set to false for local Laravel authentication
CAS_ENABLED=false
```

### Step 2: Clear Configuration Cache
After changing `.env`, clear the Laravel configuration cache:

```bash
php artisan config:clear
```

That's it! The application will automatically use the appropriate authentication method.

---

## Testing SATUID (CAS) Authentication

### Prerequisites
1. You must have a valid SATUID account
2. Your LDAP ID must exist in the `users` table (column: `id_pgn_ldap`)
3. Your localhost URL must match the registered service URL with SATUID

### Configuration for Localhost Testing

#### 1. Check Your .env Settings
Ensure these settings are correct in your `.env` file:

```env
# Enable CAS authentication
CAS_ENABLED=true

# Your local application URL
APP_URL=http://ehelp.test

# MUST match APP_URL exactly
CAS_CLIENT_SERVICE=http://ehelp.test

# SATUID server settings (production)
CAS_HOSTNAME=satuid.treasury.gov.my
CAS_PORT=443
CAS_URI=/gk

# Where to redirect after login
CAS_REDIRECT_PATH=/dashboard

# Enable debug logging
CAS_DEBUG=true
```

**Important**: `CAS_CLIENT_SERVICE` must exactly match your local URL (`http://ehelp.test`)

#### 2. Register Your URL with SATUID
Before testing, your application URL must be registered with the SATUID system. Contact your SATUID administrator to register:

**For Local Testing:**
- Service URL: `http://ehelp.test`
- Service Name: eHelp Development (Local)
- Callback URLs: `http://ehelp.test/*`

**For Dev Server:**
- Service URL: `http://techcare-dev.treasury.gov.my`
- Service Name: Techcare Development Server
- Callback URLs: `http://techcare-dev.treasury.gov.my/*`

**For Staging Server:**
- Service URL: `http://techcare-staging.treasury.gov.my`
- Service Name: Techcare Staging Server
- Callback URLs: `http://techcare-staging.treasury.gov.my/*`

**For Production Server:**
- Service URL: `http://techcare.treasury.gov.my`
- Service Name: Techcare Production
- Callback URLs: `http://techcare.treasury.gov.my/*`

### Testing Steps

#### Step 1: Check Authentication Status
Visit the authentication status endpoint:
```
http://ehelp.test/auth-status
```

You should see JSON output like:
```json
{
  "cas_enabled": true,
  "authentication_mode": "SATUID (CAS)",
  "cas_service_url": "http://ehelp.test",
  "app_url": "http://ehelp.test",
  "cas_hostname": "satuid.treasury.gov.my",
  "middleware": "cas.auth",
  "user_authenticated": false,
  "current_user": null
}
```

#### Step 2: Clear Configuration Cache
```bash
php artisan config:clear
```

#### Step 3: Logout from Any Existing Session
If logged in locally, logout first:
```
http://ehelp.test/logout
```

#### Step 4: Access the Dashboard
Navigate to:
```
http://ehelp.test/dashboard
```

**Expected Flow:**
1. You'll be redirected to SATUID login page: `https://satuid.treasury.gov.my/gk/login`
2. Enter your SATUID credentials
3. After successful login, you'll be redirected back to `http://ehelp.test/dashboard` with a ticket parameter
4. The system validates your ticket and logs you in
5. You should see your dashboard

#### Step 5: Check the Logs
Monitor Laravel logs for CAS authentication flow:
```bash
tail -f storage/logs/laravel.log
```

Look for log entries like:
- `CAS: Ticket received`
- `CAS: Validating ticket`
- `CAS: Server response received`
- `CAS: Authentication successful`
- `CAS: User found in database`
- `CAS: User logged in successfully`

#### Step 6: Verify Authentication Status Again
After login, check:
```
http://ehelp.test/auth-status
```

Should show:
```json
{
  "cas_enabled": true,
  "authentication_mode": "SATUID (CAS)",
  "user_authenticated": true,
  "current_user": {
    "id": 123,
    "nama": "Your Name",
    "email": "your.email@treasury.gov.my"
  }
}
```

### Testing Logout
To test CAS logout:
```
http://ehelp.test/cas-logout
```

This will:
1. Logout from local application
2. Invalidate session
3. Redirect to SATUID logout: `https://satuid.treasury.gov.my/gk/logout`

---

## Testing Local Authentication

### Step 1: Disable CAS in .env
```env
CAS_ENABLED=false
```

### Step 2: Clear Configuration Cache
```bash
php artisan config:clear
```

### Step 3: Access Login Page
Navigate to:
```
http://ehelp.test/login
```

You should see the standard Laravel/Jetstream login form.

### Step 4: Login with Local Credentials
Use your local email and password to login.

### Step 5: Verify Authentication Status
```
http://ehelp.test/auth-status
```

Should show:
```json
{
  "cas_enabled": false,
  "authentication_mode": "Local Laravel",
  "middleware": "auth + jetstream",
  "user_authenticated": true
}
```

---

## Troubleshooting

### Issue: Redirected to SATUID but returns with error

**Possible Causes:**
1. **Service URL not registered**: Your local URL is not registered with SATUID
2. **URL mismatch**: `CAS_CLIENT_SERVICE` doesn't match `APP_URL`
3. **SSL/Certificate issues**: Check CAS_VALIDATION setting

**Solution:**
- Ensure URLs match exactly
- Contact SATUID admin to register your local URL
- Check logs: `storage/logs/laravel.log`

### Issue: "User not found in database"

**Cause:** Your SATUID LDAP ID doesn't exist in the local database

**Solution:**
Check if your user exists:
```sql
SELECT * FROM users WHERE id_pgn_ldap = 'YOUR_LDAP_ID';
```

If not found, ask admin to create your user account.

### Issue: Ticket validation fails

**Possible Causes:**
1. Network connectivity issues
2. SATUID server unreachable
3. Invalid ticket (ticket already used or expired)

**Solution:**
- Check network connectivity
- Enable debug logging: `CAS_DEBUG=true`
- Check Laravel logs for detailed error messages

### Issue: Infinite redirect loop

**Cause:** Middleware or session issues

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Clear browser cookies
# Delete session cookies for ehelp.test
```

### Issue: Configuration changes not taking effect

**Cause:** Configuration is cached

**Solution:**
Always run after changing .env:
```bash
php artisan config:clear
```

---

## Environment Configuration Settings

### Local Development (Localhost)
```env
# Toggle authentication mode
CAS_ENABLED=false  # Set to true for testing CAS/SATUID

# Local application URL
APP_URL=http://ehelp.test
CAS_CLIENT_SERVICE=http://ehelp.test

# SATUID server settings
CAS_HOSTNAME=satuid.treasury.gov.my
CAS_PORT=443
CAS_URI=/gk

# Debugging and redirects
CAS_DEBUG=true
CAS_REDIRECT_PATH=/dashboard
CAS_LOGOUT_URL=https://satuid.treasury.gov.my/gk/logout
CAS_LOGOUT_REDIRECT=http://ehelp.test

# Environment
APP_ENV=local
APP_DEBUG=true
```

### Development Server (techcare-dev.treasury.gov.my)
**Server IP:** 10.23.105.181  
**Git Branch:** `server-dev`

```env
# Enable CAS authentication
CAS_ENABLED=true

# Server URL
APP_URL=http://techcare-dev.treasury.gov.my
CAS_CLIENT_SERVICE=http://techcare-dev.treasury.gov.my

# SATUID server settings
CAS_HOSTNAME=satuid.treasury.gov.my
CAS_PORT=443
CAS_URI=/gk

# Debugging and redirects
CAS_DEBUG=true
CAS_REDIRECT_PATH=/dashboard
CAS_LOGOUT_URL=https://satuid.treasury.gov.my/gk/logout
CAS_LOGOUT_REDIRECT=http://techcare-dev.treasury.gov.my

# Environment
APP_ENV=local
APP_DEBUG=true
```

### Staging Server (techcare-staging.treasury.gov.my)
**Server IP:** 10.23.105.182  
**Git Branch:** `server-staging`

```env
# Enable CAS authentication
CAS_ENABLED=true

# Server URL
APP_URL=http://techcare-staging.treasury.gov.my
CAS_CLIENT_SERVICE=http://techcare-staging.treasury.gov.my

# SATUID server settings
CAS_HOSTNAME=satuid.treasury.gov.my
CAS_PORT=443
CAS_URI=/gk

# Debugging and redirects (can be disabled in staging if needed)
CAS_DEBUG=true
CAS_REDIRECT_PATH=/dashboard
CAS_LOGOUT_URL=https://satuid.treasury.gov.my/gk/logout
CAS_LOGOUT_REDIRECT=http://techcare-staging.treasury.gov.my

# Environment
APP_ENV=staging
APP_DEBUG=false
```

### Production Server (techcare.treasury.gov.my)
**Server IP:** 10.23.105.180  
**Git Branch:** `main`

```env
# Enable CAS authentication
CAS_ENABLED=true

# Server URL
APP_URL=http://techcare.treasury.gov.my
CAS_CLIENT_SERVICE=http://techcare.treasury.gov.my

# SATUID server settings
CAS_HOSTNAME=satuid.treasury.gov.my
CAS_PORT=443
CAS_URI=/gk

# Production settings - disable debugging
CAS_DEBUG=false
CAS_REDIRECT_PATH=/dashboard
CAS_LOGOUT_URL=https://satuid.treasury.gov.my/gk/logout
CAS_LOGOUT_REDIRECT=http://techcare.treasury.gov.my

# Environment
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

---

## Key Points to Remember

1. **Always clear config cache** after changing .env:
   ```bash
   php artisan config:clear
   ```

2. **URLs must match exactly**:
   - `APP_URL` = `CAS_CLIENT_SERVICE`
   - No trailing slashes
   - Same protocol (http/https)

3. **Service URL must be registered** with SATUID before testing

4. **LDAP ID must exist** in local database (`users.id_pgn_ldap`)

5. **Toggle is instant**: Just change `CAS_ENABLED` and clear cache

6. **Check logs**: Always monitor `storage/logs/laravel.log` when testing

7. **Different logout routes**:
   - CAS mode: `/cas-logout` → redirects to SATUID
   - Local mode: `/logout` → standard Laravel logout

8. **Environment-specific settings**:
   - **Local:** `CAS_ENABLED=false` (or true for testing), `APP_DEBUG=true`
   - **Dev Server:** `CAS_ENABLED=true`, `APP_DEBUG=true`, `APP_ENV=local`
   - **Staging Server:** `CAS_ENABLED=true`, `APP_DEBUG=false`, `APP_ENV=staging`
   - **Production Server:** `CAS_ENABLED=true`, `APP_DEBUG=false`, `APP_ENV=production`

---

## Testing Checklist

### Before Testing CAS
- [ ] SATUID account exists and is active
- [ ] LDAP ID exists in database (`users.id_pgn_ldap`)
- [ ] Application URL registered with SATUID (local/dev/staging/production)
- [ ] .env settings are correct for the environment
- [ ] APP_URL matches CAS_CLIENT_SERVICE exactly
- [ ] CAS_ENABLED is set appropriately
- [ ] Config cache cleared: `php artisan config:clear`
- [ ] Correct git branch deployed (dev/staging/production)

### During Testing
- [ ] Monitor Laravel logs
- [ ] Check /auth-status endpoint
- [ ] Test login flow
- [ ] Test logout flow
- [ ] Verify session management
- [ ] Test session timeout

### After Testing
- [ ] Review logs for errors
- [ ] Verify user data is correct
- [ ] Test switching back to local auth
- [ ] Document any issues

---

## Useful Commands

```bash
# Clear configuration cache (ALWAYS run after .env changes)
php artisan config:clear

# Clear all caches
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# View current configuration
php artisan config:show cas

# Monitor logs in real-time
tail -f storage/logs/laravel.log

# Check database for user
php artisan tinker
>>> User::where('id_pgn_ldap', 'YOUR_LDAP_ID')->first()
```

---

## Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode: `CAS_DEBUG=true`
3. Verify .env configuration
4. Contact SATUID administrator for service registration
5. Contact system administrator for database/user issues

---

**Last Updated:** February 2026
