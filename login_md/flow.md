# Modul Login & Autentikasi - Aliran Data

## Aliran Pemilihan Mod Autentikasi

```
bootstrap/app.php
    └── Daftarkan middleware alias: 'cas.auth' → AuthCas::class

routes/web.php (dimuatkan semasa boot)
    │
    ├── Baca config('cas.cas_enabled')
    │
    ├── CAS_ENABLED=true  → $authMiddleware = ['cas.auth']
    │
    └── CAS_ENABLED=false → $authMiddleware = ['auth', 'auth_session']
                                                │
                                      Semua route dilindungi
                                      menggunakan $authMiddleware
```

---

## Aliran 1: CAS/SATUID Authentication (CAS_ENABLED=true)

### Log Masuk CAS

```
┌─────────────────────────────────────────────────────────┐
│  1. Pengguna akses URL dilindungi                        │
│     Contoh: GET /dashboard                              │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  2. Middleware: cas.auth → AuthCas::handle()            │
│     - Semak Auth::check()                               │
│     - Belum log masuk, tiada ?ticket                    │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  3. AuthCas::redirectToCasLogin()                       │
│     - Bina URL: https://satuid.treasury.gov.my/gk/login │
│     - Tambah: ?service={url_kembali_eHelp}              │
│     - return redirect($loginUrl)                        │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  4. Browser redirect ke SATUID                          │
│     https://satuid.treasury.gov.my/gk/login             │
│     ?service=https://ehelp.test/dashboard               │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  5. Pengguna masukkan credentials di SATUID             │
│     (IC/No. Pekerja + Kata Laluan)                      │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  6. SATUID redirect balik ke eHelp dengan ticket        │
│     GET /dashboard?ticket=ST-xxxxxxxxxxxxxxxx           │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  7. AuthCas::handle() semak $request->query('ticket')   │
│     Ticket ditemui → panggil validateTicketAndGetUser() │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  8. AuthCas::validateTicketAndGetUser()                 │
│     - Bina URL validasi:                                │
│       https://satuid.treasury.gov.my/gk/serviceValidate │
│       ?service={url_eHelp}&ticket={ST-ticket}           │
│     - HTTP request ke SATUID via file_get_contents()    │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  9. SATUID kembalikan XML response                      │
│                                                         │
│  Berjaya:                   Gagal:                      │
│  <cas:serviceResponse>      <cas:serviceResponse>       │
│    <cas:authSuccess>          <cas:authFailure>         │
│      <cas:user>ldapId</cas:user>  INVALID_TICKET        │
│    </cas:authSuccess>         </cas:authFailure>        │
│  </cas:serviceResponse>     </cas:serviceResponse>      │
└──────────────────┬──────────────────────────────────────┘
                   │
          ┌────────┴────────┐
          │ Berjaya         │ Gagal
          ▼                 ▼
┌─────────────────┐  ┌────────────────────────────────────┐
│ 10. Parse XML   │  │ Log::warning() + return null       │
│     Ambil       │  │ → return errorResponse(401)        │
│     //cas:user  │  └────────────────────────────────────┘
│     (ldapId)    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│  11. Cari user dalam database                           │
│      User::where('id_pgn_ldap', $ldapId)->first()       │
│                                                         │
│  Ditemui:              Tidak ditemui:                   │
│  return $user          Log::warning() + return null     │
│                        → errorResponse(401)             │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  12. Auth::login($user, true)                           │
│      - Wujudkan sesi Laravel                            │
│      - remember = true (kekal log masuk)                │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  13. Redirect ke URL bersih (tanpa ?ticket)             │
│      return redirect($request->url())                   │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  14. AuthCas::handle() dipanggil semula                 │
│      Auth::check() → true                               │
│      return $next($request) → halaman diminta dipapar   │
└──────────────────────────────────────────────────────────┘
```

---

### Log Keluar CAS

```
┌─────────────────────────────────────────────────────────┐
│  Pengguna klik butang "Log Keluar"                      │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  POST /cas-logout                                       │
│  Auth::logout()                                         │
│  $request->session()->invalidate()                      │
│  $request->session()->regenerateToken()                 │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Redirect ke SATUID Logout URL                          │
│  https://satuid.treasury.gov.my/gk/logout               │
└──────────────────────────────────────────────────────────┘
```

---

## Aliran 2: Local Authentication / Fortify (CAS_ENABLED=false)

### Log Masuk Local

```
┌─────────────────────────────────────────────────────────┐
│  1. Pengguna akses GET /login                           │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  2. Fortify: Paparan borang log masuk                   │
│     View: content.authentications.auth-login-basic      │
│     Fields: email, password, remember me                │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  3. Pengguna hantar POST /login                         │
│     { email, password, remember }                       │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  4. Fortify Pipeline Autentikasi                        │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ EnsureLoginIsNotThrottled                        │   │
│  │ Semak: < 5 percubaan/minit per {email}|{ip}     │   │
│  │ Gagal → HTTP 429 Too Many Requests              │   │
│  └───────────────────┬─────────────────────────────┘   │
│                      ▼                                  │
│  ┌─────────────────────────────────────────────────┐   │
│  │ AttemptToAuthenticate                            │   │
│  │ Auth::attempt(['email' => ..., 'password' => ]) │   │
│  │ Berjaya → teruskan                              │   │
│  │ Gagal → return error "credentials tidak sah"   │   │
│  └───────────────────┬─────────────────────────────┘   │
│                      ▼                                  │
│  ┌─────────────────────────────────────────────────┐   │
│  │ RedirectIfTwoFactorAuthenticatable               │   │
│  │ Semak: adakah user aktifkan 2FA?                │   │
│  │ Jika ya → redirect ke /two-factor-challenge     │   │
│  │ Jika tidak → teruskan                           │   │
│  └───────────────────┬─────────────────────────────┘   │
│                      ▼                                  │
│  ┌─────────────────────────────────────────────────┐   │
│  │ PrepareAuthenticatedSession                      │   │
│  │ Wujudkan sesi dan simpan user                   │   │
│  └───────────────────┬─────────────────────────────┘   │
└──────────────────────┼──────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────┐
│  5. Redirect ke home ('/')                              │
│     → Redirect ke /dashboard (via route definition)     │
└──────────────────────────────────────────────────────────┘
```

---

### Two-Factor Authentication (2FA)

```
┌─────────────────────────────────────────────────────────┐
│  (Disambung dari Pipeline: user ada 2FA diaktifkan)     │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Redirect ke GET /two-factor-challenge                  │
│  View: auth-two-steps-basic / auth-two-steps-cover      │
│  Paparan: Input kod OTP (TOTP / Recovery Code)          │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Pengguna hantar POST /two-factor-challenge             │
│  { code: "123456" }                                     │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  TwoFactorAuthenticatedSessionController::store()       │
│  Semak OTP menggunakan 2FA secret dalam user record     │
│  Rate limit: 5 percubaan/minit per sesi                 │
│                                                         │
│  Berjaya → Log masuk, redirect ke home                  │
│  Gagal   → Error + input semula                         │
└──────────────────────────────────────────────────────────┘
```

---

### Log Keluar Local

```
┌─────────────────────────────────────────────────────────┐
│  POST /logout (Fortify route)                           │
│  Auth::logout()                                         │
│  Session invalidate + regenerate CSRF token             │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Redirect ke /login                                     │
└──────────────────────────────────────────────────────────┘
```

---

## Aliran Pengurusan Sesi

### Session Lifecycle

```
Pengguna log masuk (CAS atau Local)
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│  Sesi dicipta dalam jadual `sessions`                   │
│  { id, user_id, ip_address, user_agent, payload,        │
│    last_activity }                                      │
│  Lifetime: 120 minit idle                               │
└──────────────────┬──────────────────────────────────────┘
                   │
         ┌─────────┴──────────┐
         │ Setiap request     │ Bila idle > 120 min
         ▼                    ▼
┌─────────────────┐  ┌────────────────────────────────────┐
│ `last_activity` │  │ Sesi tamat                         │
│ dikemaskini     │  │ Pengguna redirect ke /login         │
└─────────────────┘  └────────────────────────────────────┘
```

### Session Ping (Frontend)

```
JavaScript (setiap N minit)
         │
         ▼
POST /session/ping
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│  Response JSON:                                         │
│  {                                                      │
│    authenticated: true/false,                           │
│    remaining: {sisa_saat},                              │
│    timestamp: "2025-01-01T00:00:00Z"                    │
│  }                                                      │
└──────────────────────────────────────────────────────────┘
```

### Session Check (Frontend)

```
GET /session/check
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│  Response JSON:                                         │
│  {                                                      │
│    authenticated: true/false,                           │
│    user: { id: 1, name: "Ahmad" } | null,               │
│    timestamp: "2025-01-01T00:00:00Z"                    │
│  }                                                      │
└──────────────────────────────────────────────────────────┘
```

---

## Aliran Reset Kata Laluan (Local Mode Sahaja)

```
┌─────────────────────────────────────────────────────────┐
│  1. Pengguna klik "Lupa Kata Laluan"                    │
│     GET /forgot-password                                │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  2. Paparan borang: masukkan email                      │
│     POST /forgot-password { email }                     │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  3. Fortify hantar email dengan link reset              │
│     Token disimpan dalam `password_reset_tokens`        │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  4. Pengguna klik link dalam email                      │
│     GET /reset-password/{token}                         │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  5. Paparan borang kata laluan baru                     │
│     POST /reset-password { token, email, password,      │
│                           password_confirmation }        │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  6. ResetUserPassword::reset()                          │
│     - Validate token & email                            │
│     - Hash kata laluan baru                             │
│     - Padam token dari `password_reset_tokens`          │
│     - Redirect ke /login                                │
└──────────────────────────────────────────────────────────┘
```

---

## Aliran Impersonate

### Mulakan Impersonate

```
┌─────────────────────────────────────────────────────────┐
│  1. Admin klik butang "Impersonate" pada halaman        │
│     Pengurusan Pengguna                                 │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  2. GET /impersonate/user/{user_id}                     │
│     Middleware: auth (pastikan admin sudah log masuk)   │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  3. Semak Kebenaran                                     │
│     $currentUser->canImpersonate()                      │
│       → User::hasRole('Administrator') — mesti true     │
│     $userToImpersonate->canBeImpersonated()             │
│       → !hasRole('Administrator') — sasaran bukan admin │
│                                                         │
│     Gagal → abort(403) Unauthorized                     │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  4. Log Audit Trail (impersonate_start)                 │
│     DB::table('audits')->insert([                       │
│       event: 'impersonate_start',                       │
│       user_id: $currentUser->id,   ← impersonator       │
│       auditable_id: $target->id,   ← yang diimpersonate │
│       tags: 'impersonation'                             │
│     ])                                                  │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  5. $currentUser->impersonate($userToImpersonate)       │
│     (Lab404 Impersonate — simpan impersonator ID        │
│      dalam sesi, tukar Auth::user() ke target)          │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  6. Redirect ke /dashboard                              │
│     Flash: 'You are now impersonating {nama}'           │
│     Auth::user() kini mengembalikan pengguna sasaran    │
└──────────────────────────────────────────────────────────┘
```

### Semasa Sesi Impersonate (Setiap Request)

```
Setiap HTTP request semasa impersonate
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│  ImpersonateMiddleware::handle()                        │
│  $user->isImpersonated() → true                         │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Semua request:                                   │  │
│  │ logger()->info('Impersonation action', [          │  │
│  │   impersonator_id, impersonated_id, action, ip]) │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Non-GET (dan bukan Livewire) sahaja:              │  │
│  │ DB::table('audits')->insert([                    │  │
│  │   event: 'impersonate_action',                   │  │
│  │   user_id: impersonatorId,                       │  │
│  │   action: 'POST /ticket/create'                  │  │
│  │ ])                                               │  │
│  └──────────────────────────────────────────────────┘  │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
             $next($request)
```

### Tamatkan Impersonate

```
┌─────────────────────────────────────────────────────────┐
│  Admin klik butang "Stop Impersonating"                 │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  GET /impersonate/leave                                 │
│  Semak $currentUser->isImpersonated()                   │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Ambil maklumat dari ImpersonateManager                 │
│  $manager->getImpersonatorId()                          │
│  (sebelum leave — sesi masih ada)                       │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  $currentUser->leaveImpersonation()                     │
│  (Auth::user() kembali ke impersonator asal)            │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Log Audit Trail (impersonate_end)                      │
│  DB::table('audits')->insert([                          │
│    event: 'impersonate_end',                            │
│    user_id: $impersonatorId,                            │
│    auditable_id: $impersonatedId,                       │
│    tags: 'impersonation'                                │
│  ])                                                     │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Redirect ke /dashboard                                 │
│  Flash: 'You have stopped impersonating.'               │
│  Auth::user() kembali ke akaun Administrator asal      │
└──────────────────────────────────────────────────────────┘
```

---

## Ringkasan Perbezaan Dua Mod

```
┌──────────────────────────────┬─────────────────────────────────┐
│  CAS / SATUID                │  Local / Fortify                │
│  (CAS_ENABLED=true)          │  (CAS_ENABLED=false)            │
├──────────────────────────────┼─────────────────────────────────┤
│  Middleware: cas.auth        │  Middleware: auth + auth_session │
│  AuthCas::class              │  Fortify built-in               │
├──────────────────────────────┼─────────────────────────────────┤
│  Login di: SATUID portal     │  Login di: /login (eHelp)       │
├──────────────────────────────┼─────────────────────────────────┤
│  Credentials: SATUID IC/     │  Credentials: email + password  │
│  No. Pekerja                 │  dalam database `users`         │
├──────────────────────────────┼─────────────────────────────────┤
│  User lookup: id_pgn_ldap    │  User lookup: email             │
├──────────────────────────────┼─────────────────────────────────┤
│  Logout: /cas-logout         │  Logout: /logout (Fortify)      │
│          → SATUID logout     │          → /login               │
├──────────────────────────────┼─────────────────────────────────┤
│  2FA: Tidak disokong         │  2FA: Disokong (Fortify TOTP)   │
├──────────────────────────────┼─────────────────────────────────┤
│  Lupa kata laluan: Tidak ada │  Lupa kata laluan: /forgot-     │
│  (diurus oleh SATUID)        │  password (email reset)         │
└──────────────────────────────┴─────────────────────────────────┘
```

---

## Hubungan Antara Fail

```
.env (CAS_ENABLED)
    │
    ▼
config/cas.php
    │
    ▼
routes/web.php ($authMiddleware)
    │
    ├── CAS_ENABLED=true
    │       │
    │       ▼
    │   bootstrap/app.php
    │   alias: 'cas.auth' → AuthCas::class
    │       │
    │       ▼
    │   app/Http/Middleware/AuthCas.php
    │       │
    │       ├── config/cas.php (hostname, port, uri, validate_path)
    │       │
    │       └── app/Models/User.php
    │           (lookup via id_pgn_ldap)
    │
    └── CAS_ENABLED=false
            │
            ▼
        Laravel Fortify (routes auto-registered)
            │
            ▼
        app/Providers/FortifyServiceProvider.php
            │
            ├── Pipeline: EnsureLoginIsNotThrottled
            │             AttemptToAuthenticate
            │             RedirectIfTwoFactorAuthenticatable
            │             PrepareAuthenticatedSession
            │
            └── app/Actions/Fortify/
                ├── CreateNewUser.php
                ├── UpdateUserPassword.php
                ├── ResetUserPassword.php
                └── UpdateUserProfileInformation.php
```
