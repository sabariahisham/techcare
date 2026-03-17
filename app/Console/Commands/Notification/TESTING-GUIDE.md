# 🧪 Testing Guide: SLA Weekend Reminder System

## Overview
This guide helps you test the weekend skip logic for SLA reminders.

**Scenario**: When SLA due date is Monday, email should be sent on Friday only (not Saturday/Sunday).

---

## ✅ Quick Test Steps

### **Step 1: Check Current Date & Setup**
```bash
php artisan sla:test-weekend
```

This command will:
- Show today's date and day of week
- Calculate next working day
- Show if today is ideal for testing (Friday → Monday)
- Check for existing test data
- Provide guidance on what to do next

---

### **Step 2: Create Test Data**

**For Reminder Notifications (1 day BEFORE due):**
```bash
php artisan sla:create-test-data
```

**For Overdue Notifications (1 day AFTER due):**
```bash
php artisan sla:create-overdue-data
```

These commands will:
- Find active tickets and users with email
- Calculate appropriate due dates
- Create test chronology records
- Validate the data is ready for testing

---

### **Step 3: Run Test Commands**

**Test Reminder (1 day before):**
```bash
php artisan sla:test reminder
```

**Test Overdue (1 day after):**
```bash
php artisan sla:test after-due
```

**Test Both:**
```bash
php artisan sla:test all
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

### **Step 4: Debug Issues**

If emails are not sent, run the debug command:
```bash
php artisan sla:debug-email
```

This will check:
- ✓ Mail configuration (Mailpit)
- ✓ Test data availability  
- ✓ Working day status
- ✓ Database connection
- ✓ Query results

---

### **Step 5: Verify Results**

#### **A. Check Mailpit**
```
Open: http://localhost:8025
```
Look for emails with subject:
- `⚠️ Peringatan SLA - Hampir Tamat Tempoh` (Reminder)
- `🚨 SLA Overdue` (Overdue)

#### **B. Check Logs**
```bash
tail -50 storage/logs/laravel.log
tail -50 storage/logs/sla.log
```

Look for:
- `[SLA][REMINDER][START]` - Process started
- `[SLA][REMINDER][FOUND]` - Tickets found
- `SLA Reminder sent successfully` - Email sent

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
# On Friday
php artisan sla:test-weekend      # Should show "PERFECT TEST SCENARIO"
php artisan sla:create-test-data  # Create test data
php artisan sla:test reminder     # Should send email

# On Saturday
php artisan sla:test-weekend      # Should show "Cannot send emails today (weekend)"
php artisan sla:test reminder     # Should skip with reason: "Not a working day"

# On Sunday
php artisan sla:test-weekend      # Should show "Cannot send emails today (weekend)"
php artisan sla:test reminder     # Should skip with reason: "Not a working day"
```

---

### **Scenario 2: Regular Weekday → Weekday**

**Setup**: Today is Tuesday, SLA due date is Wednesday

| Day | Expected Behavior |
|-----|-------------------|
| Tuesday | ✅ **Email SENT** - Normal reminder |
| Wednesday | ℹ️ No email (due date is today) |

**How to Test**:
```bash
php artisan sla:create-test-data  # Creates data for next working day
php artisan sla:test reminder     # Should send email
```

---

### **Scenario 3: Overdue Testing**

**Setup**: Today is Monday, SLA was due on Friday (OVERDUE)

| Day | Expected Behavior |
|-----|-------------------|
| Monday | ✅ **Email SENT** - Overdue warning |

**How to Test**:
```bash
php artisan sla:create-overdue-data  # Creates overdue test data
php artisan sla:test after-due       # Should send overdue email
```

---

## 🔍 Troubleshooting

### **No emails sent even on working days**

1. **Run debug command:**
   ```bash
   php artisan sla:debug-email
   ```
   This will identify all issues automatically.

2. **Check test data:**
   ```bash
   php artisan sla:test-weekend
   ```
   Shows available test candidates.

3. **Recreate test data:**
   ```bash
   php artisan sla:create-test-data --force
   ```

4. **Check mail configuration:**
   ```bash
   php artisan config:cache
   php artisan tinker
   >>> config('mail.default')  # Should show 'smtp'
   >>> config('mail.mailers.smtp.host')  # Should show '127.0.0.1' or 'localhost'
   ```

---

### **Emails sent on weekends (Should NOT happen)**

This indicates a configuration issue. Check:

1. **Scheduler has skip logic:**
   ```bash
   cat routes/console.php | grep -A 5 "skip(function"
   ```
   Should see weekend check.

2. **Service has working day check:**
   ```bash
   cat app/Services/TicketSLAReminderService.php | grep -A 5 "isWorkingDay"
   ```
   Should see check at start of `sendBeforeDueReminders()`.

---

## 📧 Manual Email Testing

Test email template without scheduling:

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
1. ✅ On Friday with Monday due date: Email sent with special message
2. ✅ On Saturday: System skips, logs show "SKIPPED execution on weekend"
3. ✅ On Sunday: System skips, logs show "SKIPPED execution on weekend"
4. ✅ Email shows: "Nota: Tarikh akhir SLA jatuh pada hari Isnin..." when due date is Monday
5. ✅ Regular weekdays: Normal emails sent without weekend note
6. ✅ Mailpit receives all test emails
7. ✅ Logs show proper tracking of all actions

---

## 📝 Available Commands Summary

### **Setup & Preparation**
```bash
php artisan sla:test-weekend          # Check test scenario and available data
php artisan sla:create-test-data      # Create reminder test data
php artisan sla:create-overdue-data   # Create overdue test data
php artisan sla:debug-email           # Debug email system issues
```

### **Testing**
```bash
php artisan sla:test reminder         # Test reminder notifications
php artisan sla:test after-due        # Test overdue notifications
php artisan sla:test all              # Test both types
```

### **Production Commands**
```bash
php artisan sla:send-notifications --type=all    # Actual scheduler command (runs at 7:30 AM)
php artisan schedule:list                        # View all scheduled tasks
php artisan schedule:run                         # Run scheduler manually
```

---

## 📚 Related Files

### **Console Commands**
- `app/Console/Commands/Notification/TestWeekendSLA.php` - Weekend scenario checker
- `app/Console/Commands/Notification/DebugSLAEmail.php` - Email system debugger
- `app/Console/Commands/Notification/CreateSLATestData.php` - Create reminder test data
- `app/Console/Commands/Notification/CreateOverdueTestData.php` - Create overdue test data
- `app/Console/Commands/TestSLANotifications.php` - Main test command
- `app/Console/Commands/SendSLANotifications.php` - Production command

### **Core Services**
- `app/Services/TicketSLAReminderService.php` - Main SLA service
- `app/Services/TicketReminderService.php` - Alternative reminder service
- `app/Services/TicketOverdueService.php` - Overdue service

### **Notifications & Views**
- `app/Notifications/Ticket/ReminderNotification.php` - Reminder email
- `app/Notifications/Ticket/AfterDueNotification.php` - Overdue email
- `resources/views/emails/ticket/sla-reminder.blade.php` - Reminder template
- `resources/views/emails/ticket/sla-overdue.blade.php` - Overdue template

### **Configuration**
- `routes/console.php` - Scheduler with weekend skip logic
- `app/Helpers/Helpers.php` - Working day calculation functions
- `.env` - Mail configuration (MAIL_MAILER, MAIL_HOST, MAIL_PORT)

---

## 🆘 Quick Reference

**Problem**: No emails received
**Solution**: `php artisan sla:debug-email`

**Problem**: Need test data
**Solution**: `php artisan sla:create-test-data`

**Problem**: Test overdue scenario
**Solution**: `php artisan sla:create-overdue-data && php artisan sla:test after-due`

**Problem**: Check if weekend logic works
**Solution**: `php artisan sla:test-weekend`

**Problem**: View Mailpit
**Solution**: Open `http://localhost:8025`

---

**Last Updated**: March 17, 2026  
**Location**: `app/Console/Commands/Notification/TESTING-GUIDE.md`
