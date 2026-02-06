# 📧 SLA Notification System - Quick Start

## 🎯 Overview

Sistem untuk menghantar email reminder kepada Pegawai Tugasan berdasarkan SLA (Service Level Agreement) due dates. Support eskalasi Level 1 → Level 2.

## 📋 Reminder Types

| Type | Timing | Subject |
|------|--------|---------|
| **SLA-1** | 1 day before due | ⚠️ Hampir Tamat Tempoh |
| **Due Today** | On due date | 🔔 Tamat Tempoh HARI INI |
| **Due Date+1** | 1 day after due | ⚠️ Tempoh Tamat Semalam |
| **Overdue** | >1 day overdue | ⛔ Tindakan Segera: OVERDUE |

## 🚀 Quick Test

### Option 1: Simple PHP Script
```bash
cd c:\Users\sabariah.hisham\Herd\ehelp-fork_29122025
php test-sla-simple.php
```

### Option 2: PowerShell Script
```powershell
.\test-sla-notifications.ps1
```

### Option 3: API Endpoints

#### Send All Notifications
```bash
POST /api/sla/notifications/all
Authorization: Bearer YOUR_TOKEN
```

#### Send Specific Type
```bash
POST /api/sla/notifications/before-due  # SLA-1
POST /api/sla/notifications/due-today   # Due today
POST /api/sla/notifications/after-due   # Due date+1
POST /api/sla/notifications/overdue     # Overdue
```

#### Get Reports
```bash
GET /api/sla/tickets/due-tomorrow  # Tickets due tomorrow
GET /api/sla/tickets/due-today     # Tickets due today
GET /api/sla/tickets/overdue       # Overdue tickets
GET /api/sla/upcoming?days=7       # Upcoming in next 7 days
GET /api/sla/statistics            # SLA statistics
```

## 📁 Files Modified/Created

### New Files
- `app/Notifications/Ticket/ReminderNotification.php`
- `app/Services/TicketReminderService.php`
- `resources/views/emails/ticket/sla-reminder.blade.php`
- `developer-guide/SLA_NOTIFICATION_GUIDE.md`
- `test-sla-simple.php`
- `test-sla-notifications.ps1`

### Modified Files
- `app/Models/Ticket.php` - Added relationships
- `app/Http/Controllers/Notifications/TicketOverdueController.php` - New methods
- `app/Http/Controllers/Ticket/TicketSLAController.php` - Reporting methods
- `routes/api.php` - New SLA routes

## ⚙️ Setup Automation (Optional)

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // SLA-1 reminders (due tomorrow) at 9 AM
    $schedule->call(function () {
        app(TicketReminderService::class)->sendBeforeDueReminders();
    })->dailyAt('09:00');

    // Due today reminders at 8 AM
    $schedule->call(function () {
        app(TicketReminderService::class)->sendDueTodayReminders();
    })->dailyAt('08:00');

    // After due reminders at 10 AM
    $schedule->call(function () {
        app(TicketReminderService::class)->sendAfterDueReminders();
    })->dailyAt('10:00');

    // Overdue notifications at 11 AM
    $schedule->call(function () {
        app(TicketReminderService::class)->sendOverdueNotifications();
    })->dailyAt('11:00');
}
```

Then setup Windows Task Scheduler:
```cmd
php c:\path\to\artisan schedule:run
```
Run every minute.

## 📊 Test Results (27 Jan 2026)

✅ **Test Status: SUCCESS**

- Database Connection: ✅ OK
- Total Tickets: 15
- Active Tickets: 12
- Overdue Found: 2
- Notifications Sent: 2 ✅
- Failures: 0
- Success Rate: 100%

### Recipients Verified
1. ✅ hafizzuddin@mof.gov.my
2. ✅ faizal.pauzi@treasury.gov.my

## 📝 Logs

Check logs for notification activity:
```bash
# View today's logs
tail -f storage/logs/laravel-2026-01-27.log

# Search for SLA notifications
grep "SLA" storage/logs/laravel-2026-01-27.log
```

## 🔍 Troubleshooting

### No tickets found?
- Check `sla_duedate` exists in `tickets_chronologies` table
- Verify ticket status is not 5 (Selesai) or 6 (Tutup)
- Check date ranges

### Emails not sent?
- Verify `.env` email configuration
- Check recipient has valid email address
- Check `escalate_to` field in chronology
- Review Laravel logs

### Authentication error?
- Get valid Bearer token
- Check API authentication middleware
- Verify user permissions

## 📚 Documentation

Full documentation: `developer-guide/SLA_NOTIFICATION_GUIDE.md`
Implementation summary: `IMPLEMENTATION_SUMMARY.md`

## ✅ Status

**System Status**: ✅ Production Ready  
**Last Tested**: 27 January 2026  
**Test Result**: SUCCESS (2/2 notifications sent)
