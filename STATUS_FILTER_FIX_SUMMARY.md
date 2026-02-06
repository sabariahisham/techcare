# Status Filter Fix Summary

## Issue
System was incorrectly using status codes **5 and 7** to filter completed/cancelled tickets, but the database only has status 1-6:
- Status 5 = **Batal** (DiBatal/Cancelled)
- Status 6 = **Selesai** (Completed)
- Status 7 = **Does not exist**

## Changes Made

### 1. Service Layer
**File**: [app/Services/TicketSLAReminderService.php](app/Services/TicketSLAReminderService.php)
- ✅ Updated header comment from "status 5, 7" to "5=Batal, 6=Selesai"
- ✅ Updated `sendBeforeDueReminders()` method comment
- ✅ Updated `sendAfterDueReminders()` method comment
- ✅ Changed filter from `whereNotIn('tick_status', [5, 7])` to `whereNotIn('tick_status', [5, 6])`

### 2. Routes/Scheduler
**File**: [routes/console.php](routes/console.php)
- ✅ Updated schedule comment from "skip status 5, 7" to "5=Batal, 6=Selesai"

### 3. Test Script
**File**: [test-sla-notifications.php](test-sla-notifications.php)
- ✅ Updated reminder candidate status check from `[5, 7]` to `[5, 6]`
- ✅ Updated reminder instructions from "!= 5 or 7" to "!= 5 (Batal) or 6 (Selesai)"
- ✅ Updated overdue candidate status check from `[5, 7]` to `[5, 6]`
- ✅ Updated overdue instructions from "!= 5 or 7" to "!= 5 (Batal) or 6 (Selesai)"
- ✅ Updated overdue tickets filter from `[5, 6]` (already correct)
- ✅ Updated reminder SQL query from `NOT IN (5, 7)` to `NOT IN (5, 6) -- 5=Batal, 6=Selesai`
- ✅ Updated overdue SQL query from `NOT IN (5, 7)` to `NOT IN (5, 6) -- 5=Batal, 6=Selesai`
- ✅ Fixed syntax errors with `??` operator inside double quotes

### 4. Documentation Files
**File**: [developer-guide/notification/README.md](developer-guide/notification/README.md)
- ✅ Updated "except 5, 7" to "except 5=Batal, 6=Selesai"

**File**: [developer-guide/notification/03_SLA_NOTIFICATION_GUIDE.md](developer-guide/notification/03_SLA_NOTIFICATION_GUIDE.md)
- ✅ Updated reminder SQL query from `NOT IN (5, 7)` to `NOT IN (5, 6) -- 5=Batal, 6=Selesai`
- ✅ Updated overdue SQL query from `NOT IN (5, 7)` to `NOT IN (5, 6) -- 5=Batal, 6=Selesai`

**File**: [developer-guide/notification/SLA_NOTIFICATION_VISUAL_GUIDE.md](developer-guide/notification/SLA_NOTIFICATION_VISUAL_GUIDE.md)
- ✅ Updated reminder flowchart from "NOT IN (5, 7)" to "NOT IN (5, 6) -- 5=Batal, 6=Selesai"
- ✅ Updated overdue flowchart from "NOT IN (5, 7)" to "NOT IN (5, 6) -- 5=Batal, 6=Selesai"
- ✅ Updated filter diagram from "NOT IN (5, 7)" to "NOT IN (5, 6)" with proper labels

## Status Reference

| Status Code | Name | Malay Name | Should Skip Notification |
|-------------|------|------------|--------------------------|
| 1 | New Ticket | Aduan Baru | ❌ No |
| 2 | Processing | Aduan Diproses | ❌ No |
| 3 | Handed Over | Serahan Kepada | ❌ No |
| 4 | Referred | Aduan Dirujuk | ❌ No |
| 5 | **Cancelled** | **Aduan DiBatal** | **✅ Yes** |
| 6 | **Completed** | **Aduan Selesai** | **✅ Yes** |

## Testing

✅ Test script executed successfully:
```bash
php test-sla-notifications.php
```

**Results:**
- Total tickets with sla_duedate: 11
- Status breakdown shows correct status names
- Overdue tickets correctly exclude status 6 (Selesai)
- All SQL queries display correct status filter (5, 6)

**Commands available:**
```bash
# Test reminder notifications
php artisan sla:send-notifications --type=reminder

# Test overdue notifications
php artisan sla:send-notifications --type=overdue

# Test both
php artisan sla:send-notifications --type=all
```

## Impact

### ✅ Before Fix
- System was skipping status 5 (Batal) correctly
- System was **incorrectly** trying to skip non-existent status 7
- Potential to send notifications to completed tickets (status 6)

### ✅ After Fix
- System correctly skips status 5 (Batal/Cancelled)
- System correctly skips status 6 (Selesai/Completed)
- All documentation and code comments are consistent
- Clear labels showing what each status means

## Schedule

The SLA notification command runs daily at **7:30 AM**:
```php
$schedule->command('sla:send-notifications --type=all')
    ->dailyAt('07:30')
    ->timezone('Asia/Kuala_Lumpur');
```

Notifications sent:
1. **Reminder**: 1 working day BEFORE sla_duedate (skip status 5, 6)
2. **Overdue**: 1 working day AFTER sla_duedate (skip status 5, 6)

---
**Date Fixed**: 2026-02-06  
**Laravel Version**: 11.x  
**PHP Version**: 8.4.16
