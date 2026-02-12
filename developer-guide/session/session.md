# Session Management

## Overview

This document outlines the session management implementation in the eHelp application. The system uses Laravel's session handling with database storage, supports CAS authentication, includes frontend timeout handling, and provides cross-tab synchronization.

## Session Configuration

### Configuration File

Location: [`config/session.php`](../../config/session.php)

### Environment Variables

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=null
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_PARTITIONED_COOKIE=false
```

### Key Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `driver` | `database` | Session storage driver (database, file, redis, etc.) |
| `lifetime` | `120` | Session lifetime in minutes (2 hours) |
| `expire_on_close` | `false` | Whether session expires when browser closes |
| `encrypt` | `false` | Whether to encrypt session data |
| `http_only` | `true` | Prevents JavaScript access to session cookies |
| `same_site` | `lax` | CSRF protection via SameSite cookie attribute |
| `lottery` | `[2, 100]` | Probability of session cleanup (2%) |

## Database Storage

### Table Structure

The application uses database session storage. The migration file is located at:
[`database/migrations/2025_12_12_064717_create_sessions_table_custom.php`](../../database/migrations/2025_12_12_064717_create_sessions_table_custom.php)

**Note:** The migration is currently commented out, indicating the sessions table may have been created manually or through Laravel's default session table migration.

Expected table structure:
```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->text('payload');
    $table->integer('last_activity')->index();
});
```

## Session Management Routes

### Route Configuration

Location: [`routes/web.php`](../../routes/web.php#L99-L118)

### Available Endpoints

#### 1. Session Ping (POST)

**Route:** `/session/ping`  
**Name:** `session.ping`  
**Middleware:** Authentication required

**Purpose:** Extends session lifetime and checks authentication status

**Response:**
```json
{
  "authenticated": true,
  "remaining": 7200,
  "timestamp": "2026-02-11T10:30:00.000000Z"
}
```

**Usage:**
```javascript
// Frontend automatically calls this every 5 minutes
fetch('/session/ping', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  }
});
```

#### 2. Session Check (GET)

**Route:** `/session/check`  
**Name:** `session.check`  
**Middleware:** Authentication required

**Purpose:** Check session status without extending it

**Response:**
```json
{
  "authenticated": true,
  "user": {
    "id": 1,
    "name": "John Doe"
  },
  "timestamp": "2026-02-11T10:30:00.000000Z"
}
```

## Frontend Session Timeout Handler

### Configuration

Location: [`resources/assets/js/session-timeout.js`](../../resources/assets/js/session-timeout.js)

### Features

1. **Idle Timer:** Monitors user inactivity
2. **Warning Modal:** Shows countdown 5 minutes before session expires
3. **Auto Logout:** Automatically logs out user after session expires
4. **Cross-Tab Sync:** Synchronizes logout across all browser tabs
5. **Activity Detection:** Monitors mouse, keyboard, scroll, and touch events
6. **Session Extension:** Automatically pings server every 5 minutes

### Configuration Constants

```javascript
const SESSION_LIFETIME = 120;  // minutes (synced with Laravel config)
const WARNING_TIME = 5;        // Show warning 5 minutes before expiry
const PING_INTERVAL = 5;       // Ping server every 5 minutes
```

### Cross-Tab Synchronization

The system uses two methods for cross-tab communication:

1. **BroadcastChannel API:** Modern browsers
2. **localStorage Events:** Fallback method

**Storage Keys:**
- `session_last_activity`: Tracks last user activity
- `session_logout`: Triggers logout in all tabs

**Broadcast Messages:**
```javascript
// Activity broadcast
{ type: 'activity', timestamp: 1707649800000 }

// Logout broadcast
{ type: 'logout' }
```

## Middleware Integration

### LocaleMiddleware

Location: [`app/Http/Middleware/LocaleMiddleware.php`](../../app/Http/Middleware/LocaleMiddleware.php)

**Purpose:** Sets application locale based on session data

```php
public function handle(Request $request, Closure $next): Response
{
    if (session()->has('locale') && in_array(session()->get('locale'), ['en', 'fr', 'ar', 'de'])) {
        app()->setLocale(session()->get('locale'));
    }
    return $next($request);
}
```

### CAS Authentication Middleware

Location: [`app/Http/Middleware/AuthCas.php`](../../app/Http/Middleware/AuthCas.php)

**Purpose:** Handles SATUID (CAS) authentication and session management

**Session Handling:**
- Validates authentication status before each request
- Manages ticket validation from SATUID
- Handles session invalidation on logout

## Session Usage in Controllers

### Language Controller

Location: [`app/Http/Controllers/language/LanguageController.php`](../../app/Http/Controllers/language/LanguageController.php)

**Storing locale preference:**
```php
public function swap(Request $request, $locale)
{
    if (!in_array($locale, ['en', 'fr', 'ar', 'de'])) {
        abort(400);
    }
    
    $request->session()->put('locale', $locale);
    App::setLocale($locale);
    
    return redirect()->back();
}
```

## Session Usage in Views

### Flash Messages

Sessions are commonly used for flash messages across the application:

**Success Messages:**
```blade
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
```

**Error Messages:**
```blade
@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
```

**Status Messages:**
```blade
@if (session('status'))
    <div class="alert alert-info">
        {{ session('status') }}
    </div>
@endif
```

**Example Locations:**
- [`resources/views/tickets/show.blade.php`](../../resources/views/tickets/show.blade.php#L70-L84)
- [`resources/views/dashboard/dashboard.blade.php`](../../resources/views/dashboard/dashboard.blade.php#L119-L131)
- [`resources/views/auth/login.blade.php`](../../resources/views/auth/login.blade.php#L396-L400)

## Authentication & Logout

### Standard Logout

```php
Auth::logout();
request()->session()->invalidate();
request()->session()->regenerateToken();
return redirect('/login');
```

### CAS Logout

Location: [`routes/web.php`](../../routes/web.php#L122-L129)

**Route:** `/cas-logout`  
**Method:** POST  
**Enabled when:** `CAS_ENABLED=true`

```php
Route::post('/cas-logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect(config('cas.cas_logout_url'));
})->name('cas.logout');
```

## Common Session Operations

### Storing Data

```php
// Using helper
session(['key' => 'value']);

// Using request
$request->session()->put('key', 'value');

// Flash data (available only for next request)
session()->flash('message', 'Success!');
```

### Retrieving Data

```php
// Get with default value
$value = session('key', 'default');

// Check if exists
if (session()->has('key')) {
    // ...
}

// Get and forget
$value = session()->pull('key');
```

### Removing Data

```php
// Forget specific key
session()->forget('key');

// Forget multiple keys
session()->forget(['key1', 'key2']);

// Clear all session data
session()->flush();
```

### Session Regeneration

```php
// Regenerate session ID (security best practice after login)
$request->session()->regenerate();

// Regenerate CSRF token
$request->session()->regenerateToken();

// Invalidate session
$request->session()->invalidate();
```

## Security Best Practices

### 1. Session Fixation Prevention

Always regenerate session ID after authentication:
```php
Auth::login($user);
$request->session()->regenerate();
```

### 2. CSRF Protection

All POST, PUT, PATCH, DELETE requests must include CSRF token:
```blade
<form method="POST">
    @csrf
    <!-- form fields -->
</form>
```

### 3. Secure Cookie Settings

**Production `.env` settings:**
```env
SESSION_SECURE_COOKIE=true  # HTTPS only
SESSION_HTTP_ONLY=true      # Prevent XSS
SESSION_SAME_SITE=lax       # CSRF protection
```

### 4. Session Encryption

Enable for sensitive data:
```env
SESSION_ENCRYPT=true
```

### 5. Session Lifetime

Adjust based on security requirements:
```env
# Shorter for high-security applications
SESSION_LIFETIME=30

# Longer for user convenience
SESSION_LIFETIME=120
```

## Troubleshooting

### Session Not Persisting

1. **Check session driver:**
   ```bash
   php artisan config:cache
   ```

2. **Verify database connection:**
   ```bash
   php artisan migrate:status
   ```

3. **Clear session data:**
   ```bash
   php artisan session:table
   php artisan migrate
   ```

4. **Check file permissions (if using file driver):**
   ```bash
   chmod -R 775 storage/framework/sessions
   ```

### Session Timeout Issues

1. **Check configuration:**
   ```php
   dd(config('session.lifetime'));
   ```

2. **Verify frontend timer:**
   - Open browser console
   - Look for "Session timeout handler initialized" message

3. **Test session ping:**
   ```bash
   # Check network tab in browser DevTools
   # Should see POST /session/ping every 5 minutes
   ```

### Cross-Tab Sync Not Working

1. **Check BroadcastChannel support:**
   ```javascript
   console.log('BroadcastChannel' in window);
   ```

2. **Verify localStorage access:**
   ```javascript
   localStorage.setItem('test', '1');
   console.log(localStorage.getItem('test'));
   ```

3. **Check browser console for errors**

## Testing

### Manual Testing

1. **Session Creation:**
   ```bash
   # Login and check sessions table
   SELECT * FROM sessions ORDER BY last_activity DESC LIMIT 10;
   ```

2. **Session Timeout:**
   - Login to application
   - Wait for warning modal (SESSION_LIFETIME - 5 minutes)
   - Verify countdown timer
   - Test continue/logout buttons

3. **Cross-Tab Testing:**
   - Open application in two browser tabs
   - Logout from one tab
   - Verify other tab redirects to login

### Automated Testing

Example test case:
```php
public function test_session_stores_locale()
{
    $this->post('/language/swap/en')
        ->assertSessionHas('locale', 'en');
        
    $this->assertEquals('en', app()->getLocale());
}
```

## Performance Considerations

### Database Session Cleanup

The lottery system automatically cleans old sessions:
```php
'lottery' => [2, 100], // 2% chance on each request
```

**Manual cleanup:**
```bash
# Add to schedule (routes/console.php)
php artisan schedule:run

# Or cron job
0 0 * * * php artisan session:gc
```

### Session Table Indexing

Ensure indexes exist:
```sql
CREATE INDEX sessions_user_id_index ON sessions (user_id);
CREATE INDEX sessions_last_activity_index ON sessions (last_activity);
```

## Related Configuration

- **Authentication:** [`config/auth.php`](../../config/auth.php)
- **CAS Settings:** [`config/cas.php`](../../config/cas.php)
- **Cookie Settings:** [`config/session.php`](../../config/session.php)
- **Middleware:** [`app/Http/Kernel.php`](../../app/Http/Kernel.php)

## Additional Resources

- [Laravel Session Documentation](https://laravel.com/docs/session)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [MDN: HTTP Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)

---

**Last Updated:** February 11, 2026  
**Maintained By:** Development Team
