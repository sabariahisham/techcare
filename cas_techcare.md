# CAS Authentication — TechCare (eHelp) System

## 1. Senarai Fail Terlibat

| Fail | Peranan |
|------|---------|
| `config/cas.php` | Konfigurasi CAS server, port, URI, URL klien, logout URL |
| `app/Http/Middleware/AuthCas.php` | Middleware utama — proses ticket, validate, login user |
| `bootstrap/app.php` | Daftarkan `cas.auth` sebagai alias middleware |
| `routes/web.php` | Pilih middleware `cas.auth` / `auth` berdasarkan `.env`, daftarkan route `/cas-logout` |
| `.env` / `.env.cas.example` | Pemboleh ubah persekitaran CAS (`CAS_ENABLED`, `CAS_HOSTNAME`, dll.) |
| `app/Models/User.php` | Model User — carian berdasarkan `id_pgn_ldap` |
| `database/migrations/0001_01_01_000000_create_users_table.php` | Kolum `id_pgn_ldap` dalam jadual users |
| `app/Console/Commands/SyncUsersFromSpp.php` | Sync data user dari SPP DB termasuk `id_pgn_ldap` |

---

## 2. Pemboleh Ubah Persekitaran (`.env`)

```env
CAS_ENABLED=true                                        # true = SATUID, false = Laravel standard
CAS_HOSTNAME=satuid.treasury.gov.my                     # Hos CAS server
CAS_PORT=443                                            # Port HTTPS
CAS_URI=/gk                                             # Base URI CAS
CAS_CLIENT_SERVICE=http://techcare-dev.treasury.gov.my  # URL aplikasi (perlu didaftarkan di SATUID)
CAS_REDIRECT_PATH=/dashboard                            # Redirect selepas login berjaya
CAS_LOGOUT_URL=https://satuid.treasury.gov.my/gk/logout # URL logout SATUID
CAS_LOGOUT_REDIRECT=http://techcare-dev.treasury.gov.my # Redirect selepas logout
CAS_VALIDATE_PATH=/gk/serviceValidate                   # Endpoint validasi ticket
CAS_VALIDATION=none                                     # SSL: none (dev) / ca (prod)
```

---

## 3. Aliran CAS (Flow)

### 3.1 Login

```
Pengguna akses URL dilindungi
        │
        ▼
[AuthCas Middleware]
        │
        ├─── Auth::check() = true? ──► Lulus ke controller (sudah log masuk)
        │
        ├─── Ada ?ticket= dalam URL? ──► [Proses Validasi Ticket]
        │                                        │
        │                               Hantar request ke SATUID:
        │                               GET https://satuid.treasury.gov.my/gk/serviceValidate
        │                                   ?service=<URL semasa>
        │                                   &ticket=<CAS ticket>
        │                                        │
        │                               Terima XML response dari SATUID
        │                                        │
        │                         ┌──── Berjaya (cas:authenticationSuccess) ────┐
        │                         │                                              │
        │                    Dapatkan username (LDAP ID)              Gagal (cas:authenticationFailure)
        │                    dari XML response                                   │
        │                         │                                        Return error 401
        │                    Cari user dalam DB:
        │                    User::where('id_pgn_ldap', $ldapId)->first()
        │                         │
        │                    ┌─── User dijumpai? ───┐
        │                    │                      │
        │               Auth::login($user)     User tidak dijumpai
        │               Redirect ke URL bersih  Return error 401
        │               (tanpa ?ticket=)
        │
        └─── Tiada ticket ──► Redirect ke SATUID login:
                              https://satuid.treasury.gov.my/gk/login
                                  ?service=<URL semasa pengguna>
```

### 3.2 Logout

```
Pengguna klik Logout
        │
        ▼
POST /cas-logout
        │
        ▼
Auth::logout()
Session invalidate & token regenerate
        │
        ▼
Redirect ke SATUID logout:
https://satuid.treasury.gov.my/gk/logout
```

---

## 4. Butiran Teknikal

### Middleware `AuthCas`

| Langkah | Tindakan |
|---------|----------|
| 1 | Semak `Auth::check()` — jika sudah login, teruskan |
| 2 | Semak query param `?ticket=` dalam URL |
| 3 | Jika ada ticket → hantar ke `serviceValidate` SATUID via `file_get_contents` |
| 4 | Parse XML response (namespace `http://www.yale.edu/tp/cas`) |
| 5 | Ekstrak LDAP ID dari `<cas:user>` |
| 6 | Cari user dalam DB menggunakan `id_pgn_ldap` |
| 7 | `Auth::login($user, true)` dan redirect ke URL bersih |
| 8 | Jika tiada ticket → redirect ke `SATUID /gk/login?service=<URL>` |

### Pemilihan Middleware Automatik (routes/web.php)

```php
$authMiddleware = config('cas.cas_enabled')
    ? ['cas.auth']                                   // CAS_ENABLED=true → SATUID
    : ['auth', config('jetstream.auth_session')];    // CAS_ENABLED=false → Laravel standard
```

### Pemetaan User (LDAP → DB)

- SATUID mengembalikan **LDAP ID** (username) selepas pengesahan berjaya
- Sistem mencari user dalam jadual `users` menggunakan kolum `id_pgn_ldap`
- Data user disinkronkan dari sistem SPP melalui command `php artisan sync:users-spp`

---

## 5. URL CAS Penting

| Tujuan | URL |
|--------|-----|
| Login redirect | `https://satuid.treasury.gov.my/gk/login?service=<APP_URL>` |
| Validate ticket | `https://satuid.treasury.gov.my/gk/serviceValidate?service=<APP_URL>&ticket=<TICKET>` |
| Logout | `https://satuid.treasury.gov.my/gk/logout` |

---

## 6. Toggle CAS (Dev vs Prod)

| `CAS_ENABLED` | Mod Pengesahan |
|---------------|----------------|
| `true` | SATUID (CAS) — middleware `cas.auth` |
| `false` | Laravel/Jetstream standard — middleware `auth` |

> **Nota:** Untuk pembangunan tempatan, set `CAS_ENABLED=false` supaya log masuk menggunakan akaun tempatan tanpa perlu akses ke SATUID.

---

## 7. Debugging

Log tersimpan di `storage/logs/laravel.log` dengan prefix `CAS:`:

- `CAS: User already authenticated`
- `CAS: Ticket received`
- `CAS: Validating ticket`
- `CAS: Authentication successful`
- `CAS: User not found in database`
- `CAS: Failed to contact CAS server`
- `CAS: Redirecting to login`

Semak status CAS melalui route: `GET /auth-status`
