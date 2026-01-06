# Test Ticket Scripts - Usage Guide

## 📂 Location
All test scripts are located in: `resources/views/tickets/test-ticket/`

## 📝 Available Scripts

### 1. **create-test-ticket.php**
Creates a test ticket with due date tomorrow for testing email reminders.

**Run:**
```bash
php resources/views/tickets/test-ticket/create-test-ticket.php
```

**What it does:**
- Creates/finds test user with email `sabariah.hisham@treasury.gov.my`
- Creates test ticket with status "Aduan Baru"
- Sets `tick_duedate` to tomorrow
- Assigns ticket to you as Level 1 officer
- Creates chronology entry

---

### 2. **test-ticket-reminder.php**
Verifies the ticket reminder system is working correctly.

**Run:**
```bash
php resources/views/tickets/test-ticket/test-ticket-reminder.php
```

**What it does:**
- Checks tomorrow's date
- Finds tickets with status "Aduan Baru" due tomorrow
- Verifies Level 1 officer assignment
- Shows sample tickets for reference

---

### 3. **test-status-notification.php**
Tests status change notifications for all ticket statuses.

**Run:**
```bash
php resources/views/tickets/test-ticket/test-status-notification.php
```

**What it does:**
- Creates/finds test user and ticket
- Shows interactive menu to select status change
- Sends notification email
- Shows email preview
- Updates ticket status

**Status options:**
1. Aduan Baru → Aduan Diproses
2. Aduan Diproses → Serahan Kepada
3. Serahan Kepada → Aduan Dirujuk
4. Aduan Dirujuk → Aduan Selesai
5. Any Status → Aduan Dibatal
6. Test with current status

---

## 🚀 Quick Test Flow

### Test Email Reminder System:
```bash
# Step 1: Create test ticket
php resources/views/tickets/test-ticket/create-test-ticket.php

# Step 2: Verify ticket setup
php resources/views/tickets/test-ticket/test-ticket-reminder.php

# Step 3: Send reminder email
php artisan tickets:send-duedate-reminders
```

### Test Status Change Notifications:
```bash
# Run interactive test
php resources/views/tickets/test-ticket/test-status-notification.php
```

---

## ⚙️ Requirements

Make sure `.env` has proper email configuration:
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="eHelp System"
```

---

## 📧 Email Will Be Sent To
`sabariah.hisham@treasury.gov.my`

Check your inbox after running the tests!

---

## 🔄 Scheduled Task

The reminder command runs automatically via cron:
- Schedule: Daily at 9:00 AM
- Command: `php artisan tickets:send-duedate-reminders`
- Configured in: `routes/console.php`

To ensure it runs, add this to your server's crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📝 Notes

- Test scripts are safe to run multiple times
- Test tickets will have prefix "TEST-" in ticket number
- Notifications won't be sent if user has no email address
- Check Laravel logs if emails fail: `storage/logs/laravel.log`
