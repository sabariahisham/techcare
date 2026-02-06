# 📧 Dokumentasi SLA Notification System

**Last Updated:** February 6, 2026

## 📋 Ringkasan

Sistem SLA Notification yang telah diperbaiki dan diseragamkan untuk menghantar email reminder kepada Pegawai Tugasan berdasarkan tarikh akhir SLA (Service Level Agreement). Sistem ini tidak menggunakan Laravel Queue dan berjalan pada jam 7:30 AM dengan mengambil kira hari bekerja dan cuti umum.

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

### 1. **Reminder** - 1 Hari Sebelum Due Date
- **Trigger:** 1 hari bekerja sebelum SLA tamat tempoh (sla_duedate - 1 working day)
- **Subject:** "⏳ Peringatan SLA - Hampir Tamat Tempoh"
- **Email Template:** `sla-reminder.blade.php`
- **Notification Class:** `ReminderNotification`
- **Recipient:** Pegawai Tugasan Level 1 atau Level 2 (bergantung kepada chronology terkini)
- **Working Days:** Ya (skip weekends & public holidays)

### 2. **Overdue** - 1 Hari Selepas Due Date
- **Trigger:** 1 hari bekerja selepas SLA tamat tempoh (sla_duedate + 1 working day)
- **Subject:** "🚨 Amaran: Tiket SLA OVERDUE"
- **Email Template:** `sla-overdue.blade.php`
- **Notification Class:** `SLAOverdueNotification`
- **Recipient:** Pegawai Tugasan yang berkaitan
- **Working Days:** Ya (skip weekends & public holidays)

### 3. **Due Date (Status=1 Only)** - 3 Hari Selepas Created
- **Trigger:** 3 hari bekerja selepas tiket dicipta (created_at + 3 working days)
- **Condition:** HANYA untuk tiket dengan status = 1 (Baru/Menunggu)
- **Subject:** "📅 Peringatan: Tiket Menunggu Tindakan"
- **Email Template:** `due-date.blade.php`
- **Notification Class:** `DueDateNotification`
- **Recipient:** Pegawai Tugasan Level 1
- **Working Days:** Ya (skip weekends & public holidays)
- **Special:** Tidak bergantung kepada sla_duedate, guna created_at sahaja

## 🔧 Files Yang Telah Dibuat/Diubah

### Models
- ✅ `app/Models/Ticket.php` - Tambah `assignedUser()` dan helper methods
- ✅ `app/Models/TicketChronology.php` - Model dengan SLA fields (sla_duedate, tick_status)

### Notifications (NO QUEUE)
- ✅ `app/Notifications/Ticket/ReminderNotification.php` - 1 day before reminder
- ✅ `app/Notifications/Ticket/SLAOverdueNotification.php` - 1 day after overdue
- ✅ `app/Notifications/Ticket/DueDateNotification.php` - 3 days after created (status=1)
- ⚠️ **REMOVED:** `SLAReminderNotification.php` (duplicate dengan ReminderNotification)

### Services (MAIN LOGIC)
- ✅ `app/Services/TicketSLAReminderService.php` - **UNIFIED SERVICE**
  - `sendReminderNotifications()` - 1 day before
  - `sendOverdueNotifications()` - 1 day after
  - `sendDueDateNotifications()` - 3 days after created (status=1)
  - Semua method guna working days
- ⚠️ **DEPRECATED:** `TicketDueDateController` - Old conflicting logic

### Console Commands (AUTOMATION)
- ✅ `app/Console/Commands/SendSLANotifications.php` - **UNIFIED COMMAND**
  - Jalankan semua 3 jenis notifikasi
  - Scheduled pada 7:30 AM setiap hari
  - No queue, direct send

### Helpers
- ✅ `app/Helpers/WorkingDaysHelper.php` - Working days calculation
  - `addWorkingDays()` - Tambah hari bekerja
  - `subtractWorkingDays()` - Tolak hari bekerja
  - `isWorkingDay()` - Check sama ada hari bekerja
  - Skip weekends & public holidays

### Views (Email Templates)
- ✅ `resources/views/emails/ticket/sla-reminder.blade.php` - Reminder template
- ✅ `resources/views/emails/ticket/sla-overdue.blade.php` - Overdue template
- ✅ `resources/views/emails/ticket/due-date.blade.php` - Due date template

### Schedule (Kernel.php)
- ✅ `app/Console/Kernel.php` - Schedule command pada 7:30 AM
  ```php
  $schedule->command('sla:send-notifications')->dailyAt('07:30');
  ```

## 🌐 Testing & Manual Trigger

### Console Command (RECOMMENDED)

```bash
# Jalankan semua 3 jenis notifikasi sekaligus
php artisan sla:send-notifications

# Dengan verbose output untuk debugging
php artisan sla:send-notifications -v

# Atau guna PowerShell
php artisan sla:send-notifications
```

### Individual Testing (Service Methods)

```php
use App\Services\TicketSLAReminderService;

$service = app(TicketSLAReminderService::class);

// Test reminder (1 day before)
$service->sendReminderNotifications();

// Test overdue (1 day after)
$service->sendOverdueNotifications();

// Test due date (3 days after created, status=1 only)
$service->sendDueDateNotifications();
```

### Schedule Automation

Dijadualkan berjalan setiap hari pada **7:30 AM** melalui Laravel Scheduler:

```php
// app/Console/Kernel.php
$schedule->command('sla:send-notifications')->dailyAt('07:30');
```

Pastikan cron job berjalan:
```bash
* * * * * cd /path-to-ehelp && php artisan schedule:run >> /dev/null 2>&1
```

## 🧪 Database Query Examples

### Check Tickets for Reminder (1 day before)

```sql
SELECT t.id, t.ticket_nolog, t.ticket_title, 
       tc.sla_duedate, tc.tick_status,
       u.nama as pegawai_tugasan
FROM tickets t
INNER JOIN tickets_chronologies tc ON tc.ticket_id = t.id
LEFT JOIN users u ON u.id = tc.frm_user_id
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND tc.sla_duedate IS NOT NULL
  AND tc.tick_status NOT IN (5, 6) -- 5=Batal, 6=Selesai
  AND DATE(tc.sla_duedate) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))
ORDER BY tc.created_at DESC;
```

### Check Tickets for Overdue (1 day after)

```sql
SELECT t.id, t.ticket_nolog, t.ticket_title,
       tc.sla_duedate, tc.tick_status,
       DATEDIFF(NOW(), tc.sla_duedate) as days_overdue
FROM tickets t
INNER JOIN tickets_chronologies tc ON tc.ticket_id = t.id
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND tc.sla_duedate IS NOT NULL
  AND tc.tick_status NOT IN (5, 6) -- 5=Batal, 6=Selesai
  AND DATE(tc.sla_duedate) < DATE(NOW())
  AND DATE(tc.sla_duedate) = DATE(DATE_SUB(NOW(), INTERVAL 1 DAY))
ORDER BY tc.created_at DESC;
```

### Check Tickets for Due Date (3 days after created, status=1)

```sql
SELECT t.id, t.ticket_nolog, t.ticket_title,
       t.created_at, tc.tick_status,
       DATEDIFF(NOW(), t.created_at) as days_since_created
FROM tickets t
INNER JOIN tickets_chronologies tc ON tc.ticket_id = t.id
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND tc.tick_status = 1 -- Status Baru/Menunggu sahaja
  AND DATE(t.created_at) = DATE(DATE_SUB(NOW(), INTERVAL 3 DAY))
ORDER BY t.created_at DESC;
```

## 🔍 Troubleshooting

### Email tidak dihantar?

1. **Check mail configuration** dalam `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your_email@gmail.com
   MAIL_PASSWORD=your_app_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your_email@gmail.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

2. **Check Laravel logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Test mail configuration**:
   ```bash
   php artisan tinker
   Mail::raw('Test email', function($msg) {
       $msg->to('test@example.com')->subject('Test');
   });
   ```

### Notification tidak trigger?

1. **Verify cron is running**:
   ```bash
   php artisan schedule:list
   ```

2. **Run command manually**:
   ```bash
   php artisan sla:send-notifications -v
   ```

3. **Check database** untuk tickets yang qualify:
   ```sql
   SELECT COUNT(*) FROM tickets t
   JOIN tickets_chronologies tc ON tc.ticket_id = t.id
   WHERE DATE(tc.sla_duedate) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY));
   ```

### Working days tidak betul?

1. **Check public holidays** dalam database:
   ```sql
   SELECT * FROM public_holidays WHERE ph_date >= CURDATE()
   ORDER BY ph_date;
   ```

2. **Test working days calculation**:
   ```php
   php artisan tinker
   use App\Helpers\WorkingDaysHelper;
   $tomorrow = WorkingDaysHelper::addWorkingDays(now(), 1);
   echo $tomorrow;
   ```

## 📊 Response Format

### Success Response

```json
{
  "success": true,
  "message": "All SLA notifications sent successfully",
  "results": {
    "reminder": {
      "sent": 5,
      "failed": 0
    },
    "overdue": {
      "sent": 2,
      "failed": 0
    },
    "due_date": {
      "sent": 3,
      "failed": 0
    }
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Failed to send notifications",
  "error": "Error message here"
}
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
