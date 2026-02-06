# 🚀 SLA Notification Quick Reference

**Last Updated:** February 6, 2026

## 📌 Quick Summary

| Notification Type | Trigger | Time | Status | Working Days |
|-------------------|---------|------|--------|--------------|
| **Reminder** | 1 day BEFORE sla_duedate | 7:30 AM | All except 5,7 | Yes |
| **Overdue** | 1 day AFTER sla_duedate | 7:30 AM | All except 5,7 | Yes |
| **Due Date** | 3 days after created_at | 7:30 AM | Status = 1 only | Yes |

## 🎯 Key Points

### ✅ FIXED Issues
- ❌ **REMOVED** duplicate `SLAReminderNotification` class
- ❌ **DEPRECATED** old `TicketDueDateController` logic
- ✅ **UNIFIED** service: `TicketSLAReminderService`
- ✅ **UNIFIED** command: `sla:send-notifications`
- ✅ **NO QUEUE** - All emails sent directly
- ✅ **7:30 AM** - Consistent schedule time

### 📧 Notification Classes

```php
// 1. Reminder (1 day before)
App\Notifications\Ticket\ReminderNotification

// 2. Overdue (1 day after)
App\Notifications\Ticket\SLAOverdueNotification

// 3. Due Date (3 days after created, status=1)
App\Notifications\Ticket\DueDateNotification
```

### 🔧 Service Methods

```php
use App\Services\TicketSLAReminderService;

$service = app(TicketSLAReminderService::class);

// Send reminder (1 day before)
$service->sendReminderNotifications();

// Send overdue (1 day after)
$service->sendOverdueNotifications();

// Send due date (3 days after created, status=1)
$service->sendDueDateNotifications();
```

## 🧪 Quick Testing

### Command Line
```bash
# Run all notifications
php artisan sla:send-notifications

# With verbose output
php artisan sla:send-notifications -v
```

### Check Schedule
```bash
# View scheduled commands
php artisan schedule:list

# Run scheduler manually
php artisan schedule:run
```

### Check Logs
```bash
# Windows
Get-Content storage\logs\laravel.log -Tail 50

# Linux
tail -f storage/logs/laravel.log
```

## 🔍 Quick Debugging

### Check Database

```sql
-- Reminder: Tickets 1 day before due date
SELECT t.ticket_nolog, tc.sla_duedate, tc.tick_status
FROM tickets t
JOIN tickets_chronologies tc ON tc.ticket_id = t.id
WHERE tc.sla_duedate IS NOT NULL
  AND tc.tick_status NOT IN (5, 7)
  AND DATE(tc.sla_duedate) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY));

-- Overdue: Tickets 1 day after due date
SELECT t.ticket_nolog, tc.sla_duedate, tc.tick_status
FROM tickets t
JOIN tickets_chronologies tc ON tc.ticket_id = t.id
WHERE tc.sla_duedate IS NOT NULL
  AND tc.tick_status NOT IN (5, 7)
  AND DATE(tc.sla_duedate) = DATE(DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Due Date: Tickets 3 days after created (status=1)
SELECT t.ticket_nolog, t.created_at, tc.tick_status
FROM tickets t
JOIN tickets_chronologies tc ON tc.ticket_id = t.id
WHERE tc.tick_status = 1
  AND DATE(t.created_at) = DATE(DATE_SUB(NOW(), INTERVAL 3 DAY));
```

### Check Working Days

```php
php artisan tinker

use App\Helpers\WorkingDaysHelper;

// Check if today is working day
WorkingDaysHelper::isWorkingDay(now());

// Add 1 working day
WorkingDaysHelper::addWorkingDays(now(), 1);

// Subtract 1 working day
WorkingDaysHelper::subtractWorkingDays(now(), 1);
```

## 📋 Checklist

### Initial Setup
- [ ] Mail configuration dalam `.env`
- [ ] Public holidays dalam database
- [ ] Cron job setup untuk scheduler
- [ ] Test notification berjalan

### Verification
- [ ] Check logs untuk errors
- [ ] Verify email templates
- [ ] Test working days calculation
- [ ] Confirm schedule time (7:30 AM)

### Troubleshooting
- [ ] Mail config correct?
- [ ] Cron job running?
- [ ] Public holidays updated?
- [ ] Logs show errors?

## 📊 Expected Behavior

### Scenario 1: New Ticket Created
```
Day 0: Ticket created (status=1)
Day 3: Due date notification sent (7:30 AM)
```

### Scenario 2: Ticket with SLA
```
Day -1: Reminder sent (7:30 AM) - 1 day before due
Day 0:  Due date
Day +1: Overdue sent (7:30 AM) - 1 day after due
```

### Scenario 3: Weekend Handling
```
Friday:  sla_duedate set
Thursday: Reminder sent (skip weekend)
Monday:   Overdue sent (skip weekend)
```

## 🔗 Related Files

### Core Logic
- [TicketSLAReminderService.php](../../app/Services/TicketSLAReminderService.php)
- [SendSLANotifications.php](../../app/Console/Commands/SendSLANotifications.php)

### Notifications
- [ReminderNotification.php](../../app/Notifications/Ticket/ReminderNotification.php)
- [SLAOverdueNotification.php](../../app/Notifications/Ticket/SLAOverdueNotification.php)
- [DueDateNotification.php](../../app/Notifications/Ticket/DueDateNotification.php)

### Email Templates
- [sla-reminder.blade.php](../../resources/views/emails/ticket/sla-reminder.blade.php)
- [sla-overdue.blade.php](../../resources/views/emails/ticket/sla-overdue.blade.php)
- [due-date.blade.php](../../resources/views/emails/ticket/due-date.blade.php)

### Helpers
- [WorkingDaysHelper.php](../../app/Helpers/WorkingDaysHelper.php)

## 📖 Full Documentation

For complete documentation, see:
- [03_SLA_NOTIFICATION_GUIDE.md](03_SLA_NOTIFICATION_GUIDE.md) - Complete guide
- [README.md](README.md) - Index of all documentation

## 🆘 Quick Help

### Email Not Sending?
1. Check `.env` mail config
2. Test: `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));`
3. Check logs: `storage/logs/laravel.log`

### Notifications Not Triggering?
1. Run manually: `php artisan sla:send-notifications -v`
2. Check schedule: `php artisan schedule:list`
3. Verify cron: `php artisan schedule:run`

### Wrong Recipients?
1. Check `tickets_chronologies` table for correct `frm_user_id`
2. Verify user has email in `users` table
3. Check logs for skipped notifications

---

**Need more details?** See [03_SLA_NOTIFICATION_GUIDE.md](03_SLA_NOTIFICATION_GUIDE.md)
