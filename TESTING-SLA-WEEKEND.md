# 🧪 Testing Guide: SLA Weekend Reminder System

## Overview
This guide helps you test the weekend skip logic for SLA reminders.

**Scenario**: When SLA due date is Monday, email should be sent on Friday only (not Saturday/Sunday).

---

## ✅ Quick Test Steps

### **Step 1: Check Current Date & Setup**
```bash
php test-weekend-sla.php
```

This script will:
- Show today's date and day of week
- Calculate next working day
- Show if today is ideal for testing (Friday → Monday)
- Check for existing test data
- Provide SQL to create test data if needed

---

### **Step 2: Create Test Data (if needed)**

The script above will provide SQL commands. Alternatively, use this template:

```sql
-- Find a test ticket (not Batal/Selesai)
SELECT id, ticket_nolog, tick_status FROM tickets 
WHERE tick_status NOT IN (5, 6) 
LIMIT 1;

-- Find a test user with email
SELECT id, nama, email FROM users 
WHERE email IS NOT NULL 
LIMIT 1;

-- Create test chronology (adjust the date based on test-weekend-sla.php output)
INSERT INTO tickets_chronologies 
  (ticket_id, escalate_to, level_pegtugas_id, sla_duedate, created_at, updated_at)
VALUES 
  (123, 456, 1, '2026-03-17 09:00:00', NOW(), NOW());
  -- Replace: 123 = ticket_id, 456 = user_id, date = next Monday if today is Friday
```

**Important**: The `sla_duedate` must be set to the NEXT WORKING DAY from today.

---

### **Step 3: Run Test Command**

Test the reminder notification:
```bash
php artisan sla:test reminder
```

Expected output:
```
📧 Testing REMINDER notifications (1 day before due)...
┌──────────────┬──────┐
│ Metric       │ Value│
├──────────────┼──────┤
│ Total Found  │ 1    │
│ Sent         │ 1    │
│ Failed       │ 0    │
│ Skipped      │ 0    │
└──────────────┴──────┘
```

---

### **Step 4: Verify Results**

#### **A. Check Logs**
```bash
# Check Laravel log
tail -50 storage/logs/laravel.log

# Check SLA-specific log (if configured)
tail -50 storage/logs/sla.log
```

Look for:
- `[SLA][REMINDER][START]` - Process started
- `[SLA][REMINDER][FOUND]` - Tickets found
- `SLA Reminder sent successfully` - Email sent

#### **B. Check Email**
If mail is configured, check the inbox of the test user. Email should contain:
- Subject: `⚠️ Peringatan SLA - Hampir Tamat Tempoh`
- Special note if due date is Monday: *"Nota: Tarikh akhir SLA jatuh pada hari Isnin..."*

---

## 📅 Test Scenarios

### **Scenario 1: Friday → Monday (MAIN SCENARIO)**

**Setup**: Today is Friday, SLA due date is next Monday

| Day | Time | Expected Behavior |
|-----|------|-------------------|
| Friday | 7:30 AM | ✅ **Email SENT** - "Tiket akan tamat tempoh pada hari Isnin" |
| Saturday | 7:30 AM | 🚫 **SKIPPED** - Scheduler skips weekend |
| Sunday | 7:30 AM | 🚫 **SKIPPED** - Scheduler skips weekend |
| Monday | 7:30 AM | ℹ️ No email (due date is today, not tomorrow) |

**How to Test**:
```bash
# On Friday (March 14, 2026)
php test-weekend-sla.php    # Should show "PERFECT TEST SCENARIO"
php artisan sla:test reminder  # Should send email

# On Saturday (March 15, 2026)
php test-weekend-sla.php    # Should show "Cannot send emails today (weekend)"
php artisan sla:test reminder  # Should skip with reason: "Not a working day"

# On Sunday (March 16, 2026)
php test-weekend-sla.php    # Should show "Cannot send emails today (weekend)"
php artisan sla:test reminder  # Should skip with reason: "Not a working day"
```

---

### **Scenario 2: Regular Weekday → Weekday**

**Setup**: Today is Tuesday, SLA due date is Wednesday

| Day | Expected Behavior |
|-----|-------------------|
| Tuesday | ✅ **Email SENT** - Normal reminder |
| Wednesday | ℹ️ No email (due date is today) |

**How to Test**:
```sql
-- Set sla_duedate to tomorrow (Wednesday)
UPDATE tickets_chronologies 
SET sla_duedate = DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY) + INTERVAL 9 HOUR
WHERE id = YOUR_TEST_CHRONOLOGY_ID;
```

```bash
php artisan sla:test reminder  # Should send email
```

---

### **Scenario 3: Thursday → Friday**

**Setup**: Today is Thursday, SLA due date is Friday

| Day | Expected Behavior |
|-----|-------------------|
| Thursday | ✅ **Email SENT** - Normal reminder |
| Friday | ℹ️ No email (due date is today) |

Similar to Scenario 2.

---

## 🔍 Troubleshooting

### **No emails sent even on working days**

Check:
1. **Test data exists?**
   ```bash
   php test-weekend-sla.php  # Shows candidates
   ```

2. **Ticket status valid?**
   ```sql
   SELECT t.ticket_nolog, t.tick_status, tc.sla_duedate
   FROM tickets t
   JOIN tickets_chronologies tc ON t.id = tc.ticket_id
   WHERE tc.sla_duedate BETWEEN CURDATE() + INTERVAL 1 DAY 
     AND CURDATE() + INTERVAL 1 DAY + INTERVAL 23 HOUR;
   ```
   Status must NOT be 5 (Batal) or 6 (Selesai)

3. **Officer has email?**
   ```sql
   SELECT u.id, u.nama, u.email
   FROM users u
   JOIN tickets_chronologies tc ON u.id = tc.escalate_to
   WHERE tc.sla_duedate BETWEEN CURDATE() + INTERVAL 1 DAY 
     AND CURDATE() + INTERVAL 1 DAY + INTERVAL 23 HOUR;
   ```

4. **Mail configured?**
   ```bash
   php artisan config:cache
   php artisan tinker
   >>> config('mail.default')  # Should show your mail driver
   ```

---

### **Emails sent on weekends**

This should NOT happen if updates are correct. Check:

1. **Scheduler has skip logic?**
   ```bash
   grep -A 5 "skip(function" routes/console.php
   ```
   Should see weekend check.

2. **Service has working day check?**
   ```bash
   grep -A 5 "isWorkingDay" app/Services/TicketSLAReminderService.php
   ```
   Should see check at start of `sendBeforeDueReminders()`.

---

## 📧 Manual Email Testing

If you want to test the email template without waiting:

```bash
php artisan tinker
```

```php
// Get a test ticket with chronology
$chrono = App\Models\TicketChronology::whereNotNull('sla_duedate')
    ->whereNotNull('escalate_to')
    ->with(['ticket', 'escalatedTo'])
    ->first();

// Send test email
if ($chrono && $chrono->ticket && $chrono->escalatedTo) {
    $chrono->escalatedTo->notify(
        new App\Notifications\Ticket\ReminderNotification($chrono->ticket)
    );
    echo "✅ Test email sent to: " . $chrono->escalatedTo->email . "\n";
} else {
    echo "❌ No valid test data found\n";
}
```

---

## 🎯 Success Criteria

✅ **Test passes if:**
1. On Friday with Monday due date: Email sent with special message
2. On Saturday: System skips, logs show "SKIPPED execution on weekend"
3. On Sunday: System skips, logs show "SKIPPED execution on weekend"
4. Email shows: "Nota: Tarikh akhir SLA jatuh pada hari Isnin..." when due date is Monday
5. Regular weekdays: Normal emails sent without weekend note

---

## 📝 Additional Commands

```bash
# Test both reminder and overdue
php artisan sla:test all

# Test only reminder
php artisan sla:test reminder

# Test only overdue
php artisan sla:test after-due

# Run actual scheduler command (what runs daily at 7:30 AM)
php artisan sla:send-notifications --type=all

# Check scheduler is registered
php artisan schedule:list
```

---

## 📚 Related Files

- `routes/console.php` - Scheduler with weekend skip logic
- `app/Services/TicketSLAReminderService.php` - Main service with working day check
- `app/Services/TicketReminderService.php` - Alternative service
- `app/Notifications/Ticket/ReminderNotification.php` - Email notification class
- `resources/views/emails/ticket/sla-reminder.blade.php` - Email template
- `test-weekend-sla.php` - Test preparation script
- `app/Helpers/Helpers.php` - Working day calculation functions

---

**Last Updated**: March 16, 2026
