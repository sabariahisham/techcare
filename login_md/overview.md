# Modul Login & Autentikasi - Gambaran Keseluruhan

## Tujuan
Modul Login & Autentikasi menguruskan proses pengesahan identiti pengguna sebelum mengakses sistem eHelp. Sistem menyokong **dua mod autentikasi** yang boleh ditukar melalui pemboleh ubah persekitaran `.env`.

---

## Dua Mod Autentikasi

| Mod | Keterangan | Env Setting |
|-----|-----------|-------------|
| **CAS (SATUID)** | Autentikasi terpusat melalui portal SATUID Perbendaharaan | `CAS_ENABLED=true` |
| **Local (Fortify)** | Autentikasi tempatan Laravel menggunakan email + kata laluan | `CAS_ENABLED=false` |

---

## Mod 1: CAS (SATUID) — Autentikasi Terpusat

### Apakah SATUID?
SATUID ialah sistem Single Sign-On (SSO) milik Kementerian Kewangan Malaysia. Pengguna log masuk sekali melalui portal SATUID dan boleh mengakses semua aplikasi yang berdaftar tanpa perlu log masuk semula.

### Aliran CAS
1. Pengguna akses mana-mana halaman eHelp yang dilindungi
2. Middleware `cas.auth` → `AuthCas` semak sama ada pengguna sudah log masuk (`Auth::check()`)
3. Jika belum, redirect ke SATUID login: `https://satuid.treasury.gov.my/gk/login?service={url_kembali}`
4. Pengguna log masuk di SATUID
5. SATUID redirect balik ke eHelp dengan **CAS ticket** dalam query parameter: `?ticket=ST-xxxxx`
6. Middleware `AuthCas` terima ticket, hantar ke `serviceValidate` endpoint SATUID untuk pengesahan
7. SATUID kembalikan respons XML — sama ada `authenticationSuccess` atau `authenticationFailure`
8. Jika berjaya, `id_pgn_ldap` (LDAP ID) diambil dari respons XML
9. User dicari dalam database menggunakan `id_pgn_ldap`
10. `Auth::login($user, true)` dipanggil untuk wujudkan sesi
11. Redirect ke URL bersih (tanpa `?ticket=`)

### Log Keluar CAS
- Route `POST /cas-logout` → `Auth::logout()` + invalidate sesi
- Redirect ke `cas_logout_url` (URL log keluar SATUID)

---

## Mod 2: Local Authentication — Laravel Fortify

### Komponen Utama
- **Laravel Fortify** — mengendalikan login, register, reset kata laluan, 2FA
- **Laravel Jetstream** — menyediakan sesi autentikasi (`auth_session`)
- **FortifyServiceProvider** — mendaftarkan pipeline autentikasi

### Pipeline Autentikasi Fortify
```
1. EnsureLoginIsNotThrottled    ← Semak rate limit (5 percubaan/minit)
2. AttemptToAuthenticate        ← Cuba sahkan email + kata laluan
3. RedirectIfTwoFactorAuthenticatable ← Semak 2FA jika diaktifkan
4. PrepareAuthenticatedSession  ← Wujudkan sesi log masuk
```

### Two-Factor Authentication (2FA)
- Menggunakan `TwoFactorAuthenticatable` trait dalam User model
- Route: `GET /two-factor-challenge` → paparan kod OTP
- Route: `POST /two-factor-challenge` → hantar kod OTP
- Rate limit: 5 percubaan/minit per sesi

---

## Pengurusan Sesi

### Konfigurasi Sesi
| Setting | Nilai | Keterangan |
|---------|-------|-----------|
| Driver | `database` | Sesi disimpan dalam jadual `sessions` |
| Lifetime | `120 minit` | Tamat jika tiada aktiviti |
| Table | `sessions` | Nama jadual dalam database |
| Cookie | `{app_name}_session` | Nama cookie sesi |

### Session Ping & Check
Sistem menyediakan dua endpoint untuk pengurusan sesi frontend:

| Route | Method | Fungsi |
|-------|--------|--------|
| `/session/ping` | POST | Lanjutkan sesi dan semak status auth |
| `/session/check` | GET | Semak status auth tanpa lanjutkan sesi |

---

## Middleware Autentikasi

### Pemilihan Middleware Dinamik
Di `routes/web.php`, middleware dipilih secara automatik berdasarkan `CAS_ENABLED`:
```php
$authMiddleware = config('cas.cas_enabled')
    ? ['cas.auth']
    : ['auth', config('jetstream.auth_session')];
```

### Senarai Middleware

| Middleware | Kelas | Tujuan |
|-----------|-------|--------|
| `cas.auth` | `AuthCas::class` | CAS/SATUID autentikasi |
| `auth` | Laravel built-in | Semak pengguna log masuk (local) |
| `LocaleMiddleware` | `LocaleMiddleware::class` | Set bahasa antara muka |
| `ImpersonateMiddleware` | `ImpersonateMiddleware::class` | Sokongan impersonate (admin masuk sebagai pengguna lain) |

---

## Model Pengguna (User)

### Traits Penting
| Trait | Fungsi |
|-------|--------|
| `HasApiTokens` | Sanctum API token |
| `TwoFactorAuthenticatable` | Fortify 2FA support |
| `HasRoles` | Spatie permission roles |
| `HasPermissions` | Spatie permission |
| `SoftDeletes` | Soft delete |
| `Impersonate` | Lab404 impersonate |

### Medan Autentikasi Penting
| Medan | Keterangan |
|-------|-----------|
| `id_pgn_ldap` | LDAP ID dari SATUID (untuk CAS lookup) |
| `email` | Email pengguna (untuk local login) |
| `password` | Kata laluan di-hash (hashed) |
| `kodaktif` | Status aktif pengguna (boolean) |
| `lastlogin_at` | Tarikh/masa log masuk terakhir |

---

## Konfigurasi CAS (`config/cas.php`)

| Key | Default | Keterangan |
|-----|---------|-----------|
| `cas_enabled` | `false` | Toggle CAS on/off |
| `cas_hostname` | `satuid.treasury.gov.my` | Hos server CAS |
| `cas_port` | `443` | Port HTTPS |
| `cas_uri` | `/gk` | Base URI CAS |
| `cas_client_service` | URL aplikasi | Service URL yang didaftarkan di SATUID |
| `cas_redirect_path` | `/dashboard` | Redirect selepas berjaya log masuk |
| `cas_logout_url` | URL SATUID logout | Destinasi log keluar CAS |
| `cas_validate_path` | `/gk/serviceValidate` | Endpoint untuk validate ticket |
| `cas_validation` | `none` | SSL validation (`ca`, `self`, `none`) |

---

## Route Utama Autentikasi

| URL | Method | Keterangan |
|-----|--------|-----------|
| `/login` | GET | Fortify: Paparan borang log masuk |
| `/login` | POST | Fortify: Proses log masuk |
| `/logout` | POST | Fortify: Log keluar (local) |
| `/cas-logout` | POST | CAS: Log keluar → redirect SATUID |
| `/two-factor-challenge` | GET | Paparan cabaran 2FA |
| `/two-factor-challenge` | POST | Proses kod 2FA |
| `/session/ping` | POST | Lanjutkan sesi |
| `/session/check` | GET | Semak status sesi |
| `/auth-status` | GET | Debug: Status konfigurasi CAS |
| `/forgot-password` | GET | Fortify: Lupa kata laluan |
| `/reset-password/{token}` | GET | Fortify: Reset kata laluan |

---

## Impersonate (Masuk Sebagai Pengguna Lain)

### Apakah Impersonate?
Fungsi impersonate membenarkan Administrator log masuk **sebagai pengguna lain** tanpa mengetahui kata laluan mereka. Berguna untuk tujuan sokongan teknikal dan debug.

### Peraturan
| Peranan | canImpersonate | canBeImpersonated |
|---------|----------------|-------------------|
| **Administrator** | ✅ Ya — boleh impersonate orang lain | ❌ Tidak — tidak boleh diimpersonate |
| **Pengguna Biasa** | ❌ Tidak | ✅ Ya — boleh diimpersonate |

### Routes
| URL | Fungsi |
|-----|--------|
| `GET /impersonate/user/{user}` | Mulakan impersonate pengguna `{user}` |
| `GET /impersonate/leave` | Tamatkan impersonate, kembali ke akaun asal |

### Audit Trail
Setiap tindakan impersonate dilog secara automatik:
- **Mulakan impersonate** → event `impersonate_start` disimpan dalam jadual `audits`
- **Tindakan semasa impersonate** → semua non-GET request dilog via `ImpersonateMiddleware`
- **Tamatkan impersonate** → event `impersonate_end` disimpan dalam jadual `audits`

---

## Keselamatan

- **Rate Limiting**: 5 percubaan log masuk per minit per pengguna/IP
- **CSRF Protection**: Semua form dilindungi CSRF token
- **Session Invalidation**: Sesi di-invalidate sepenuhnya semasa log keluar
- **Soft Delete**: Pengguna yang dihapus tidak boleh log masuk
- **Ticket Validation**: CAS ticket disahkan terus dengan SATUID sebelum log masuk
- **SSL**: Komunikasi dengan SATUID melalui HTTPS

---

## Stack Teknologi
- **Backend**: Laravel 12, PHP 8.4
- **Auth Framework**: Laravel Fortify v1
- **Session Management**: Jetstream auth session
- **CAS Integration**: Custom `AuthCas` middleware (tanpa library phpCAS)
- **Permission**: Spatie Laravel Permission
- **2FA**: Fortify built-in TOTP
- **Impersonate**: Lab404 Laravel Impersonate
