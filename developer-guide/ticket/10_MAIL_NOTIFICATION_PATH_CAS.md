# 📧 Email Notification Flow dengan CAS Authentication

## 📋 Ringkasan Sistem

Sistem email notification untuk ticket management menggunakan **SatuID CAS Authentication** untuk memastikan semua akses ke ticket adalah secure dan authenticated.

---

## 🔄 Flow Lengkap: Klik Button "Lihat Tiket"

```
┌─────────────────────────────────────────────────────────────────────┐
│ 1. User terima email                                                │
│    - Email template render dengan button "Lihat Tiket"              │
│    - URL: route('tickets.show', $ticket->uuid)                      │
│    - Example: http://ehelp-fork_29122025.test/tickets/{uuid}        │
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 2. User klik button → Browser request ke /tickets/{uuid}            │
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 3. Route middleware 'cas.auth' intercepts request                   │
│    File: routes/web.php (line 381-389)                              │
│    Route::middleware(['cas.auth'])->prefix('tickets')               │
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 4. AuthCas Middleware check authentication                          │
│    File: app/Http/Middleware/AuthCas.php                            │
│                                                                     │
│    IF Auth::check() === TRUE                                        │
│    ├─> User sudah login → Continue to Controller                    │
│    │                                                                │
│    ELSE                                                             │
│    └─> Redirect ke SatuID Login                                     │
│        URL: https://satuid.treasury.gov.my/gk/login?service={url}   │
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 5. User login di SatuID Portal                                      │
│    - Enter credentials                                              │
│    - SatuID validates user                                          │
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 6. SatuID redirect balik dengan ticket                              │
│    URL: http://ehelp-fork_29122025.test/tickets/{uuid}?ticket=ST-xxx│
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 7. AuthCas Middleware validate ticket                               │
│    - Call SatuID API: /gk/serviceValidate                           │
│    - Parse XML response                                             │
│    - Extract LDAP ID                                                │
│    - Find user in database (id_pgn_ldap)                            │
│    - Auth::login($user)                                             │
│    - Redirect to clean URL (without ticket parameter)               │
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 8. TicketController@show dipanggil                                  │
│    File: app/Http/Controllers/Ticket/TicketController.php           │
│    - Load ticket dengan UUID                                        │
│    - Load relationships (user, category, comments, etc)             │
│    - Return view: tickets.show                                      │
└─────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────┐
│ 9. User view ticket page                                            │
│    File: resources/views/tickets/show.blade.php                     │
│    - Display ticket details                                         │
│    - Show comments, attachments, history                            │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📁 Files Yang Terlibat

### 1. **Email Templates** (4 files)
Location: `resources/views/emails/ticket/`

#### a) `pengadu-status.blade.php`
- **Triggered:** Bila status ticket berubah
- **Recipients:** Pengadu (ticket creator)
- **Button URL:** `route('tickets.show', $ticket->uuid)`
- **Purpose:** Notify pengadu about ticket status changes

```blade
@component('mail::button', ['url' => route('tickets.show', $ticket->uuid)])
Lihat Tiket
@endcomponent
```

#### b) `pegawai-tugasan-status.blade.php`
- **Triggered:** Bila ticket ditugaskan atau status berubah
- **Recipients:** Pegawai tugasan (assigned officer)
- **Button URL:** `route('tickets.show', $ticket->uuid)`
- **Purpose:** Notify officer about new assignments or updates

```blade
@component('mail::button', ['url' => route('tickets.show', $ticket->uuid)])
Lihat Tiket
@endcomponent
```

#### c) `due-date-alert.blade.php`
- **Triggered:** Bila ticket approaching due date
- **Recipients:** Assigned officer & stakeholders
- **Button URL:** `route('tickets.show', $ticket->uuid)`
- **Purpose:** Warn about upcoming deadline

```blade
@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => 'error'])
Lihat Tiket
@endcomponent
```

#### d) `sla-overdue.blade.php`
- **Triggered:** Bila ticket melepasi SLA deadline
- **Recipients:** Assigned officer, supervisors
- **Button URL:** `route('tickets.show', $ticket->uuid)`
- **Purpose:** Alert about overdue tickets

```blade
@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => 'error'])
Lihat Tiket
@endcomponent
```

---

### 2. **Notification Classes** (4 files)
Location: `app/Notifications/Ticket/`

#### a) `PengaduStatusChangedNotification.php`
```php
public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Status Tiket Dikemaskini')
        ->markdown('emails.ticket.pengadu-status', [
            'ticket' => $this->ticket,
            'statusName' => $this->statusName,
            'assignedUser' => $this->assignedUser
        ]);
}
```

#### b) `PegawaiTugasanStatusChangedNotification.php`
```php
public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Tiket Baru Ditugaskan')
        ->markdown('emails.ticket.pegawai-tugasan-status', [
            'ticket' => $this->ticket,
            'newStatus' => $this->newStatus,
            'assignedUser' => $this->assignedUser
        ]);
}
```

#### c) `DueDateNotification.php`
```php
public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Peringatan Tarikh Tamat Tindakan')
        ->markdown('emails.ticket.due-date-alert', [
            'ticket' => $this->ticket
        ]);
}
```

#### d) `SLAOverdueNotification.php`
```php
public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('SLA Overdue - Tindakan Segera Diperlukan')
        ->markdown('emails.ticket.sla-overdue', [
            'ticket' => $this->ticket
        ]);
}
```

---

### 3. **Routes Configuration**
File: `routes/web.php`

```php
// Tickets Routes Group: Core ticket operations (TicketController only)
// Using 'cas.auth' middleware for SatuID authentication
Route::middleware(['cas.auth'])->prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/data', [TicketController::class, 'data'])->name('tickets.data');
    Route::post('/', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/{uuid}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/{uuid}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::put('/{uuid}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/{uuid}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::post('/{uuid}/update-status', [TicketController::class, 'updateTicketStatus'])->name('tickets.update-status');
});
```

**Key Points:**
- Semua routes dalam group ini protected dengan `cas.auth` middleware
- Route `tickets.show` adalah target utama dari email notifications
- Parameter `{uuid}` untuk identify ticket secara unique

---

### 4. **CAS Authentication Middleware**
File: `app/Http/Middleware/AuthCas.php`

```php
public function handle(Request $request, Closure $next): Response
{
    try {
        // Check if user is already logged in
        if (Auth::check()) {
            Log::info('User already authenticated', ['user_id' => Auth::id()]);
            return $next($request);
        }
        
        // Check if we have a CAS ticket (returning from SATUID)
        $ticket = $request->query('ticket');
        
        if ($ticket) {
            // Validate ticket with CAS server
            $serviceUrl = config('cas.cas_client_service') . $request->getPathInfo();
            $casServer = 'https://' . config('cas.cas_hostname') . ':' . config('cas.cas_port') . config('cas.cas_uri');
            $validateUrl = $casServer . '/serviceValidate?service=' . urlencode($serviceUrl) . '&ticket=' . urlencode($ticket);
            
            // Call CAS server to validate ticket
            $response = @file_get_contents($validateUrl, false, $context);
            
            // Parse XML response and get LDAP ID
            // Find user in database
            // Login user
            // Redirect to clean URL
            
            return redirect($request->getPathInfo());
        }
        
        // No ticket, redirect to CAS login
        $serviceUrl = config('cas.cas_client_service') . $request->getPathInfo();
        $casServer = 'https://' . config('cas.cas_hostname') . ':' . config('cas.cas_port') . config('cas.cas_uri');
        $loginUrl = $casServer . '/login?service=' . urlencode($serviceUrl);
        
        return redirect($loginUrl);
        
    } catch (\Throwable $e) {
        // Error handling
    }
}
```

**Key Functions:**
- ✅ Check existing authentication
- 🎫 Validate CAS ticket dari SatuID
- 🔐 Create Laravel session after successful validation
- 🔄 Redirect management (to SatuID, back from SatuID, clean URL)

---

### 5. **Ticket Controller**
File: `app/Http/Controllers/Ticket/TicketController.php`

```php
public function show($uuid)
{
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();
    
    // Load ticket with comprehensive relationships
    $ticket->load([
        'user.sektor',
        'user.bahagian',
        'category',
        'subCategory',
        'status',
        'createdBy',
        'updatedBy',
        'chronologies.status',
        'chronologies.createdBy',
        'chronologies.escalatedTo',
        'comments.user'
    ]);
    
    // Get ticket attachments
    $ticket->attachments = Document::where('doc_id', $ticket->id)
        ->where('doc_jenis', 'TICKET')
        ->get();
    
    // Get ticket history
    $ticketHistory = TicketChronologyController::getTicketHistory($ticket);
    
    // Get SLA data
    $currentSLA = Sla::where('cat_id', $ticket->cat_id)
        ->where('subcat_id', $ticket->subcat_id)
        ->with(['category', 'subCategory'])
        ->get();
    
    return view('tickets.show', compact('ticket', 'ticketHistory', 'allStatuses', 'allCategories', 'allSubCategories', 'officers', 'currentSLA'));
}
```

---

### 6. **CAS Configuration**
File: `config/cas.php`

```php
return [
    // CAS Server
    'cas_hostname' => env('CAS_HOSTNAME', 'satuid.treasury.gov.my'),
    'cas_port' => env('CAS_PORT', 443),
    'cas_uri' => env('CAS_URI', '/gk'),

    // Client Service (PENTING: Mesti sama dengan domain aplikasi)
    'cas_client_service' => env('CAS_CLIENT_SERVICE'),
    'cas_redirect_path' => env('CAS_REDIRECT_PATH', '/dashboard'),

    // Logout
    'cas_logout_url' => env('CAS_LOGOUT_URL', 'https://satuid.treasury.gov.my/user/dashboard/'),
    
    // Validation
    'cas_validation' => 'none', // SSL validation
];
```

---

### 7. **Environment Configuration**
File: `.env`

```env
# ============================================
# CAS AUTHENTICATION (SATUID)
# ============================================
CAS_HOSTNAME=satuid.treasury.gov.my
CAS_PORT=443
CAS_URI=/gk

# DEVELOPMENT: Local Herd URL
CAS_CLIENT_SERVICE=http://ehelp-fork_29122025.test

# PRODUCTION: Production URL
# CAS_CLIENT_SERVICE=https://ehelp.treasury.gov.my

CAS_REDIRECT_PATH=/dashboard
CAS_LOGOUT_URL=https://satuid.treasury.gov.my/user/dashboard/
CAS_VALIDATE_PATH=/gk/serviceValidate
# ============================================
```

---

## ⚙️ Configuration Setup

### Development Environment (Herd)
```env
CAS_CLIENT_SERVICE=http://ehelp-fork_29122025.test
```

### Production Environment
```env
CAS_CLIENT_SERVICE=https://ehelp.treasury.gov.my
```

**PENTING:** Setiap kali tukar nilai dalam `.env`, run command:
```bash
php artisan config:clear
php artisan optimize:clear
```

---

## 🔐 Security Features

### 1. **CAS Single Sign-On (SSO)**
- Centralized authentication melalui SatuID
- No password storage dalam aplikasi
- Secure token-based validation

### 2. **UUID-based Ticket Access**
- Ticket identified by UUID, bukan ID integer
- Harder to guess/brute force
- Example: `89a5251e-2278-43ce-b4ee-ee432e2a7ddf`

### 3. **Middleware Protection**
- Semua ticket routes protected dengan `cas.auth`
- Cannot bypass authentication
- Automatic redirect to login jika tidak authenticated

### 4. **Session Management**
- Laravel session created selepas CAS validation
- Shared session domain: `.treasury.gov.my`
- Automatic session cleanup on logout

---

## 📊 Email Notification Triggers

### 1. **Status Change Notification**
**Triggered by:** `TicketController@updateTicketStatus`

```php
// Notify pengadu
$ticket->user->notify(new PengaduStatusChangedNotification(
    $ticket, 
    $statusName, 
    $assignedUser
));

// Notify assigned officer
$assignedUser->notify(new PegawaiTugasanStatusChangedNotification(
    $ticket,
    $newStatus,
    $assignedUser
));
```

### 2. **Due Date Alert**
**Triggered by:** Scheduled command/cron job

```php
// Find tickets approaching due date
$tickets = Ticket::whereDate('tick_duedate', '=', Carbon::now()->addDays(3))
    ->where('tick_status', '!=', 6)
    ->get();

foreach ($tickets as $ticket) {
    $ticket->assignedUser->notify(new DueDateNotification($ticket));
}
```

### 3. **SLA Overdue Alert**
**Triggered by:** Scheduled command/cron job

```php
// Find overdue tickets
$tickets = Ticket::whereDate('tick_duedate', '<', Carbon::now())
    ->where('tick_status', '!=', 6)
    ->get();

foreach ($tickets as $ticket) {
    $ticket->assignedUser->notify(new SLAOverdueNotification($ticket));
}
```

---

## 🚨 Common Issues & Solutions

### Issue 1: URL redirect ke domain lain
**Symptom:** Link redirect ke `http://ehelp-dev.treasury.gov.my` instead of local
**Solution:** Check dan update `.env`:
```env
CAS_CLIENT_SERVICE=http://ehelp-fork_29122025.test
```
Then run: `php artisan config:clear`

### Issue 2: Infinite redirect loop
**Symptom:** Page keeps redirecting between app and SatuID
**Cause:** CAS ticket validation failed
**Solution:** 
- Check CAS_CLIENT_SERVICE domain sama dengan application domain
- Check SSL validation settings
- Check logs: `storage/logs/laravel.log`

### Issue 3: User not found after SatuID login
**Symptom:** Error "Akaun tidak dijumpai untuk LDAP ID"
**Cause:** User record tidak exist dalam database dengan LDAP ID yang betul
**Solution:** Pastikan field `id_pgn_ldap` dalam table `users` filled dengan LDAP ID dari SatuID

### Issue 4: Button URL tidak generate correctly
**Symptom:** Button link broken atau 404
**Cause:** Ticket UUID tidak pass correctly
**Solution:** Check notification class passing `$ticket` object (bukan `$ticket->id`)

---

## 🧪 Testing Flow

### Manual Testing Steps:

1. **Clear all caches:**
   ```bash
   php artisan optimize:clear
   ```

2. **Logout dari semua session:**
   - Clear browser cookies
   - Logout dari SatuID jika sudah login

3. **Trigger email notification:**
   ```bash
   php artisan tinker
   ```
   ```php
   $ticket = App\Models\Ticket::first();
   $ticket->user->notify(new App\Notifications\Ticket\PengaduStatusChangedNotification($ticket, 'Test', $ticket->user));
   ```

4. **Check email inbox** (Mailtrap untuk development)

5. **Click "Lihat Tiket" button**
   - Should redirect to SatuID login page
   - URL should contain `?service=http://ehelp-fork_29122025.test/tickets/{uuid}`

6. **Login dengan SatuID credentials**

7. **After successful login:**
   - Should redirect back to ticket page
   - URL should be clean: `http://ehelp-fork_29122025.test/tickets/{uuid}`
   - No `?ticket=ST-xxx` parameter in final URL

---

## 📝 Important Notes

### ⚠️ Critical Points:

1. **CAS_CLIENT_SERVICE must match application URL**
   - Development: `http://ehelp-fork_29122025.test`
   - Production: `https://ehelp.treasury.gov.my`
   - NO trailing slash

2. **All ticket routes must use 'cas.auth' middleware**
   - Ensures authentication before access
   - Automatic SatuID redirect

3. **Email templates use `route()` helper**
   - Dynamic URL generation
   - Automatically use correct domain dari config

4. **UUID parameter in routes and emails**
   - Always use `$ticket->uuid` NOT `$ticket->id`
   - More secure, tidak sequential

5. **Session management**
   - Session created after CAS validation
   - Persists across requests
   - Automatic cleanup on logout

---

## 🔧 Maintenance Checklist

### Before Production Deployment:

- [ ] Update `.env` dengan production URLs
- [ ] Set `CAS_CLIENT_SERVICE=https://ehelp.treasury.gov.my`
- [ ] Test CAS authentication flow
- [ ] Verify email notifications working
- [ ] Check all email templates render correctly
- [ ] Test button links redirect properly
- [ ] Verify SatuID integration
- [ ] Configure proper SSL certificates
- [ ] Set up cron jobs untuk due date alerts
- [ ] Configure mail server (SMTP)
- [ ] Test notification triggers
- [ ] Verify user LDAP ID mapping

### Regular Monitoring:

- [ ] Check email delivery rates
- [ ] Monitor CAS authentication logs
- [ ] Review failed login attempts
- [ ] Check notification queue status
- [ ] Verify cron jobs running
- [ ] Monitor ticket access patterns

---

## 📚 Related Documentation

- **SatuID Integration:** `/developer-guide/authentication/satuid-cas.md`
- **Email Configuration:** `/developer-guide/notifications/email-setup.md`
- **Ticket Management:** `/developer-guide/ticket/ticket-workflow.md`
- **Security Guidelines:** `/developer-guide/security-sanitization.md`

---

**Last Updated:** 27 January 2026
**Version:** 1.0
**Author:** System Administrator
