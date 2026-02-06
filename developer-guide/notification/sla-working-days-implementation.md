# SLA Working Days Implementation

## Overview
System menggunakan **working days** (hari bekerja) untuk SLA reminder calculations, bukan calendar days. Working days tidak termasuk:
- Weekends (Saturday & Sunday)
- Malaysian Federal Public Holidays

## Working Day Helper Functions

### 1. `Helpers::addWorkingDays($date, $days)`
Menambah working days ke tarikh tertentu.
```php
// Example: Add 1 working day from Friday
$friday = Carbon::parse('2025-01-17'); // Friday
$nextDay = Helpers::addWorkingDays($friday, 1);
// Result: 2025-01-20 (Monday) - skip weekend
```

### 2. `Helpers::subtractWorkingDays($date, $days)`
Mengurangkan working days dari tarikh tertentu.
```php
// Example: Subtract 1 working day from Monday
$monday = Carbon::parse('2025-01-20'); // Monday
$prevDay = Helpers::subtractWorkingDays($monday, 1);
// Result: 2025-01-17 (Friday) - skip weekend
```

### 3. `Helpers::isWorkingDay($date)`
Check jika tarikh adalah working day.
```php
$date = Carbon::parse('2025-01-18'); // Saturday
$isWorkingDay = Helpers::isWorkingDay($date);
// Result: false (weekend)
```

### 4. `Helpers::getWorkingDaysBetween($start, $end)`
Kira bilangan working days antara 2 tarikh.
```php
$start = Carbon::parse('2025-01-17'); // Friday
$end = Carbon::parse('2025-01-20'); // Monday
$days = Helpers::getWorkingDaysBetween($start, $end);
// Result: 1 (skip weekend)
```

### 5. `Helpers::isPublicHoliday($date)`
Check jika tarikh adalah cuti umum.
```php
$date = Carbon::parse('2025-01-25'); // Thaipusam
$isHoliday = Helpers::isPublicHoliday($date);
// Result: true
```

## SLA Reminder Logic

### 1. SLA-1 (Before Due) - Reminder 1 Working Day Sebelum Due
**When**: Triggered at **start of working day**  
**Logic**: `sla_duedate = TODAY + 1 working day`  
**Example**:
- Today: Friday (17 Jan 2025)
- SLA-1 reminder akan send untuk tickets dengan sla_duedate = Monday (20 Jan 2025)
- Skip Saturday & Sunday

### 2. Due Today - Reminder Pada Hari Due
**When**: Triggered at **start of working day** (ONLY if today is working day)  
**Logic**: `sla_duedate = TODAY`  
**Example**:
- Today: Monday (20 Jan 2025) - is working day
- Due Today reminder akan send untuk tickets dengan sla_duedate = Monday (20 Jan 2025)
- If today is weekend/holiday, no reminder will be sent

### 3. Due Date+1 - Reminder 1 Working Day Selepas Due
**When**: Triggered at **start of working day**  
**Logic**: `sla_duedate = TODAY - 1 working day`  
**Example**:
- Today: Monday (20 Jan 2025)
- Due Date+1 reminder akan send untuk tickets dengan sla_duedate = Friday (17 Jan 2025)
- Skip Saturday & Sunday

### 4. Overdue - Reminder untuk Tickets Yang Dah Lama Overdue
**When**: Triggered daily  
**Logic**: `sla_duedate < (TODAY - 1 working day) - 1 day`  
**Purpose**: Untuk tickets yang dah overdue lebih dari 1 working day (exclude yang baru overdue kemarin)

## Workflow Example

### Scenario: SLA Due Date = Monday, 20 Jan 2025

| Day | Date | Reminder Type | Triggered? | Logic |
|-----|------|---------------|------------|-------|
| Friday | 17 Jan | SLA-1 | ✅ YES | 17 Jan + 1 working day = 20 Jan |
| Saturday | 18 Jan | - | ❌ NO | Not a working day |
| Sunday | 19 Jan | - | ❌ NO | Not a working day |
| Monday | 20 Jan | Due Today | ✅ YES | sla_duedate = 20 Jan |
| Tuesday | 21 Jan | Due Date+1 | ✅ YES | 21 Jan - 1 working day = 20 Jan |
| Wednesday | 22 Jan | Overdue | ✅ YES | 20 Jan < (22 Jan - 1 working day - 1 day) |

### Scenario: SLA Due Date = Friday, 17 Jan 2025 (Before Weekend)

| Day | Date | Reminder Type | Triggered? | Logic |
|-----|------|---------------|------------|-------|
| Thursday | 16 Jan | SLA-1 | ✅ YES | 16 Jan + 1 working day = 17 Jan |
| Friday | 17 Jan | Due Today | ✅ YES | sla_duedate = 17 Jan |
| Saturday | 18 Jan | - | ❌ NO | Not a working day |
| Sunday | 19 Jan | - | ❌ NO | Not a working day |
| Monday | 20 Jan | Due Date+1 | ✅ YES | 20 Jan - 1 working day = 17 Jan |
| Tuesday | 21 Jan | Overdue | ✅ YES | 17 Jan < (21 Jan - 1 working day - 1 day) |

### Scenario: SLA Due Date = Thursday, 30 Jan 2025 (Before Thaipusam Holiday)

| Day | Date | Reminder Type | Triggered? | Logic |
|-----|------|---------------|------------|-------|
| Wednesday | 29 Jan | SLA-1 | ✅ YES | 29 Jan + 1 working day = 30 Jan |
| Thursday | 30 Jan | Due Today | ✅ YES | sla_duedate = 30 Jan |
| Friday | 31 Jan | - | ❌ NO | Not sent - but it's 1 day after due |
| Saturday | 1 Feb | - | ❌ NO | Not a working day |
| Sunday | 2 Feb | - | ❌ NO | Not a working day |
| Monday | 3 Feb | Due Date+1 | ✅ YES | 3 Feb - 1 working day = 30 Jan (skip Sat/Sun) |

**Note**: In this scenario, Friday 31 Jan is NOT Thaipusam (Thaipusam 2025 is on 25 Jan). If it was a holiday, the Due Date+1 would still trigger on Monday.

## Public Holidays Configuration

Public holidays are configured in `Helpers::getMalaysianPublicHolidays()`:
- 2025 Federal Holidays ✅
- 2026 Federal Holidays ✅
- 2027 Federal Holidays ✅

**Important**: Add more years as needed for future SLA calculations.

## Implementation Files

### 1. Helpers.php
Location: `app/Helpers/Helpers.php`
Contains all working day calculation functions.

### 2. TicketReminderService.php
Location: `app/Services/TicketReminderService.php`
Main service for SLA reminder logic.

Key methods:
- `sendBeforeDueReminders()` - SLA-1 reminder
- `sendDueTodayReminders()` - Due today reminder
- `sendAfterDueReminders()` - Due Date+1 reminder
- `sendOverdueNotifications()` - Overdue reminder

### 3. Notification Classes
- `ReminderNotification.php` - Email template for SLA-1, Due Today, Due Date+1
- `OverdueNotification.php` - Email template for Overdue

## Testing

Run test script to verify working day calculations:
```bash
php test-working-days.php
```

Test output shows:
- Add working days examples (skip weekends & holidays)
- Subtract working days examples (skip weekends & holidays)
- SLA reminder logic explanation
- Weekend scenario handling
- Public holiday scenario handling

## Scheduler Configuration

Add to Laravel scheduler (`app/Console/Kernel.php`):
```php
// SLA Reminders - Run at 9:00 AM daily
$schedule->call(function () {
    app(TicketReminderService::class)->sendBeforeDueReminders();
    app(TicketReminderService::class)->sendDueTodayReminders();
    app(TicketReminderService::class)->sendAfterDueReminders();
    app(TicketReminderService::class)->sendOverdueNotifications();
})->dailyAt('09:00');
```

## API Endpoints for Manual Triggering

```bash
# Send SLA-1 reminder
POST /api/sla/notifications/send-before-due

# Send Due Today reminder
POST /api/sla/notifications/send-due-today

# Send Due Date+1 reminder
POST /api/sla/notifications/send-after-due

# Send Overdue notification
POST /api/sla/notifications/send-overdue

# Send all reminders
POST /api/sla/notifications/send-all
```

## Important Notes

1. **Working Days Only**: System tidak akan send reminder pada weekend atau public holidays
2. **Start of Day**: Reminders triggered pada start of working day (9:00 AM recommended)
3. **Skip Non-Working Days**: Calculation automatically skip Saturday, Sunday, dan public holidays
4. **Update Holidays**: Remember to add new years' public holidays to `Helpers.php`
5. **Test Before Production**: Always test with `test-working-days.php` after adding new holidays

## Advantages of Working Days Calculation

1. ✅ More accurate - follows actual working schedule
2. ✅ Fair to officers - no reminders during off days
3. ✅ Compliant - respects Malaysian public holidays
4. ✅ Predictable - consistent logic across all tickets
5. ✅ Testable - can verify calculations easily
