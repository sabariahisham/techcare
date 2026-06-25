# Modul Login & Autentikasi - Fail-Fail

## Middleware

### AuthCas
**Path**: `app/Http/Middleware/AuthCas.php`

**Namespace**: `App\Http\Middleware`

**Alias Route**: `cas.auth`

**Tujuan**: Mengendalikan autentikasi CAS/SATUID secara manual tanpa library phpCAS.

**Methods**:
- `handle(Request $request, Closure $next): Response` — Titik masuk middleware; semak auth, proses ticket, redirect ke SATUID
- `validateTicketAndGetUser(Request $request, string $ticket): ?User` — Hantar HTTP request ke `serviceValidate`, parse XML, cari user via `id_pgn_ldap`
- `redirectToCasLogin(Request $request): Response` — Bina URL login SATUID dengan `?service=` parameter dan redirect
- `errorResponse(string $message): Response` — Render view `errors.401` atau JSON error

**Aliran Dalaman**:
1. Semak `Auth::check()` — jika sudah log masuk, teruskan request
2. Semak query param `?ticket=` — jika ada, validate dengan SATUID
3. Parse XML response: cari `//cas:authenticationSuccess` dan `//cas:user`
4. Cari `User::where('id_pgn_ldap', $ldapId)->first()`
5. `Auth::login($user, true)` kemudian redirect ke URL bersih
6. Jika tiada ticket, redirect ke CAS login SATUID

**Dependencies**:
- `Auth` facade
- `User` model
- `config('cas.*')` untuk konfigurasi CAS

---

### ImpersonateMiddleware
**Path**: `app/Http/Middleware/ImpersonateMiddleware.php`

**Namespace**: `App\Http\Middleware`

**Tujuan**: Log setiap tindakan yang dilakukan semasa sesi impersonate ke dalam `laravel.log` dan jadual `audits`.

**Logik**:
- Semak `$user->isImpersonated()` pada setiap request
- Jika ya, ambil `impersonatorId` dari `ImpersonateManager`
- Log ke `logger()->info()` untuk semua request
- Log ke jadual `audits` (event: `impersonate_action`) untuk semua non-GET request
- GET request dan Livewire requests dikecualikan dari audit table (elak clutter)

**Didaftarkan**: Sebagai web middleware global dalam `bootstrap/app.php`

---

### LocaleMiddleware
**Path**: `app/Http/Middleware/LocaleMiddleware.php`

**Namespace**: `App\Http\Middleware`

**Tujuan**: Set bahasa antara muka aplikasi berdasarkan sesi atau tetapan pengguna.

**Didaftarkan**: Sebagai web middleware global dalam `bootstrap/app.php`

---

### ImpersonateMiddleware
**Path**: `app/Http/Middleware/ImpersonateMiddleware.php`

**Namespace**: `App\Http\Middleware`

**Tujuan**: Menyokong fungsi impersonate — admin boleh log masuk sebagai pengguna lain untuk tujuan debug/sokongan.

**Didaftarkan**: Sebagai web middleware global dalam `bootstrap/app.php`

---

## Controllers Autentikasi

### LoginBasic
**Path**: `app/Http/Controllers/authentications/LoginBasic.php`

**Namespace**: `App\Http\Controllers\authentications`

**Methods**:
- `index()` — Paparan halaman log masuk gaya "Basic" (satu kolum)

**View**: `content.authentications.auth-login-basic`

---

### LoginCover
**Path**: `app/Http/Controllers/authentications/LoginCover.php`

**Namespace**: `App\Http\Controllers\authentications`

**Methods**:
- `index()` — Paparan halaman log masuk gaya "Cover" (dua kolum dengan imej)

**View**: `content.authentications.auth-login-cover`

---

### Lain-Lain Auth Controllers
*(Semua dalam `app/Http/Controllers/authentications/`)*

| Fail | Tujuan |
|------|--------|
| `ForgotPasswordBasic.php` | Paparan borang lupa kata laluan (Basic) |
| `ForgotPasswordCover.php` | Paparan borang lupa kata laluan (Cover) |
| `RegisterBasic.php` | Paparan borang daftar akaun (Basic) |
| `RegisterCover.php` | Paparan borang daftar akaun (Cover) |
| `RegisterMultiSteps.php` | Daftar akaun berbilang langkah |
| `ResetPasswordBasic.php` | Reset kata laluan (Basic) |
| `ResetPasswordCover.php` | Reset kata laluan (Cover) |
| `TwoStepsBasic.php` | Cabaran 2FA (Basic) |
| `TwoStepsCover.php` | Cabaran 2FA (Cover) |
| `VerifyEmailBasic.php` | Pengesahan email (Basic) |
| `VerifyEmailCover.php` | Pengesahan email (Cover) |

---

## Routes Impersonate

```php
// Middleware: auth (bukan cas.auth — hanya perlukan log masuk)
Route::middleware(['auth'])->group(function () {

    // Mulakan impersonate
    GET /impersonate/user/{user}
    → Semak canImpersonate() dan canBeImpersonated()
    → Log event impersonate_start ke jadual audits
    → $currentUser->impersonate($userToImpersonate)
    → Redirect /dashboard                          [impersonate]

    // Tamatkan impersonate
    GET /impersonate/leave
    → Ambil maklumat impersonator dari ImpersonateManager
    → $currentUser->leaveImpersonation()
    → Log event impersonate_end ke jadual audits
    → Redirect /dashboard                          [impersonate.leave]
});
```

---

## Fortify Actions

### CreateNewUser
**Path**: `app/Actions/Fortify/CreateNewUser.php`

**Implements**: `Laravel\Fortify\Contracts\CreatesNewUsers`

**Tujuan**: Validate dan cipta pengguna baru semasa pendaftaran.

**Validasi**: `username`, `email` (unique), `password`, `terms`

---

### UpdateUserProfileInformation
**Path**: `app/Actions/Fortify/UpdateUserProfileInformation.php`

**Tujuan**: Kemaskini maklumat profil pengguna.

---

### UpdateUserPassword
**Path**: `app/Actions/Fortify/UpdateUserPassword.php`

**Tujuan**: Kemaskini kata laluan pengguna.

---

### ResetUserPassword
**Path**: `app/Actions/Fortify/ResetUserPassword.php`

**Tujuan**: Set kata laluan baru selepas reset.

---

### PasswordValidationRules
**Path**: `app/Actions/Fortify/PasswordValidationRules.php`

**Tujuan**: Trait yang mengandungi peraturan validasi kata laluan (digunakan oleh Create & Reset).

---

## Providers

### FortifyServiceProvider
**Path**: `app/Providers/FortifyServiceProvider.php`

**Namespace**: `App\Providers`

**Tujuan**: Bootstrap Fortify — daftarkan actions, pipeline autentikasi, dan rate limiter.

**Pipeline Autentikasi**:
```php
[
    EnsureLoginIsNotThrottled::class,
    AttemptToAuthenticate::class,
    RedirectIfTwoFactorAuthenticatable::class,
    PrepareAuthenticatedSession::class,
]
```

**Rate Limiters**:
- `login` — 5 percubaan/minit per `{email}|{ip}`
- `two-factor` — 5 percubaan/minit per sesi

---

## Model

### User
**Path**: `app/Models/User.php`

**Table**: `users`

**Medan Berkaitan Autentikasi**:

| Medan | Jenis | Keterangan |
|-------|-------|-----------|
| `id_pgn_ldap` | string | LDAP ID dari SATUID (kunci carian CAS) |
| `email` | string | Email untuk local login |
| `password` | string (hashed) | Kata laluan dienkripsi |
| `kodaktif` | boolean | Status aktif (`1` = aktif) |
| `lastlogin_at` | datetime | Tarikh/masa log masuk terakhir |
| `two_factor_secret` | string | Kunci rahsia 2FA (hidden) |
| `two_factor_recovery_codes` | text | Kod pemulihan 2FA (hidden) |

**Traits**:
- `HasApiTokens` — Sanctum token
- `TwoFactorAuthenticatable` — Fortify 2FA
- `HasRoles` + `HasPermissions` — Spatie
- `SoftDeletes` — Soft delete
- `Impersonate` — Lab404 impersonate — `canImpersonate()`: hanya `Administrator`; `canBeImpersonated()`: semua kecuali `Administrator`
- `HasProfilePhoto` — Jetstream foto profil

---

## Config Files

### config/cas.php
**Tujuan**: Semua tetapan untuk CAS/SATUID integration.

**Key Settings**:
| Key | Keterangan |
|-----|-----------|
| `cas_enabled` | Toggle CAS (env: `CAS_ENABLED`) |
| `cas_hostname` | Hos SATUID server |
| `cas_port` | Port (default: 443) |
| `cas_uri` | Base URI (default: `/gk`) |
| `cas_client_service` | URL aplikasi yang didaftarkan di SATUID |
| `cas_redirect_path` | Redirect selepas login berjaya |
| `cas_logout_url` | URL log keluar SATUID |
| `cas_logout_redirect` | Redirect selepas log keluar |
| `cas_validate_path` | Endpoint validate ticket (default: `/gk/serviceValidate`) |
| `cas_validation` | SSL mode: `ca`, `self`, `none` |

---

### config/fortify.php
**Tujuan**: Konfigurasi Laravel Fortify.

**Key Settings**:
| Key | Nilai | Keterangan |
|-----|-------|-----------|
| `guard` | `web` | Authentication guard |
| `passwords` | `users` | Password broker |
| `username` | `email` | Medan login utama |
| `lowercase_usernames` | `true` | Lowercase email sebelum simpan |
| `home` | `/` | Redirect selepas login berjaya |

---

### config/auth.php
**Tujuan**: Konfigurasi Laravel authentication.

**Guards**:
- `web` — driver: `session`, provider: `users` (Eloquent)

**Providers**:
- `users` — Model: `App\Models\User`

**Password Timeout**: 10800 saat (3 jam) untuk password confirmation

---

### config/session.php
**Tujuan**: Konfigurasi pengurusan sesi.

**Key Settings**:
| Key | Nilai Default | Keterangan |
|-----|--------------|-----------|
| `driver` | `database` | Sesi disimpan dalam jadual `sessions` |
| `lifetime` | `120` minit | Tempoh sesi idle |
| `expire_on_close` | `false` | Sesi tidak tamat bila browser ditutup |
| `table` | `sessions` | Jadual database untuk sesi |
| `cookie` | `{app_name}_session` | Nama cookie |
| `path` | `/` | Path cookie |
| `encrypt` | `false` | Enkripsi data sesi |

---

## Bootstrap

### bootstrap/app.php
**Tujuan**: Titik konfigurasi utama aplikasi Laravel 12 — daftarkan middleware.

**Middleware Didaftarkan**:
```php
$middleware->web(LocaleMiddleware::class);
$middleware->web(ImpersonateMiddleware::class);
$middleware->alias([
    'cas.auth' => AuthCas::class,
]);
```

---

## Routes

**Fail**: `routes/web.php`

### Middleware Dinamik
```php
$authMiddleware = config('cas.cas_enabled')
    ? ['cas.auth']
    : ['auth', config('jetstream.auth_session')];
```

### Senarai Route Autentikasi

```php
// Fortify Routes (dihasilkan secara automatik oleh Laravel Fortify)
GET  /login                  → Fortify: Paparan log masuk          [login]
POST /login                  → Fortify: Proses log masuk
POST /logout                 → Fortify: Log keluar (local)          [logout]
GET  /forgot-password        → Fortify: Borang lupa kata laluan     [password.request]
POST /forgot-password        → Fortify: Hantar pautan reset
GET  /reset-password/{token} → Fortify: Borang reset kata laluan    [password.reset]
POST /reset-password         → Fortify: Proses reset kata laluan

// Two-Factor Authentication
GET  /two-factor-challenge   → Paparan cabaran 2FA                 [two-factor.login]
POST /two-factor-challenge   → Proses kod 2FA                      [two-factor.login.store]

// CAS Logout (aktif hanya jika CAS_ENABLED=true)
POST /cas-logout             → Log keluar CAS → redirect SATUID    [cas.logout]

// Session Management
POST /session/ping           → Lanjutkan sesi & semak auth         [session.ping]
GET  /session/check          → Semak status sesi (tanpa lanjut)    [session.check]

// Debug/Status
GET  /auth-status            → Status konfigurasi CAS (JSON)       [auth.status]
```

---

## Blade Views

### Login Views (Sneat Template)
| Path | Keterangan |
|------|-----------|
| `resources/views/content/authentications/auth-login-basic.blade.php` | Halaman log masuk gaya Basic |
| `resources/views/content/authentications/auth-login-cover.blade.php` | Halaman log masuk gaya Cover |

### Lain-Lain Auth Views
| Path | Keterangan |
|------|-----------|
| `auth-forgot-password-basic.blade.php` | Lupa kata laluan (Basic) |
| `auth-forgot-password-cover.blade.php` | Lupa kata laluan (Cover) |
| `auth-reset-password-basic.blade.php` | Reset kata laluan (Basic) |
| `auth-reset-password-cover.blade.php` | Reset kata laluan (Cover) |
| `auth-register-basic.blade.php` | Daftar akaun (Basic) |
| `auth-register-cover.blade.php` | Daftar akaun (Cover) |
| `auth-register-multisteps.blade.php` | Daftar akaun berbilang langkah |
| `auth-two-steps-basic.blade.php` | 2FA (Basic) |
| `auth-two-steps-cover.blade.php` | 2FA (Cover) |
| `auth-verify-email-basic.blade.php` | Verify email (Basic) |
| `auth-verify-email-cover.blade.php` | Verify email (Cover) |

*(Semua dalam `resources/views/content/authentications/`)*

---

## Database Tables

### users
Jadual pengguna sistem.

**Lajur Berkaitan Auth**: `id`, `id_pgn_ldap`, `email`, `password`, `kodaktif`, `lastlogin_at`, `deleted_at`

---

### sessions
Jadual sesi (driver: `database`).

**Lajur**: `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`

---

### password_reset_tokens
Jadual token reset kata laluan.

**Lajur**: `email`, `token`, `created_at`

---

## Struktur Fail

```
app/
├── Http/
│   ├── Middleware/
│   │   ├── AuthCas.php                   ← CAS authentication middleware
│   │   ├── LocaleMiddleware.php           ← Language middleware
│   │   └── ImpersonateMiddleware.php      ← Impersonate middleware
│   └── Controllers/
│       └── authentications/
│           ├── LoginBasic.php
│           ├── LoginCover.php
│           ├── ForgotPasswordBasic.php
│           ├── ForgotPasswordCover.php
│           ├── RegisterBasic.php
│           ├── RegisterCover.php
│           ├── RegisterMultiSteps.php
│           ├── ResetPasswordBasic.php
│           ├── ResetPasswordCover.php
│           ├── TwoStepsBasic.php
│           ├── TwoStepsCover.php
│           ├── VerifyEmailBasic.php
│           └── VerifyEmailCover.php
├── Actions/
│   └── Fortify/
│       ├── CreateNewUser.php
│       ├── UpdateUserProfileInformation.php
│       ├── UpdateUserPassword.php
│       ├── ResetUserPassword.php
│       └── PasswordValidationRules.php
├── Models/
│   └── User.php
└── Providers/
    └── FortifyServiceProvider.php

config/
├── cas.php          ← CAS/SATUID configuration
├── fortify.php      ← Fortify configuration
├── auth.php         ← Laravel auth guards/providers
└── session.php      ← Session driver & lifetime

bootstrap/
└── app.php          ← Middleware registration

routes/
└── web.php          ← Auth & session routes

resources/views/content/authentications/
├── auth-login-basic.blade.php
├── auth-login-cover.blade.php
└── ... (lain-lain auth views)

login_md/
├── overview.md      ← Gambaran keseluruhan modul
├── files.md         ← Fail ini
└── flow.md          ← Diagram aliran data
```
