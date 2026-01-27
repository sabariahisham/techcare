# 📧 Dokumentasi SLA Notification System

## 📋 Ringkasan

Sistem SLA Notification yang telah ditambah baik untuk menghantar email reminder kepada Pegawai Tugasan berdasarkan tarikh akhir SLA (Service Level Agreement).

## 🔄 Flow Sistem

```
Aduan Baru
     ↓
Assign Level 1 (Ketua/Supervisor)
     ↓
Reminder Notifications:
  - SLA-1 (Due Tomorrow)
  - Due Today
  - Due Date+1 (Yesterday)
     ↓
Level 1 Ambil Tindakan?
   ↓ YA                    ↓ TIDAK
Selesai              Escalate ke Level 2
                     (Pelaksana/Technician)
                          ↓
                  Status: Dalam Tindakan
                          ↓
                  Reminder Notifications:
                    - SLA-1
                    - Due Today
                    - Due Date+1
                          ↓
                       Selesai
```

## 📧 Jenis Email Notifications

### 1. **SLA-1 (Due Tomorrow)** - `before_due`
- Dihantar 1 hari sebelum SLA tamat tempoh
- Subject: "⚠️ Peringatan SLA - Hampir Tamat Tempoh"
- Recipient: Pegawai Tugasan yang ditugaskan (Level 1 atau Level 2)

### 2. **Due Today** - `due_today`
- Dihantar pada hari SLA akan tamat tempoh
- Subject: "🔔 Peringatan SLA - Tamat Tempoh HARI INI"
- Recipient: Pegawai Tugasan yang ditugaskan

### 3. **Due Date+1 (Yesterday)** - `after_due`
- Dihantar sehari selepas SLA tamat tempoh
- Subject: "⚠️ Peringatan SLA - Tempoh Tamat Semalam"
- Recipient: Pegawai Tugasan yang ditugaskan

### 4. **Overdue** - `overdue`
- Dihantar untuk tiket yang sudah lebih 1 hari overdue
- Subject: "⛔ Tindakan Segera: SLA (OVERDUE)"
- Recipient: Pegawai Tugasan yang ditugaskan

## 🔧 Files Yang Telah Dibuat/Diubah

### Models
- ✅ `app/Models/Ticket.php` - Tambah `assignedUser()` dan `getLatestSlaDueDateAttribute()`
- ✅ `app/Models/TicketChronology.php` - Existing model dengan SLA fields

### Notifications
- ✅ `app/Notifications/Ticket/SLAReminderNotification.php` - **NEW** - Reminder notification class
- ✅ `app/Notifications/Ticket/SLAOverdueNotification.php` - Existing overdue notification

### Services
- ✅ `app/Services/TicketSLAReminderService.php` - **NEW** - Handle semua SLA reminder logic
- ⚠️ `app/Services/TicketSLAOverdueService.php` - Existing (masih boleh guna untuk overdue sahaja)

### Controllers
- ✅ `app/Http/Controllers/Notifications/TicketSLAOverdueController.php` - Updated dengan methods baru
- ✅ `app/Http/Controllers/Ticket/TicketSLAController.php` - Updated dengan reporting methods

### Views (Email Templates)
- ✅ `resources/views/emails/ticket/sla-reminder.blade.php` - **NEW** - Template untuk reminders
- ✅ `resources/views/emails/ticket/sla-overdue.blade.php` - Existing template untuk overdue

### Routes
- ✅ `routes/api.php` - Tambah routes untuk SLA notifications

## 🌐 API Endpoints

### Trigger Notifications (Manual/Testing)

```bash
# 1. Send SLA-1 reminders (due tomorrow)
POST /api/sla/notifications/before-due

# 2. Send due today reminders
POST /api/sla/notifications/due-today

# 3. Send after due reminders (due date+1)
POST /api/sla/notifications/after-due

# 4. Send overdue notifications
POST /api/sla/notifications/overdue

# 5. Send ALL notifications at once
POST /api/sla/notifications/all
```

### Reporting Endpoints

```bash
# Get overdue tickets
GET /api/sla/tickets/overdue

# Get tickets due tomorrow (SLA-1)
GET /api/sla/tickets/due-tomorrow

# Get tickets due today
GET /api/sla/tickets/due-today

# Get tickets due yesterday (due date+1)
GET /api/sla/tickets/due-yesterday

# Get SLA statistics
GET /api/sla/statistics

# Get upcoming SLA tickets (next 7 days)
GET /api/sla/upcoming?days=7
```

## 🧪 Testing Manual

### 1. Test Individual Notification

```bash
# Test using PowerShell
$token = "YOUR_BEARER_TOKEN"
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

# Test SLA-1 (due tomorrow)
Invoke-RestMethod -Uri "http://localhost/api/sla/notifications/before-due" -Method POST -Headers $headers

# Test due today
Invoke-RestMethod -Uri "http://localhost/api/sla/notifications/due-today" -Method POST -Headers $headers

# Test after due
Invoke-RestMethod -Uri "http://localhost/api/sla/notifications/after-due" -Method POST -Headers $headers

# Test overdue
Invoke-RestMethod -Uri "http://localhost/api/sla/notifications/overdue" -Method POST -Headers $headers
```

### 2. Test All Notifications

```bash
# Send all notifications at once
Invoke-RestMethod -Uri "http://localhost/api/sla/notifications/all" -Method POST -Headers $headers
```

### 3. Check Reports

```bash
# Get overdue tickets
Invoke-RestMethod -Uri "http://localhost/api/sla/tickets/overdue" -Method GET -Headers $headers

# Get due tomorrow
Invoke-RestMethod -Uri "http://localhost/api/sla/tickets/due-tomorrow" -Method GET -Headers $headers

# Get upcoming tickets
Invoke-RestMethod -Uri "http://localhost/api/sla/upcoming?days=7" -Method GET -Headers $headers
```

## ⏰ Automated Scheduling (Cronjob)

Untuk automate notification system, tambah dalam `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Send SLA-1 reminders daily at 9:00 AM
    $schedule->call(function () {
        app(TicketSLAReminderService::class)->sendBeforeDueReminders();
    })->dailyAt('09:00')->name('sla-before-due-reminders');

    // Send due today reminders daily at 8:00 AM
    $schedule->call(function () {
        app(TicketSLAReminderService::class)->sendDueTodayReminders();
    })->dailyAt('08:00')->name('sla-due-today-reminders');

    // Send after due reminders daily at 10:00 AM
    $schedule->call(function () {
        app(TicketSLAReminderService::class)->sendAfterDueReminders();
    })->dailyAt('10:00')->name('sla-after-due-reminders');

    // Send overdue notifications daily at 11:00 AM
    $schedule->call(function () {
        app(TicketSLAReminderService::class)->sendOverdueNotifications();
    })->dailyAt('11:00')->name('sla-overdue-notifications');
}
```

Kemudian setup Laravel Scheduler dalam Windows Task Scheduler atau cron:

```bash
# Add to Windows Task Scheduler (run every minute)
php c:\path\to\artisan schedule:run
```

## 📊 Response Format

### Success Response

```json
{
  "success": true,
  "message": "SLA-1 reminders (due tomorrow) processed",
  "stats": {
    "type": "SLA-1 (Due Tomorrow)",
    "total_found": 5,
    "sent": 4,
    "failed": 0,
    "skipped": 1,
    "errors": ["No email for recipient: John Doe"]
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Failed to send SLA-1 reminders",
  "error": "Error message here"
}
```

## 🔍 Log Monitoring

Semua notification activities dilog dalam Laravel log files:

```bash
# View logs
tail -f storage/logs/laravel.log

# Search for SLA notifications
grep "SLA Reminder" storage/logs/laravel.log
grep "SLA Overdue" storage/logs/laravel.log
```

## 📝 Ticket Status Reference

- Status 1-4: Active tickets (akan receive notifications)
- Status 5: Selesai (resolved) - **TIDAK** receive notifications
- Status 6: Tutup (closed) - **TIDAK** receive notifications

## 🔐 Security

- Semua endpoints require authentication (`auth:sanctum`)
- Hanya authenticated users boleh trigger notifications
- Email dihantar kepada Pegawai Tugasan yang ditugaskan sahaja

## 🎯 Best Practices

1. **Test dalam development environment dahulu** sebelum production
2. **Setup email configuration** dengan betul dalam `.env`
3. **Monitor logs** untuk debugging issues
4. **Use queue** untuk send emails asynchronously (implement `ShouldQueue`)
5. **Test dengan real data** untuk validate logic

## 🐛 Troubleshooting

### Email tidak dihantar?
1. Check `.env` email configuration
2. Check `storage/logs/laravel.log` untuk errors
3. Verify Pegawai Tugasan ada email address
4. Check mail server credentials

### Notification tidak trigger?
1. Verify tiket ada `sla_duedate` dalam chronology
2. Check ticket status (mesti bukan 5 atau 6)
3. Check date ranges dalam query
4. Verify authentication token valid

### Response "No recipients found"?
1. Check chronology table ada `escalate_to` user
2. Verify user exists dan active
3. Check user ada email address

## 📚 Additional Resources

- Laravel Notifications: https://laravel.com/docs/notifications
- Laravel Task Scheduling: https://laravel.com/docs/scheduling
- Carbon Dates: https://carbon.nesbot.com/docs/
