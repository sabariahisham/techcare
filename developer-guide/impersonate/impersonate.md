# User Impersonation Feature

## Overview

The User Impersonation feature allows authorized users (Administrators) to temporarily login as other users for support, testing, and troubleshooting purposes. This is useful when you need to see the system from another user's perspective or help debug issues they're experiencing.

## Package

**Package**: `lab404/laravel-impersonate`  
**Documentation**: https://github.com/404labfr/laravel-impersonate

```json
"lab404/laravel-impersonate": "*"
```

## Core Concepts

### Access Control Rules

1. **Only Administrator roles can impersonate other users**
2. **Administrator roles CANNOT be impersonated** (for security)
3. **All impersonation activities are logged to the audit trail database**
4. When impersonating, all actions are recorded under the impersonated user's account
5. A warning banner is displayed on the dashboard during active impersonation
6. Users can easily leave impersonation mode and return to their original account

### Audit Trail Events

The system logs three types of impersonation events:

- **`impersonate_start`** - When an administrator begins impersonating a user
- **`impersonate_action`** - Actions performed during active impersonation (POST/PUT/PATCH/DELETE)
- **`impersonate_end`** - When an administrator stops impersonating

All events are tagged with `impersonation` for easy filtering and include full tracking information (IP address, user agent, timestamp).

## Implementation

### 1. User Model Configuration

**File**: `app/Models/User.php`

The User model uses the `Impersonate` trait and implements two key methods:

```php
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use Impersonate;
    
    /**
     * Determine if the user can impersonate another user.
     * Only Administrator roles can impersonate others.
     *
     * @return bool
     */
    public function canImpersonate()
    {
        // Only Administrator can impersonate others
        return $this->hasRole('Administrator');
    }

    /**
     * Determine if this user can be impersonated by another user.
     * Only Administrator roles cannot be impersonated.
     *
     * @return bool
     */
    public function canBeImpersonated()
    {
        // Users with Administrator role cannot be impersonated
        return !$this->hasRole('Administrator');
    }
}
```

### 2. Configuration

**File**: `config/laravel-impersonate.php`

```php
return [
    /**
     * The session key used to store the original user id.
     */
    'session_key' => 'impersonated_by',

    /**
     * The session key used to stored the original user guard.
     */
    'session_guard' => 'impersonator_guard',

    /**
     * The session key used to stored what guard is impersonator using.
     */
    'session_guard_using' => 'impersonator_guard_using',

    /**
     * The default impersonator guard used.
     */
    'default_impersonator_guard' => 'web',

    /**
     * The URI to redirect after taking an impersonation.
     */
    'take_redirect_to' => '/dashboard',

    /**
     * The URI to redirect after leaving an impersonation.
     */
    'leave_redirect_to' => '/dashboard',
];
```

### 3. Routes

**File**: `routes/web.php`

The routes include audit trail logging for impersonation start and end events:

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Impersonation Routes
Route::middleware(['auth'])->group(function () {
    // Start impersonation
    Route::get('/impersonate/user/{user}', function ($user) {
        $userToImpersonate = App\Models\User::findOrFail($user);
        $currentUser = Auth::user();

        if (!$currentUser->canImpersonate() || !$userToImpersonate->canBeImpersonated()) {
            abort(403, 'Unauthorized action.');
        }

        // Log impersonation start to audit trail
        try {
            DB::table('audits')->insert([
                'user_type' => 'App\\Models\\User',
                'user_id' => $currentUser->id,
                'event' => 'impersonate_start',
                'auditable_type' => 'App\\Models\\User',
                'auditable_id' => $userToImpersonate->id,
                'old_values' => '{}',
                'new_values' => json_encode([
                    'impersonator_id' => $currentUser->id,
                    'impersonator_name' => $currentUser->nama,
                    'impersonated_id' => $userToImpersonate->id,
                    'impersonated_name' => $userToImpersonate->nama,
                ]),
                'url' => request()->fullUrl(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'tags' => 'impersonation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('Impersonation start logged successfully', [
                'user' => $currentUser->id, 
                'target' => $userToImpersonate->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log impersonation start', [
                'error' => $e->getMessage(),
                'user' => $currentUser->id,
                'target' => $userToImpersonate->id
            ]);
        }

        $currentUser->impersonate($userToImpersonate);

        return redirect('/dashboard')->with('impersonate_success', 'You are now impersonating ' . $userToImpersonate->nama);
    })->name('impersonate');

    // Leave impersonation
    Route::get('/impersonate/leave', function () {
        $currentUser = Auth::user();

        if ($currentUser->isImpersonated()) {
            // Get impersonator info before leaving
            $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);
            $impersonatorId = $manager->getImpersonatorId();
            $impersonatedId = $currentUser->getKey();
            $impersonatedName = $currentUser->nama;

            // Leave impersonation
            $currentUser->leaveImpersonation();

            // Get impersonator user for audit log
            $impersonator = App\Models\User::find($impersonatorId);

            // Log impersonation end to audit trail
            try {
                DB::table('audits')->insert([
                    'user_type' => 'App\\Models\\User',
                    'user_id' => $impersonatorId,
                    'event' => 'impersonate_end',
                    'auditable_type' => 'App\\Models\\User',
                    'auditable_id' => $impersonatedId,
                    'old_values' => json_encode([
                        'impersonated_id' => $impersonatedId,
                        'impersonated_name' => $impersonatedName,
                    ]),
                    'new_values' => json_encode([
                        'impersonator_id' => $impersonatorId,
                        'impersonator_name' => $impersonator ? $impersonator->nama : 'Unknown',
                    ]),
                    'url' => request()->fullUrl(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'tags' => 'impersonation',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info('Impersonation end logged successfully', [
                    'impersonator' => $impersonatorId, 
                    'impersonated' => $impersonatedId
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log impersonation end', [
                    'error' => $e->getMessage(),
                    'impersonator' => $impersonatorId,
                    'impersonated' => $impersonatedId
                ]);
            }
        }

        return redirect('/dashboard')->with('impersonate_leave', 'You have stopped impersonating.');
    })->name('impersonate.leave');
});
```

### 4. Middleware (Audit Logging)

**File**: `app/Http/Middleware/ImpersonateMiddleware.php`

This middleware logs all impersonation actions to both Laravel log files and the audit trail database:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is impersonating and add a header/log entry for audit purposes
        $user = Auth::user();

        if ($user && $user->isImpersonated()) {
            // Add logging for impersonation actions
            $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);
            $impersonatorId = $manager->getImpersonatorId();
            $impersonatedId = $user->getKey();
            
            // Log to Laravel log file
            logger()->info('Impersonation action performed', [
                'impersonator_id' => $impersonatorId,
                'impersonated_id' => $impersonatedId,
                'action' => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Also log to audit trail table (only for non-GET requests to avoid clutter)
            if (!$request->isMethod('GET') && !str_contains($request->path(), 'livewire')) {
                try {
                    DB::table('audits')->insert([
                        'user_type' => 'App\\Models\\User',
                        'user_id' => $impersonatorId, // Log under impersonator's ID
                        'event' => 'impersonate_action',
                        'auditable_type' => 'App\\Models\\User',
                        'auditable_id' => $impersonatedId,
                        'old_values' => json_encode([]),
                        'new_values' => json_encode([
                            'impersonator_id' => $impersonatorId,
                            'impersonated_id' => $impersonatedId,
                            'action' => $request->method() . ' ' . $request->path(),
                        ]),
                        'url' => $request->fullUrl(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'tags' => 'impersonation',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    // Silently fail if audit logging fails to not break the application
                    logger()->error('Failed to log impersonation action to audit trail', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $next($request);
    }
}
```

**Registration**: `bootstrap/app.php`

The middleware is registered in the web middleware group:

```php
use App\Http\Middleware\ImpersonateMiddleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(LocaleMiddleware::class);
    $middleware->web(ImpersonateMiddleware::class); // Registered here
    // ... other middleware
  })
  ->create();
```

### 5. Controller - User Management

**File**: `app/Http/Controllers/Configuration/UserManagementController.php`

The impersonate button is conditionally rendered in the user list DataTables:

```php
->addColumn('action', function ($user) {
    // Only show impersonate button - exclude edit and delete buttons
    $impersonateBtn = '';
    $currentUser = Auth::user();

    if ($currentUser && $currentUser->canImpersonate() && $user->canBeImpersonated()) {
        $impersonateUrl = route('impersonate', ['user' => $user->id]);
        $impersonateBtn = '<a href="'.e($impersonateUrl).'" class="btn btn-icon btn-text-warning rounded-pill btn-sm impersonate-user" data-bs-toggle="tooltip" data-bs-placement="top" title="Impersonate User" data-user-name="'.e($user->nama).'">
            <i class="bx bx-user-check"></i>
        </a>';
    }

    return '<div class="d-inline-block text-nowrap">' . $impersonateBtn . '</div>';
})
```

### 6. Frontend - Dashboard Alert Card

**File**: `resources/views/dashboard/partials/impersonate-card.blade.php`

A warning banner is displayed at the top of the dashboard when a user is being impersonated:

```blade
@if(auth()->user() && auth()->user()->isImpersonated())
<div class="card mb-6 border-warning">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <i class="bx bx-user-circle text-warning me-2 fs-4"></i>
                    <h5 class="mb-0 text-warning">Mode Ambil Alih Aktif</h5>
                </div>
                <p class="mb-2">
                    <strong>Anda sedang menggunakan akaun:</strong>
                    <span class="text-primary fw-bold">{{ auth()->user()->nama }}</span>
                    @if(auth()->user()->email)
                        <span class="text-muted">({{ auth()->user()->email }})</span>
                    @endif
                </p>
                <p class="mb-0 text-muted small">
                    <i class="bx bx-info-circle me-1"></i>
                    Semua tindakan yang dilakukan akan dikaitkan dengan akaun pengguna yang sedang diambil alih.
                </p>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('impersonate.leave') }}" class="btn btn-outline-danger">
                    <i class="bx bx-log-out me-1"></i>
                    Keluar dari Mode Ambil Alih
                </a>
            </div>
        </div>
    </div>
</div>
@endif
```

**Include in Dashboard**: `resources/views/dashboard/dashboard.blade.php`

```blade
<!-- Impersonate Alert (if active) -->
@include('dashboard/partials/impersonate-card')
```

### 7. Frontend - Session Flash Messages

**File**: `resources/views/dashboard/dashboard.blade.php`

Success and info messages are displayed when impersonation starts or ends:

```blade
@if (session('impersonate_success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>
        {{ session('impersonate_success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('impersonate_leave'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bx bx-info-circle me-2"></i>
        {{ session('impersonate_leave') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
```

### 8. Frontend - JavaScript Confirmation

**File**: `resources/assets/js/user/app-user-list.js`

SweetAlert2 confirmation dialogs with client-side validation:

```javascript
// Handle impersonate button clicks with confirmation
$(document).on('click', '.impersonate-user', function(e) {
  e.preventDefault();

  const impersonateUrl = $(this).attr('href');
  const userName = $(this).data('user-name') || 'this user';

  // Get the row data to check user role
  const $row = $(this).closest('tr');
  const rowData = dt_user.row($row).data();
  const userRole = rowData ? rowData.role : null;

  // Additional client-side validation for Administrator roles
  if (userRole === 'Administrator') {
    Swal.fire({
      title: 'Tindakan Tidak Dibenarkan',
      html: `Pengguna dengan peranan <strong>Administrator</strong> tidak boleh diambil alih.<br><small class="text-muted">Hanya Administrator boleh mengambil alih pengguna lain, tetapi tidak boleh mengambil alih sesama Administrator.</small>`,
      icon: 'error',
      confirmButtonColor: '#696cff',
      confirmButtonText: '<i class="bx bx-check me-1"></i>Faham'
    });
    return;
  }

  // Show confirmation dialog using SweetAlert2
  Swal.fire({
    title: 'Ambil Alih Pengguna',
    html: `Adakah anda pasti untuk mengambil alih sebagai Pengguna <strong>${userName}</strong>?<br><small class="text-muted">Semua tindakan akan dilakukan sebagai pengguna ini. Peranan: <span class="badge bg-primary">${userRole || 'Tidak diketahui'}</span></small>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#696cff',
    cancelButtonColor: '#8592a3',
    confirmButtonText: '<i class="bx bx-user-check me-1"></i>Ya, Ambil Alih!',
    cancelButtonText: '<i class="bx bx-x me-1"></i>Batal',
    showDenyButton: false
  }).then((result) => {
    if (result.isConfirmed) {
      // Show loading state
      Swal.fire({
        title: 'Mengambil Alih...',
        text: 'Sila tunggu semasa kami menukar ke akaun pengguna.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Redirect to impersonate URL
      window.location.href = impersonateUrl;
    }
  });
});
```

## Usage Flow

### Starting Impersonation

1. Administrator navigates to User Management page
2. Click the **impersonate icon** (user-check icon) next to a user
3. SweetAlert2 confirmation dialog appears with user details
4. If confirmed, user is redirected to dashboard as the impersonated user
5. Warning banner appears at the top showing active impersonation mode
6. Success flash message is displayed

### During Impersonation

- All actions are performed as the impersonated user
- Warning card remains visible on dashboard
- All impersonation activities are logged to the audit trail:
  - Start impersonation logged with `impersonate_start` event
  - POST/PUT/PATCH/DELETE actions logged with `impersonate_action` event
  - End impersonation logged with `impersonate_end` event
- Session stores the original user ID for reverting
- Both impersonator and impersonated user IDs are tracked in all audit records

### Leaving Impersonation

1. Click **"Keluar dari Mode Ambil Alih"** button in the warning card
2. User is returned to their original account
3. Info flash message is displayed confirming exit from impersonation mode

## Security Features

### Server-Side Validation

- `canImpersonate()` checks if current user has Administrator role
- `canBeImpersonated()` prevents impersonation of Administrator accounts
- Route middleware ensures only authenticated users can access impersonation routes
- 403 error is thrown for unauthorized attempts

### Client-Side Validation

- JavaScript prevents clicking impersonate button for Administrator users
- Clear error messages explain why action is not allowed
- Role-based visibility of impersonate buttons

### Audit Logging

The system provides comprehensive audit trail logging for all impersonation activities:

#### Database Audit Trail (`audits` table)

**Impersonation Start** (`impersonate_start` event):
- Logged when administrator starts impersonating a user
- Records:
  - Impersonator ID and name
  - Impersonated user ID and name
  - IP address
  - User agent
  - Full URL
  - Timestamp

**Impersonation End** (`impersonate_end` event):
- Logged when administrator stops impersonating
- Records:
  - Impersonator ID and name (who was impersonating)
  - Impersonated user ID and name (who was being impersonated)
  - IP address
  - User agent
  - Full URL
  - Timestamp

**Impersonation Actions** (`impersonate_action` event):
- Logged for POST, PUT, PATCH, DELETE requests during active impersonation
- GET requests excluded to avoid excessive logging
- Records:
  - Impersonator ID (logged under impersonator's account)
  - Impersonated user ID
  - Action performed (HTTP method + path)
  - IP address
  - User agent
  - Full URL
  - Timestamp

#### Laravel Log Files

All impersonation activities are also logged to `storage/logs/laravel-{date}.log` for additional debugging:
- Impersonation start/end success/failure
- All actions performed during impersonation (including GET requests)
- Any errors during audit logging

#### Viewing Audit Trail

Navigate to **Audit Trail** page in the system to view:
- Filter by event: `impersonate_start`, `impersonate_end`, or `impersonate_action`
- Filter by tag: `impersonation`
- View detailed information including who impersonated whom
- See IP addresses and user agents for security monitoring

## Available Methods

The `Impersonate` trait provides several helper methods:

```php
// Check if user can impersonate others
$user->canImpersonate(); // Returns bool

// Check if user can be impersonated
$user->canBeImpersonated(); // Returns bool

// Check if current user is being impersonated
$user->isImpersonated(); // Returns bool

// Start impersonating another user
$currentUser->impersonate($targetUser);

// Stop impersonating and return to original user
$currentUser->leaveImpersonation();
```

## Querying Audit Records

### Via Database Query

You can query impersonation audit records directly:

```php
use OwenIt\Auditing\Models\Audit;

// Get all impersonation start events
$impersonationStarts = Audit::where('event', 'impersonate_start')
    ->where('tags', 'impersonation')
    ->with('user')
    ->orderBy('created_at', 'desc')
    ->get();

// Get impersonation activities for a specific administrator
$adminId = 14;
$adminImpersonations = Audit::where('user_id', $adminId)
    ->whereIn('event', ['impersonate_start', 'impersonate_end', 'impersonate_action'])
    ->where('tags', 'impersonation')
    ->orderBy('created_at', 'desc')
    ->get();

// Get when a specific user was impersonated
$targetUserId = 7;
$impersonatedEvents = Audit::where('auditable_id', $targetUserId)
    ->where('auditable_type', 'App\\Models\\User')
    ->whereIn('event', ['impersonate_start', 'impersonate_end'])
    ->where('tags', 'impersonation')
    ->with('user')
    ->get();

// Get all actions performed during impersonation
$impersonationActions = Audit::where('event', 'impersonate_action')
    ->where('tags', 'impersonation')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Audit Record Structure

Each impersonation audit record contains:

```json
{
  "id": 123,
  "user_type": "App\\Models\\User",
  "user_id": 14,  // Administrator (impersonator) ID
  "event": "impersonate_start",  // or impersonate_end, impersonate_action
  "auditable_type": "App\\Models\\User",
  "auditable_id": 7,  // Target user (impersonated) ID
  "old_values": {},
  "new_values": {
    "impersonator_id": 14,
    "impersonator_name": "SHAMSUL ADLI BIN MOHD. SHAH",
    "impersonated_id": 7,
    "impersonated_name": "John Doe"
  },
  "url": "http://localhost/impersonate/user/7",
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0...",
  "tags": "impersonation",
  "created_at": "2026-02-11 15:53:00",
  "updated_at": "2026-02-11 15:53:00"
}
```

### Via Audit Trail UI

Navigate to `/audit-trail` in your application:
1. Access the Audit Trail page (requires `audit.view` permission)
2. Filter by tag: select "impersonation"
3. Filter by event type: `impersonate_start`, `impersonate_end`, or `impersonate_action`
4. Filter by user: select administrator to see who they impersonated
5. Click "Lihat Detail" to view full audit record details

## Testing Scenarios

### Valid Impersonation

1. **Administrator → Helpdesk Staff**
   - ✅ Should work
   - Administrator can impersonate Helpdesk staff

2. **Administrator → Pengadu (Complainant)**
   - ✅ Should work
   - Administrator can impersonate any non-admin user

### Invalid Impersonation (Should Fail)

1. **Administrator → Another Administrator**
   - ❌ Should be blocked
   - Administrators cannot impersonate each other

2. **Helpdesk Staff → Any User**
   - ❌ Should be blocked
   - Only Administrators can impersonate

3. **Non-Admin → Any User**
   - ❌ Should be blocked
   - Button should not be visible

## Troubleshooting

### Impersonate Button Not Showing

**Check**:
1. Current user has `Administrator` role
2. Target user does NOT have `Administrator` role
3. Both users are active accounts
4. Proper permissions are assigned

### Cannot Leave Impersonation

**Solution**:
- Clear browser cache and session
- Check if `impersonate.leave` route exists
- Verify session is not expired

### Actions Not Logged

**Check**:
1. `ImpersonateMiddleware` is registered in `bootstrap/app.php`
2. Database connection is working properly
3. `audits` table exists in database
4. Check `storage/logs/laravel-{date}.log` for error messages
5. Verify `DB` and `Log` facades are imported in `routes/web.php`
6. Test database insert manually:
   ```php
   DB::table('audits')->count(); // Should return a number
   ```

**Common Issues**:
- **"Class DB not found"**: Add `use Illuminate\Support\Facades\DB;` to routes file
- **"Class Log not found"**: Add `use Illuminate\Support\Facades\Log;` to routes file
- **Silent failure**: Check Laravel log files for exception messages
- **Database error**: Verify `audits` table schema matches migration

### Viewing Audit Logs

**Via UI**:
1. Navigate to `/audit-trail`
2. Use filters:
   - Tag: `impersonation`
   - Event: `impersonate_start`, `impersonate_end`, or `impersonate_action`
   - User: Select administrator

**Via Database**:
```sql
-- View all impersonation events
SELECT * FROM audits 
WHERE tags = 'impersonation' 
ORDER BY created_at DESC;

-- View who impersonated whom
SELECT 
    a.created_at,
    a.event,
    u1.nama as impersonator,
    u2.nama as impersonated,
    a.url,
    a.ip_address
FROM audits a
LEFT JOIN users u1 ON a.user_id = u1.id
LEFT JOIN users u2 ON a.auditable_id = u2.id
WHERE a.tags = 'impersonation'
ORDER BY a.created_at DESC;
```

## Best Practices

1. **Use Sparingly**: Only impersonate when absolutely necessary for support
2. **Inform Users**: Consider notifying users when their account is impersonated
3. **Time Limits**: Consider implementing automatic logout after a period
4. **Audit Reviews**: Regularly review impersonation logs for security compliance
   - Check audit trail weekly for unauthorized impersonation attempts
   - Review who impersonated whom and when
   - Monitor for suspicious patterns or excessive impersonation
5. **Session Management**: Ensure proper session timeout handling
6. **Documentation**: Always document why impersonation was used
7. **Security Monitoring**: Use the audit trail to:
   - Track administrator activities
   - Detect potential security breaches
   - Generate compliance reports
   - Investigate user complaints or issues

## Related Files

```
├── app/
│   ├── Models/
│   │   └── User.php                    # Impersonate trait & methods
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Configuration/
│   │   │       └── UserManagementController.php
│   │   └── Middleware/
│   │       └── ImpersonateMiddleware.php
├── config/
│   ├── laravel-impersonate.php         # Package configuration
│   └── audit.php                       # Audit trail configuration
├── routes/
│   └── web.php                         # Impersonation routes with audit logging
├── bootstrap/
│   └── app.php                         # Middleware registration
├── database/
│   └── migrations/
│       └── 2025_09_02_074329_create_audits_table.php
├── resources/
│   ├── views/
│   │   └── dashboard/
│   │       ├── dashboard.blade.php     # Includes impersonate card
│   │       └── partials/
│   │           └── impersonate-card.blade.php
│   └── assets/
│       └── js/
│           └── user/
│               └── app-user-list.js    # Frontend logic
└── composer.json                       # Package dependency
```

## References

- **Package Documentation**: https://github.com/404labfr/laravel-impersonate
- **Audit Trail Package**: https://github.com/owen-it/laravel-auditing
- **Spatie Permission Package**: Used for role checking
- **Laravel Session Management**: Session keys for storing impersonation state
- **Database**: `audits` table stores all impersonation events

### Audit Trail Events Reference

| Event | Description | user_id | auditable_id |
|-------|-------------|---------|--------------|
| `impersonate_start` | Admin starts impersonating | Admin ID (impersonator) | Target User ID |
| `impersonate_action` | Action during impersonation | Admin ID (impersonator) | Target User ID |
| `impersonate_end` | Admin stops impersonating | Admin ID (impersonator) | Target User ID |

All events are tagged with `impersonation` for filtering purposes.

---

**Last Updated**: February 11, 2026  
**Version**: 2.0  
**Maintained By**: Development Team
