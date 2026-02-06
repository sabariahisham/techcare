# 📋 SLA Notification System - Complete Implementation

**Date:** February 6, 2026  
**Status:** ✅ FIXED & PRODUCTION READY  
**Version:** 2.0 (Unified)

---

## 🎯 Implementation Overview

### Requirements Met

✅ **3 Types of Notifications:**
1. **Reminder:** 1 hari sebelum sla_duedate
2. **Overdue:** 1 hari selepas sla_duedate
3. **Due Date:** 3 hari selepas created_at (status=1 sahaja)

✅ **Technical Requirements:**
- ❌ **NO Laravel Queue** - Direct email sending
- ✅ **Working Days Calculation** - Skip weekends & public holidays
- ✅ **Scheduled at 7:30 AM** - Daily execution
- ✅ **No Duplicates** - Unified service & command
- ✅ **No Conflicts** - Deprecated old logic

---

## 📁 File Structure

### ✅ Created/Updated Files

```
app/
├── Console/Commands/
│   └── SendSLANotifications.php          ✅ NEW - Unified command
├── Services/
│   └── TicketSLAReminderService.php      ✅ UPDATED - Main logic
├── Notifications/Ticket/
│   ├── ReminderNotification.php          ✅ EXISTS
│   ├── SLAOverdueNotification.php        ✅ UPDATED
│   └── DueDateNotification.php           ✅ UPDATED
├── Helpers/
│   └── WorkingDaysHelper.php             ✅ EXISTS
└── Models/
    ├── Ticket.php                        ✅ EXISTS
    └── TicketChronology.php              ✅ EXISTS

resources/views/emails/ticket/
├── sla-reminder.blade.php                ✅ EXISTS
├── sla-overdue.blade.php                 ✅ UPDATED
└── due-date.blade.php                    ✅ UPDATED

developer-guide/notification/
├── 03_SLA_NOTIFICATION_GUIDE.md          ✅ UPDATED - Main docs
├── SLA_NOTIFICATION_QUICK_REF.md         ✅ NEW - Quick reference
└── SLA_NOTIFICATION_IMPLEMENTATION.md    ✅ NEW - This file
```

### ⚠️ Deprecated Files

```
app/Http/Controllers/Notifications/
└── TicketDueDateController.php           ⚠️ DEPRECATED - Old logic

app/Notifications/Ticket/
└── SLAReminderNotification.php           ❌ REMOVED - Duplicate
```

---

## 🔧 Technical Implementation

### 1. Service Layer (TicketSLAReminderService.php)

**Location:** `app/Services/TicketSLAReminderService.php`

**Methods:**
```php
// Send reminder 1 day before
public function sendReminderNotifications(): array

// Send overdue 1 day after
public function sendOverdueNotifications(): array

// Send due date 3 days after created (status=1)
public function sendDueDateNotifications(): array
```

**Key Features:**
- Uses `WorkingDaysHelper` for date calculations
- Fetches latest chronology per ticket
- Validates recipient emails
- Logs all activities
- Returns statistics (sent/failed/skipped)

**Example Query (Reminder):**
```php
$reminderDate = WorkingDaysHelper::addWorkingDays(now(), 1);

$tickets = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', function ($join) {
        $join->on('tc.ticket_id', '=', 't.id')
            ->whereRaw('tc.id = (
                SELECT id FROM tickets_chronologies 
                WHERE ticket_id = t.id 
                AND deleted_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT 1
            )');
    })
    ->leftJoin('users as u', 'u.id', '=', 'tc.frm_user_id')
    ->whereNull('t.deleted_at')
    ->whereNull('tc.deleted_at')
    ->whereNotNull('tc.sla_duedate')
    ->whereNotIn('tc.tick_status', [5, 7])
    ->whereDate('tc.sla_duedate', $reminderDate->format('Y-m-d'))
    ->select('t.*', 'tc.sla_duedate', 'tc.frm_user_id', 'tc.tick_status')
    ->get();
```

### 2. Console Command (SendSLANotifications.php)

**Location:** `app/Console/Commands/SendSLANotifications.php`

**Signature:** `sla:send-notifications`

**Description:** Send all SLA notifications (reminder, overdue, due date)

**Execution:**
```bash
php artisan sla:send-notifications
```

**Schedule (Kernel.php):**
```php
$schedule->command('sla:send-notifications')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->onOneServer();
```

### 3. Notification Classes

#### ReminderNotification.php
```php
namespace App\Notifications\Ticket;

class ReminderNotification extends Notification
{
    // NO implements ShouldQueue
    
    public function via($notifiable): array
    {
        return ['mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⏳ Peringatan SLA - Hampir Tamat Tempoh')
            ->markdown('emails.ticket.sla-reminder', [
                'ticket' => $this->ticket,
                'reminderMessage' => 'Tiket akan tamat tempoh esok...'
            ]);
    }
}
```

#### SLAOverdueNotification.php
```php
namespace App\Notifications\Ticket;

class SLAOverdueNotification extends Notification
{
    // NO implements ShouldQueue
    
    public function via($notifiable): array
    {
        return ['mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🚨 Amaran: Tiket SLA OVERDUE')
            ->markdown('emails.ticket.sla-overdue', [
                'ticket' => $this->ticket,
                'daysOverdue' => $this->daysOverdue
            ]);
    }
}
```

#### DueDateNotification.php
```php
namespace App\Notifications\Ticket;

class DueDateNotification extends Notification
{
    // NO implements ShouldQueue
    
    public function via($notifiable): array
    {
        return ['mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📅 Peringatan: Tiket Menunggu Tindakan')
            ->markdown('emails.ticket.due-date', [
                'ticket' => $this->ticket,
                'daysSinceCreated' => 3
            ]);
    }
}
```

### 4. Working Days Helper

**Location:** `app/Helpers/WorkingDaysHelper.php`

**Key Methods:**
```php
// Check if date is working day
public static function isWorkingDay(Carbon $date): bool

// Add working days
public static function addWorkingDays(Carbon $date, int $days): Carbon

// Subtract working days
public static function subtractWorkingDays(Carbon $date, int $days): Carbon

// Check if date is public holiday
public static function isPublicHoliday(Carbon $date): bool
```

**Logic:**
- Skips Saturday & Sunday
- Skips dates in `public_holidays` table
- Recursive calculation for multiple days

---

## 🗄️ Database Structure

### Tables Used

#### tickets_chronologies
```sql
CREATE TABLE tickets_chronologies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    frm_user_id BIGINT UNSIGNED NULL,        -- Pegawai Tugasan
    level_pegtugas_id INT NULL,
    tick_status INT NOT NULL,                -- Status tiket
    escalate_to BIGINT UNSIGNED NULL,
    sla_duedate DATETIME NULL,               -- SLA due date
    sla_days INT NULL,
    sla_severity VARCHAR(50) NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (frm_user_id) REFERENCES users(id)
);

-- Index untuk performance
CREATE INDEX idx_sla_duedate ON tickets_chronologies(sla_duedate);
CREATE INDEX idx_tick_status ON tickets_chronologies(tick_status);
CREATE INDEX idx_created_at ON tickets_chronologies(created_at);
```

#### public_holidays
```sql
CREATE TABLE public_holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ph_date DATE NOT NULL UNIQUE,
    ph_name VARCHAR(255) NOT NULL,
    ph_type VARCHAR(50),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX idx_ph_date ON public_holidays(ph_date);
```

### Query Logic

#### Get Latest Chronology Per Ticket
```sql
-- Subquery approach (used in service)
SELECT tc.*
FROM tickets_chronologies tc
WHERE tc.id = (
    SELECT id 
    FROM tickets_chronologies 
    WHERE ticket_id = tc.ticket_id 
      AND deleted_at IS NULL 
    ORDER BY created_at DESC 
    LIMIT 1
)
```

---

## 📧 Email Templates

### sla-reminder.blade.php
```blade
@component('mail::message')
<div style="background: #ffc107; padding: 14px; text-align: center;">
    ⏳ Peringatan SLA - Hampir Tamat Tempoh
</div>

{{ $reminderMessage }}

@component('mail::panel')
**No. Log:** {{ $ticket->ticket_nolog }}
**Tajuk:** {{ $ticket->ticket_title }}
**Tarikh Akhir SLA:** {{ $ticket->latestSlaDueDate }}
@endcomponent

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid)])
Lihat Tiket
@endcomponent
@endcomponent
```

### sla-overdue.blade.php
```blade
@component('mail::message')
<div style="background: #dc3545; color: white; padding: 14px;">
    🚨 AMARAN: TIKET SLA OVERDUE
</div>

Tiket telah OVERDUE {{ $daysOverdue }} hari.

@component('mail::panel')
**No. Log:** {{ $ticket->ticket_nolog }}
**Status:** {{ $ticket->status->st_name }}
**SLA Due Date:** {{ $ticket->latestSlaDueDate }}
@endcomponent

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid)])
Ambil Tindakan Segera
@endcomponent
@endcomponent
```

---

## ⏰ Scheduling

### Laravel Scheduler Setup

**File:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    // SLA Notifications - 7:30 AM daily
    $schedule->command('sla:send-notifications')
        ->dailyAt('07:30')
        ->withoutOverlapping(10)  // Prevent multiple instances
        ->onOneServer()            // Run on single server only
        ->appendOutputTo(storage_path('logs/sla-notifications.log'));
}
```

### Cron Setup

**Linux:**
```bash
* * * * * cd /path-to-ehelp && php artisan schedule:run >> /dev/null 2>&1
```

**Windows Task Scheduler:**
- Program: `php.exe`
- Arguments: `c:\path\to\ehelp\artisan schedule:run`
- Trigger: Every 1 minute
- Run whether user is logged on or not

---

## 🧪 Testing

### Manual Testing

```bash
# Test all notifications
php artisan sla:send-notifications

# With verbose output
php artisan sla:send-notifications -v

# Test scheduler
php artisan schedule:run

# View scheduled commands
php artisan schedule:list
```

### Service Testing

```php
php artisan tinker

use App\Services\TicketSLAReminderService;

$service = app(TicketSLAReminderService::class);

// Test each method
$reminderResult = $service->sendReminderNotifications();
$overdueResult = $service->sendOverdueNotifications();
$dueDateResult = $service->sendDueDateNotifications();

// Check results
print_r($reminderResult);
```

### Database Testing

```sql
-- Check for reminder tickets (tomorrow)
SELECT t.ticket_nolog, tc.sla_duedate, tc.tick_status, u.nama
FROM tickets t
JOIN tickets_chronologies tc ON tc.id = (
    SELECT id FROM tickets_chronologies 
    WHERE ticket_id = t.id AND deleted_at IS NULL 
    ORDER BY created_at DESC LIMIT 1
)
LEFT JOIN users u ON u.id = tc.frm_user_id
WHERE DATE(tc.sla_duedate) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))
  AND tc.tick_status NOT IN (5, 7);

-- Check for overdue tickets (yesterday)
SELECT t.ticket_nolog, tc.sla_duedate, tc.tick_status
FROM tickets t
JOIN tickets_chronologies tc ON tc.id = (
    SELECT id FROM tickets_chronologies 
    WHERE ticket_id = t.id AND deleted_at IS NULL 
    ORDER BY created_at DESC LIMIT 1
)
WHERE DATE(tc.sla_duedate) = DATE(DATE_SUB(NOW(), INTERVAL 1 DAY))
  AND tc.tick_status NOT IN (5, 7);

-- Check for due date tickets (3 days old, status=1)
SELECT t.ticket_nolog, t.created_at, tc.tick_status
FROM tickets t
JOIN tickets_chronologies tc ON tc.id = (
    SELECT id FROM tickets_chronologies 
    WHERE ticket_id = t.id AND deleted_at IS NULL 
    ORDER BY created_at DESC LIMIT 1
)
WHERE DATE(t.created_at) = DATE(DATE_SUB(NOW(), INTERVAL 3 DAY))
  AND tc.tick_status = 1;
```

---

## 📊 Monitoring & Logging

### Log Files

```bash
# Laravel application log
storage/logs/laravel.log

# SLA notifications log (if configured)
storage/logs/sla-notifications.log
```

### Log Format

```
[2026-02-06 07:30:01] local.INFO: [SLA Reminder] Starting reminder notifications
[2026-02-06 07:30:02] local.INFO: [SLA Reminder] Found 5 tickets to process
[2026-02-06 07:30:03] local.INFO: [SLA Reminder] Sent to: user@example.com for ticket: TK-2026-001
[2026-02-06 07:30:04] local.INFO: [SLA Reminder] Completed - Sent: 5, Failed: 0, Skipped: 0
```

### Monitoring Points

1. **Check Daily Execution:**
   - Review logs at 7:30 AM
   - Verify all 3 notification types ran

2. **Monitor Email Delivery:**
   - Check mail logs
   - Verify bounce rates
   - Monitor spam reports

3. **Database Queries:**
   - Monitor query performance
   - Check for slow queries
   - Optimize indexes if needed

---

## 🔧 Troubleshooting

### Issue: Emails Not Sending

**Check:**
1. Mail configuration in `.env`
2. SMTP credentials valid
3. Mail service running
4. Firewall blocking port 587/465

**Test:**
```php
php artisan tinker
Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

### Issue: Wrong Recipients

**Check:**
1. `tickets_chronologies.frm_user_id` correct
2. User email exists in `users` table
3. Latest chronology query working

**Debug:**
```sql
SELECT tc.*, u.email
FROM tickets_chronologies tc
LEFT JOIN users u ON u.id = tc.frm_user_id
WHERE tc.ticket_id = 123
ORDER BY tc.created_at DESC
LIMIT 1;
```

### Issue: Wrong Dates

**Check:**
1. Working days calculation
2. Public holidays in database
3. Server timezone

**Test:**
```php
php artisan tinker
use App\Helpers\WorkingDaysHelper;

WorkingDaysHelper::addWorkingDays(now(), 1);
WorkingDaysHelper::isWorkingDay(now());
```

### Issue: Schedule Not Running

**Check:**
1. Cron job configured
2. Laravel scheduler running
3. Command in schedule list

**Verify:**
```bash
php artisan schedule:list
php artisan schedule:run
```

---

## ✅ Checklist

### Pre-Production

- [ ] Mail configuration tested
- [ ] Public holidays populated
- [ ] Cron job configured
- [ ] Email templates reviewed
- [ ] Test notifications sent
- [ ] Logs monitored
- [ ] Database indexes created
- [ ] Documentation reviewed

### Post-Deployment

- [ ] Monitor first execution at 7:30 AM
- [ ] Verify emails received
- [ ] Check logs for errors
- [ ] Confirm recipient accuracy
- [ ] Test manual trigger works
- [ ] Schedule list shows command
- [ ] Working days calculation correct

---

## 📚 References

### Documentation
- [03_SLA_NOTIFICATION_GUIDE.md](03_SLA_NOTIFICATION_GUIDE.md) - Complete guide
- [SLA_NOTIFICATION_QUICK_REF.md](SLA_NOTIFICATION_QUICK_REF.md) - Quick reference
- [README.md](README.md) - Documentation index

### Related Files
- Service: [TicketSLAReminderService.php](../../app/Services/TicketSLAReminderService.php)
- Command: [SendSLANotifications.php](../../app/Console/Commands/SendSLANotifications.php)
- Helper: [WorkingDaysHelper.php](../../app/Helpers/WorkingDaysHelper.php)

---

**Version:** 2.0 (Unified)  
**Status:** ✅ Production Ready  
**Last Updated:** February 6, 2026  
**Maintained By:** Development Team
