# 📧 Panduan Laravel Markdown Email - eHelp System

**Tarikh Dicipta:** 19 Januari 2026  
**Dikemaskini:** 20 Januari 2026  
**Sistem:** Digital Service Desk (BTM)

---

## 📋 Ringkasan

Sistem email eHelp telah dinaiktaraf daripada plain text kepada **Laravel Markdown Mail** dengan custom CSS theme untuk paparan yang lebih profesional dan menarik.

---

## 📁 Struktur Fail

### 1. Email Templates
Lokasi: `resources/views/emails/ticket/`

#### a) **pengadu-status.blade.php**
- **Tujuan**: Email notifikasi kepada pengadu (ticket owner) apabila status tiket berubah
- **Penerima**: Pengadu yang membuka tiket
- **Trigger**: Status tiket dikemaskini
- **Komponen Utama**:
  - Header "🔔 Status Aduan" (warna ungu #696cff)
  - Panel maklumat tiket (No. Log, Tajuk, Status, Pegawai Tugasan)
  - Button "Lihat Tiket"

#### b) **pegawai-tugasan-status.blade.php**
- **Tujuan**: Email notifikasi kepada pegawai yang ditugaskan tiket
- **Penerima**: Pegawai Level 1/Level 2 yang ditugaskan
- **Trigger**: Tiket baru ditugaskan kepada pegawai
- **Komponen Utama**:
  - Header "📌 Tiket Baru Ditugaskan" (warna oren #ffab00)
  - Panel maklumat tiket (No. Log, Tajuk, Status, Pengadu, Catatan)
  - Button "Lihat Tiket"

#### c) **due-date-alert.blade.php**
- **Tujuan**: Email peringatan tarikh akhir tiket (due date reminder)
- **Penerima**: Pegawai yang bertanggungjawab
- **Trigger**: Tiket menghampiri tarikh akhir
- **Komponen Utama**:
  - Header "⚠️ Peringatan Segera: Tarikh Tamat Tindakan" (warna merah #ff3e1d)
  - Panel maklumat tiket dengan tarikh akhir
  - Button "Lihat Tiket" (warna merah/error)

---

## 🎨 Custom Theme

### Lokasi Fail
- **CSS**: `resources/views/vendor/mail/html/themes/ticket_mail.css`
- **Blade Wrapper**: `resources/views/vendor/mail/html/themes/ticket_mail.blade.php`
- **Message Layout**: `resources/views/vendor/mail/html/message.blade.php`

### Konfigurasi
File: `config/mail.php`
```php
'markdown' => [
    'theme' => 'ticket_mail',  // Nama theme custom
    'paths' => [
        resource_path('views/vendor/mail'),
    ],
],
```

### Warna Theme
- **Header Gradient**: Blue (#1e40af → #1e3a8a)
- **Footer Gradient**: Light Grey (#f3f4f6 → #e5e7eb)
- **Panel Background**: Light Blue Gradient (#eff6ff → #dbeafe)
- **Panel Border**: Blue (#2563eb)
- **Button Primary**: Blue (#2563eb)
- **Button Success**: Green (#16a34a)
- **Button Error**: Red (#dc2626)

### Custom Header Badges
Setiap email ada header badge dengan warna berbeza:
- **Status Aduan**: #696cff (Ungu)
- **Tiket Baru Ditugaskan**: #ffab00 (Oren)
- **Peringatan Tarikh**: #ff3e1d (Merah)

---

## 🔧 Notification Classes

### Lokasi: `app/Notifications/Ticket/`

#### 1. **PengaduStatusChangedNotification.php**
```php
return (new MailMessage)
    ->subject('Status Tiket ' . $this->ticket->ticket_nolog . ' Dikemaskini: ' . $statusName)
    ->markdown('emails.ticket.pengadu-status', [
        'ticket' => $this->ticket,
        'assignedUser' => $assignedUser,
    ]);
```

#### 2. **PegawaiTugasanStatusChangedNotification.php**
```php
return (new MailMessage)
    ->subject('[Aduan Baru] ' . $this->ticket->ticket_nolog . ' - ' . $this->ticket->ticket_title)
    ->markdown('emails.ticket.pegawai-tugasan-status', [
        'ticket' => $this->ticket,
        'assignedUser' => $assignedUser,
    ]);
```

#### 3. **DueDateNotification.php**
```php
return (new MailMessage)
    ->subject('⚠️ Peringatan: Tarikh Akhir Tiket ' . $this->ticket->ticket_nolog)
    ->markdown('emails.ticket.due-date-alert', [
        'ticket' => $this->ticket,
    ]);
```

---

## 📝 Sintaks Laravel Markdown

### Component Utama

#### 1. Message Component
```blade
@component('mail::message')
    <!-- Content here -->
@endcomponent
```

#### 2. Panel Component (Box Maklumat)
```blade
@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>
@endcomponent
```

#### 3. Button Component
```blade
@component('mail::button', ['url' => route('tickets.show', $ticket->id)])
Lihat Tiket
@endcomponent
```

**Warna Button:**
- Default (blue): `@component('mail::button')`
- Success (green): `@component('mail::button', ['color' => 'success'])`
- Error (red): `@component('mail::button', ['color' => 'error'])`

#### 4. Custom HTML Header Badge
```blade
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 20px 0;">
    <tr>
        <td style="
            background: #696cff;
            padding: 14px 18px;
            border-radius: 8px;
            text-align: center;
        ">
            <p style="
                color: #ffffff;
                font-size: 16px;
                font-weight: 600;
                margin: 0;
                text-align: center;
            ">
                🔔 Status Aduan
            </p>
        </td>
    </tr>
</table>
```

---

## ⚠️ Perkara Penting

### 1. Indentation Rules
```blade
{{-- blade-formatter-disable --}}
@component('mail::message')
<!-- Content MUST start at column 0 (no indentation) -->
@endcomponent
{{-- blade-formatter-enable --}}
```

**KRITIKAL:** 
- Tiada indentation dalam `@component` blocks
- Jika ada indentation, markdown akan render sebagai code blocks (raw HTML)
- Gunakan `blade-formatter-disable` untuk prevent VSCode auto-formatting

### 2. .editorconfig Settings
File: `.editorconfig`
```ini
[*.blade.php]
indent_size = 0
trim_trailing_whitespace = false
```

### 3. Variable Data

**Ticket Variables:**
- `$ticket->ticket_nolog` - No. Log tiket
- `$ticket->ticket_title` - Tajuk tiket
- `$ticket->ticket_desc` - Deskripsi tiket
- `$ticket->tick_duedate` - Tarikh akhir
- `$ticket->status->st_name` - Nama status

**User Variables:**
- `$ticket->user->nama` - Nama pengadu
- `$assignedUser->nama` - Nama pegawai ditugaskan

---

## 🔄 Testing

### Test Script
File: `test_notification_detail.php`

```bash
php test_notification_detail.php {ticket_id}
```

**Contoh:**
```bash
php test_notification_detail.php 14
```

### Mailpit (Local Testing)
- URL: http://localhost:8025
- View semua email yang dihantar dalam development
- Boleh check responsive design

### Clear View Cache
```bash
php artisan view:clear
```

---

## 📱 Responsive Design

Email theme ini responsive dengan breakpoints:
- **Large Desktop** (≥1200px): 85% width, max 900px
- **Desktop** (992px-1199px): 90% width, max 800px
- **Tablet** (768px-991px): 95% width
- **Mobile** (≤767px): 100% width
- **Small Mobile** (≤480px): Reduced font sizes

---

## 🚀 Deployment Steps

### 1. Publish Laravel Mail Views
```bash
php artisan vendor:publish --tag=laravel-mail
```

### 2. Create Custom Theme
- Cipta `ticket_mail.css` dalam `resources/views/vendor/mail/html/themes/`
- Cipta `ticket_mail.blade.php` wrapper

### 3. Update Config
- Set theme dalam `config/mail.php`

### 4. Create Email Templates
- Cipta blade files dalam `resources/views/emails/ticket/`

### 5. Update Notification Classes
- Convert dari `->line()` ke `->markdown()`

### 6. Test
```bash
php artisan view:clear
php test_notification_detail.php {ticket_id}
```

---

## 🐛 Troubleshooting

### Issue: Raw HTML Displayed
**Sebab:** Indentation dalam `@component` blocks  
**Penyelesaian:** Remove semua indentation, gunakan `blade-formatter-disable`

### Issue: Theme Not Applied
**Sebab:** Cache tidak di-clear atau theme salah configured  
**Penyelesaian:**
```bash
php artisan view:clear
php artisan config:clear
```

### Issue: Button Cutoff
**Sebab:** Missing `border-bottom` property  
**Penyelesaian:** Ensure all button borders set (top, bottom, left, right)

### Issue: Footer Not Full Width
**Sebab:** Footer CSS nested dalam `.inner-body`  
**Penyelesaian:** Move footer CSS out as independent selector

---

## 📚 References

### Laravel Documentation
- [Laravel Mail - Markdown](https://laravel.com/docs/10.x/mail#markdown-mailables)
- [Laravel Notifications](https://laravel.com/docs/10.x/notifications)

### Blade Components
- `mail::message` - Main email container
- `mail::panel` - Info box with gradient background
- `mail::button` - Action button (support colors: default, success, error)
- `mail::subcopy` - Small text footer

### CSS Classes
- `.header` - Email header dengan gradient blue
- `.footer` - Email footer dengan gradient light grey
- `.panel` - Info box dengan border kiri blue
- `.button` - Action button dengan borders
- `.content-cell` - Main content area

---

## ✅ Checklist Maintenance

- [ ] Regular test email sending
- [ ] Check responsive display on different devices
- [ ] Verify email content accuracy
- [ ] Monitor email delivery success
- [ ] Update CSS theme as needed
- [ ] Clear cache after updates
- [ ] Test in Mailpit before production

---

## 📞 Support

Untuk sebarang masalah atau pertanyaan:
- **Sistem**: Digital Service Desk (BTM)
- **Developer**: eHelp Development Team
- **Dokumentasi**: `/developer-guide/` folder

---

**Nota Akhir:** Sistem email markdown ini direka untuk professional display sambil maintain simplicity dan responsive design. Sentiasa test dalam Mailpit sebelum deploy ke production.
