# Sistem Peringatan Due Date Tiket

## Ringkasan
Sistem ini menghantar email peringatan kepada Pegawai Tugasan Level 1 sehari sebelum tarikh tamat tindakan tiket.

## Flow Sistem

```
Aduan Baru Dibuat
        ↓
Kira tick_duedate (+3 hari bekerja)
        ↓
Set reminder_date (-1 hari bekerja sebelum due date)
        ↓
Cronjob harian semak setiap hari pukul 9:00 AM:
    Jika hari ini == reminder_date
    DAN status == Aduan Baru (tick_status = 1)
        ↓
    Hantar email reminder kepada Pegawai Tugasan L1
```

## Komponen Sistem

### 1. Controller: TicketDueDateController
**Lokasi**: `app/Http/Controllers/Ticket/TicketDueDateController.php`

**Methods**:
- `calculateTicketDueDate($createdAt)` - Kira due date (+3 hari bekerja)
- `calculateReminderDate($dueDate)` - Kira reminder date (-1 hari bekerja)
- `sendDueDateReminders()` - Hantar email reminder kepada pegawai tugasan
- `testDueDateReminders()` - Manual trigger untuk testing

### 2. Command: SendTicketDueDateReminders
**Lokasi**: `app/Console/Commands/SendTicketDueDateReminders.php`

**Signature**: `tickets:send-duedate-reminders`

**Fungsi**: Wrapper untuk panggil controller method `sendDueDateReminders()`

### 3. Notification: DueDateNotification
**Lokasi**: `app/Notifications/Ticket/DueDateNotification.php`

**Fungsi**: Format dan hantar email notification

**Email Template**: `resources/views/emails/ticket/due-date-alert.blade.php`

### 4. Scheduler
**Lokasi**: `routes/console.php`

**Schedule**: Setiap hari pukul 9:00 AM (Malaysia Time)

```php
Schedule::command('tickets:send-duedate-reminders')
    ->dailyAt('09:00')
    ->timezone('Asia/Kuala_Lumpur')
```

### 5. Helper: Helpers
**Lokasi**: `app/Helpers/Helpers.php`

**Methods**:
- `addWorkingDays($startDate, $workingDays)` - Tambah hari bekerja (skip weekend & cuti umum)
- `isPublicHoliday($date)` - Semak cuti umum Malaysia
- `getMalaysianPublicHolidays()` - Senarai cuti umum Malaysia

## Kriteria Email Dihantar

Email reminder akan dihantar jika tiket memenuhi kriteria berikut:

1. ✅ Status tiket = 1 (Aduan Baru)
2. ✅ tick_duedate = esok (tomorrow)
3. ✅ Ada Pegawai Tugasan L1 yang assigned (level_pegtugas_id = 1)
4. ✅ Pegawai mempunyai alamat email

## Cara Test

### Test Via Command Line
```bash
php artisan tickets:send-duedate-reminders
```

### Test Via API (Authenticated)
```
GET /api/tickets/test-due-date-reminders
```

### Semak Schedule
```bash
php artisan schedule:list
```

### Trigger Schedule Manually
```bash
php artisan schedule:run
```

## Cara Semak Email Dihantar

### 1. Semak Log File
```bash
# Windows PowerShell
Get-Content "storage/logs/laravel-$(Get-Date -Format 'yyyy-MM-dd').log" | Select-String "Due date reminder"

# Linux/Mac
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "Due date reminder"
```

### 2. Log Info Yang Direkod

**Success Log**:
```json
{
    "message": "Due date reminder sent successfully",
    "ticket_id": 10,
    "ticket_nolog": "HELP-2026-01-0005",
    "officer_id": 2498,
    "officer_name": "ZAIRINA BINTI MOHD ZAID",
    "officer_email": "zairina.zaid@treasury.gov.my",
    "due_date": "2026-01-28",
    "reminder_date": "2026-01-27"
}
```

**Warning Log** (No officer assigned):
```json
{
    "message": "Ticket has no Level 1 officer assigned",
    "ticket_id": 10,
    "ticket_nolog": "HELP-2026-01-0005"
}
```

**Warning Log** (No email):
```json
{
    "message": "Officer has no email address",
    "ticket_id": 10,
    "ticket_nolog": "HELP-2026-01-0005",
    "officer_id": 2498,
    "officer_name": "Ahmad"
}
```

### 3. Semak Database

Query untuk check ticket yang akan dapat reminder hari ini:
```sql
SELECT 
    t.id,
    t.ticket_nolog,
    t.tick_duedate,
    t.tick_status,
    tc.escalate_to as officer_id,
    u.nama as officer_name,
    u.email as officer_email
FROM tickets t
LEFT JOIN ticket_chronologies tc ON t.id = tc.ticket_id 
    AND tc.level_pegtugas_id = 1
LEFT JOIN users u ON tc.escalate_to = u.id
WHERE t.tick_status = 1
    AND DATE(t.tick_duedate) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    AND tc.escalate_to IS NOT NULL
ORDER BY t.created_at DESC;
```

## Setup Cronjob (Production)

### Linux/Mac Crontab
```bash
# Edit crontab
crontab -e

# Tambah baris ini
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Windows Task Scheduler
1. Buka Task Scheduler
2. Create Basic Task
3. Trigger: Daily at 9:00 AM
4. Action: Start a program
   - Program: `php`
   - Arguments: `artisan tickets:send-duedate-reminders`
   - Start in: `C:\path\to\ehelp`

## Troubleshooting

### Email tidak dihantar

1. **Semak mail configuration**
   ```bash
   php artisan config:cache
   ```

2. **Test mail connection**
   ```bash
   php artisan tinker
   Mail::raw('Test', function($msg) {
       $msg->to('test@example.com')->subject('Test');
   });
   ```

3. **Semak queue settings**
   - Jika guna queue, pastikan queue worker running:
   ```bash
   php artisan queue:work
   ```

4. **Semak .env file**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=mailpit
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS="hello@example.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

### Scheduler tidak run

1. **Verify scheduler berjalan**
   ```bash
   php artisan schedule:list
   ```

2. **Test manually**
   ```bash
   php artisan schedule:run
   ```

3. **Semak cronjob setup** (Production)
   ```bash
   crontab -l
   ```

### Tiket tidak dijumpai

1. **Semak due date calculation**
   - Due date mestilah ESOK (tomorrow)
   - Status mestilah = 1 (Aduan Baru)

2. **Semak pegawai assignment**
   ```sql
   SELECT * FROM ticket_chronologies 
   WHERE ticket_id = YOUR_TICKET_ID 
   AND level_pegtugas_id = 1 
   ORDER BY created_at DESC;
   ```

## Update Notes

**Tarikh**: 27 Januari 2026

**Perubahan**:
1. ✅ Pindahkan business logic dari Command ke TicketDueDateController
2. ✅ Betulkan logic reminder (check esok, bukan hari ini)
3. ✅ Daftarkan command dalam scheduler (daily at 9:00 AM)
4. ✅ Tambah API endpoint untuk testing
5. ✅ Improve logging dengan detail lengkap
6. ✅ Tambah validation untuk officer email

**Fixed Issues**:
- ❌ Email tidak dihantar → ✅ Command tidak didaftarkan dalam scheduler
- ❌ Logic salah → ✅ Check ticket due esok, bukan hari ini
- ❌ Tiada centralized controller → ✅ TicketDueDateController dicipta
