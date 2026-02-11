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
3. When impersonating, all actions are recorded under the impersonated user's account
4. A warning banner is displayed on the dashboard during active impersonation
5. Users can easily leave impersonation mode and return to their original account

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

```php
// Impersonation Routes
Route::middleware(['auth'])->group(function () {
    // Start impersonation
    Route::get('/impersonate/user/{user}', function ($user) {
        $userToImpersonate = App\Models\User::findOrFail($user);
        $currentUser = Auth::user();

        if (!$currentUser->canImpersonate() || !$userToImpersonate->canBeImpersonated()) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser->impersonate($userToImpersonate);

        return redirect('/dashboard')->with('impersonate_success', 'You are now impersonating ' . $userToImpersonate->nama);
    })->name('impersonate');

    // Leave impersonation
    Route::get('/impersonate/leave', function () {
        $currentUser = Auth::user();

        if ($currentUser->isImpersonated()) {
            $currentUser->leaveImpersonation();
        }

        return redirect('/dashboard')->with('impersonate_leave', 'You have stopped impersonating.');
    })->name('impersonate.leave');
});
```

### 4. Middleware (Logging)

**File**: `app/Http/Middleware/ImpersonateMiddleware.php`

This middleware logs all impersonation actions for audit purposes:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is impersonating and add a header/log entry for audit purposes
        $user = Auth::user();

        if ($user && $user->isImpersonated()) {
            // Add logging for impersonation actions
            $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);
            logger()->info('Impersonation action performed', [
                'impersonator_id' => $manager->getImpersonatorId(),
                'impersonated_id' => $user->getKey(),
                'action' => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        return $next($request);
    }
}
```

**Note**: This middleware needs to be registered in `bootstrap/app.php` if you want to use it globally.

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
- All actions are logged with both impersonator and impersonated user IDs
- Session stores the original user ID for reverting

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

- `ImpersonateMiddleware` logs all actions during impersonation
- Logs include:
  - Original user ID (impersonator)
  - Impersonated user ID
  - Action performed (HTTP method + path)
  - IP address
  - User agent
  - Timestamp

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
2. Logging configuration in `config/logging.php`
3. Log file permissions in `storage/logs/`

## Best Practices

1. **Use Sparingly**: Only impersonate when absolutely necessary for support
2. **Inform Users**: Consider notifying users when their account is impersonated
3. **Time Limits**: Consider implementing automatic logout after a period
4. **Audit Reviews**: Regularly review impersonation logs for security
5. **Session Management**: Ensure proper session timeout handling
6. **Documentation**: Always document why impersonation was used

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
│   └── laravel-impersonate.php         # Package configuration
├── routes/
│   └── web.php                         # Impersonation routes
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
- **Spatie Permission Package**: Used for role checking
- **Laravel Session Management**: Session keys for storing impersonation state

---

**Last Updated**: February 11, 2026  
**Version**: 1.0  
**Maintained By**: Development Team
